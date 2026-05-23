# Part 2 — Architectural Decision: Read & Write Pressure on the Orders Table

> **Scenario:** the system grows to millions of orders. Two pressures appear together: bulk write spikes during peak hours overwhelm direct `INSERT`s, and the ops team's "active orders" page now takes over 5 seconds because it reads from the same table.
>
> **Options to discuss:** (a) add an index, (b) Redis cache, (c) a separate table for active orders.

## Diagnose before you optimize

Before picking a solution I'd measure. The two symptoms ("inserts slow", "reads slow") have completely different shapes:

- **Read latency** — is the slow query doing a sequential scan, or is it using an index but doing a large sort? PostgreSQL's `EXPLAIN (ANALYZE, BUFFERS)` tells you which. The fix for "sequential scan over 50M rows" is an index. The fix for "sorting 50k matched rows" is a different index, or moving the sort out of the DB.
- **Write latency** — is the bottleneck CPU on `INSERT`, lock contention on the table or its indexes, or WAL flush? `pg_stat_activity` and `pg_locks` will show it. The fix depends on which.

The wrong-shape solution wastes time and money. "Throw Redis at it" can leave the real problem (e.g. a missing index) untouched.

With that said, the three options in the assignment are the right toolbox. Here's when each one applies and what it costs.

## Option (a) — A partial index

This is what I'd ship first. It costs minutes to add, doesn't change application code, and solves the read pressure cheaply for the case described.

The ops page does `WHERE status = 'pending' ORDER BY created_at`. A plain `(status)` index has bad selectivity at this cardinality (only ~5 values). The right shape is a **partial index** that only contains pending rows:

```sql
CREATE INDEX orders_pending_partial_idx
  ON orders (created_at)
  WHERE status = 'pending';
```

This index is tiny — only as big as the pending subset, regardless of how many millions of historical rows exist. PostgreSQL uses it for ordered scans without sorting the result set, and ignores it during writes that don't touch pending rows.

**Tradeoffs:**
- It doesn't reduce read latency to zero — there's still a query roundtrip and connection hold time. Under high concurrency, even a fast query can starve the connection pool.
- It doesn't help write pressure at all — `INSERT`s actually pay a small extra cost to update the index.
- It only works in PostgreSQL (or MySQL via the "null trick" — see `assignable` column note in the implementation chat).

Start here. Then measure again. If reads are now fine and writes were never the real bottleneck, **stop**. Don't add Redis or staging tables on speculation.

## Option (b) — Redis cache

If the partial index isn't enough — typically because of **concurrent read pressure**, not single-request latency — then a Redis cache earns its complexity.

For "ops team pending orders" specifically, a Redis **ZSET** (`orders:pending`, score = `created_at`) holds the queue. `ZRANGE 0 14` returns the first 15 IDs in microseconds, sorted, without holding a DB connection. The DB hit shrinks to `WHERE id IN (15 PKs)` — a primary-key lookup.

I implemented and measured this. On 1M orders with 50k pending, 50 concurrent users:

| Metric | DB + partial index | Redis ZSET | Delta |
|---|---:|---:|---:|
| Throughput | 244 req/s | 441 req/s | **+80%** |
| Mean latency | 205 ms | 113 ms | **−45%** |
| p99 | 362 ms | 208 ms | **−43%** |

For a single uncontended request the DB was actually ~10 ms faster — the partial index is genuinely fast. The Redis win is **concurrency-scaled**: the DB connection is held for ~5 ms instead of ~180 ms, so the pool serves many more requests per second. (Full report: [`benchmarks/SUMMARY.md`](./benchmarks/SUMMARY.md).)

**The real cost of Redis** is cache invalidation. Every code path that changes order state has to keep Redis in sync. I handled this with an `OrderObserver` that pushes/removes on every `saved`/`deleted` — but that doesn't fire on raw query-builder updates or bulk inserts, so two places (the seeder using `WithoutModelEvents`, and the bulk-insert worker) have to call the store directly. This is the kind of bug you don't notice until production data drifts.

**Decision rule:** DB stays as source of truth, Redis is a derived view, and a backfill artisan command rebuilds Redis from DB on demand. If Redis dies, nothing is lost.

## Option (c) — Separate active-orders table

I wouldn't reach for this for the read problem alone — Redis ZSET solves that more cleanly. But a **separate write-staging table** is the right answer for the **write pressure** half of the scenario, which (a) and (b) don't touch.

The pattern in the implementation:

1. `POST /api/orders` writes to `unprocessed_orders` — a small, narrow table optimized for fast inserts (no business indexes, just `id` and `created_at`).
2. A background worker (`orders:process`) drains it in batches: `lockForUpdate`, bulk `INSERT INTO orders (...) RETURNING id`, push IDs to the Redis ZSET, delete the staged rows. All inside one DB transaction.

Why this works:
- The user-facing path is a single `INSERT` into a tiny table — no contention with the main `orders` table's many indexes and partial unique constraints.
- The worker controls batch size and concurrency, so the main `orders` table sees predictable load even when traffic spikes 100×.
- Bulk insert amortizes per-row overhead.

**Tradeoffs:**
- Orders are eventually visible, not instantly. Acceptable for most flows (the ops team page polls), but breaks "read-your-own-writes" if the same client wants to see the order it just created. Mitigations: return the unprocessed_order id, or have the worker trigger a websocket.
- Adds operational complexity — a worker to monitor, a backlog metric to alert on, and a poison-pill problem (one bad row stalling the batch).
- Doesn't help the read path. It's purely a write-side decision.

## The integrated answer

Apply them in this order, measuring between each step:

1. **Partial index** — cheapest, fastest, often enough. Always measure first to confirm it solved the read problem.
2. **Redis ZSET (and GEO for driver proximity)** — only if read latency is still bad under concurrency. The benchmark proves the win is real.
3. **Staging table + worker** — only when write pressure is the actual problem, not just an assumed one. Validates the value of separating the user-facing write from the durable persistence write.

The three layers don't compete. The partial index becomes the recovery path for Redis. The staging table feeds both the main `orders` table and the Redis ZSET. Each layer serves a distinct failure mode, and none of them is wasted.

## What I'd add before production

- **Metrics** — request latency p50/p95/p99 per endpoint, DB pool wait time, Redis ZSET size, `unprocessed_orders` backlog depth, worker batch time. Without these, every "improvement" is a guess.
- **Redis durability** — RDB + AOF, replication, and a runbook for "Redis is down". Currently the code degrades to "ops page shows empty list" which is wrong silent behavior. Better: fall back to the partial-index DB query on Redis miss.
- **Worker as a daemon** — `supervisor` or Horizon, with a poison-pill quarantine (move repeatedly-failing rows to `failed_unprocessed_orders`).
- **The one-active-order-per-driver partial unique index** — I added it, then removed it during development to talk through tradeoffs. It belongs in production as the final safety net behind the application-level `is_available` flag and the assignment transaction. Race conditions across operators clicking "Assign" simultaneously are exactly the case where this constraint earns its keep.

## What I'd skip

- **A message queue (Kafka, RabbitMQ) instead of `unprocessed_orders`.** Adds another moving part. The staging-table pattern in Postgres has good enough throughput at most realistic scales, and recovery is just SQL.
- **Sharding the orders table.** This is the right answer at 100M+ orders, but it's a big jump in complexity. Get the partial index + Redis + staging combination measured first; sharding is only justified once you can show your single-node ceiling.
- **An "active orders" denormalized table** as proposed in option (c). The combination of partial index (for recovery) + Redis ZSET (for hot reads) covers the same ground with less write amplification.
