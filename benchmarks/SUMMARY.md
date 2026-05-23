# Pending Orders Read Path — Benchmark Summary

**Setup:** 1M orders (50k pending, 950k historical), Apache Bench at 50 concurrent users, 1000 requests.

**A:** `/api/orders/pending-db` → PostgreSQL `WHERE status='pending'` with partial index
**B:** `/api/orders/pending` → Redis `ZRANGE` + `whereIn(ids)`

| Metric | DB (before) | Redis (after) | Delta |
|---|---:|---:|---:|
| Req/sec | 244 | **441** | **+80%** |
| Mean | 205 ms | **113 ms** | **−45%** |
| p99 | 362 ms | **208 ms** | **−43%** |

**Why:** the DB query has to sort 50k pending rows by `created_at` on every request and holds a DB connection for ~180 ms. ZRANGE returns pre-sorted IDs in microseconds, so the DB hit shrinks to a 5 ms primary-key lookup — the connection pool serves many more requests per second.

**Caveat:** single uncontended request, DB is ~10 ms faster. The Redis win is concurrency-scaled.

## Conclusion: keep both

- **Redis ZSET** — hot read path. +80% throughput, −45% latency under load.
- **Partial index** — recovery path. Lets the backfill command rebuild Redis without a full-table scan. Costs almost nothing (only pending rows).

They don't compete. Redis is the cache, the index is the fallback.

Raw `ab` outputs: [`before_db_partial_index.txt`](./before_db_partial_index.txt), [`after_redis_zset.txt`](./after_redis_zset.txt).
