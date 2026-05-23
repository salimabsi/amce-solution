# Benchmarks

Two read-path A/B benchmarks. Both compare the original DB-only path against the Redis-backed path. Both use Apache Bench against the local stack (Herd + PostgreSQL + Redis).

---

## 1. Pending Orders Read — DB partial index vs Redis ZSET

**Setup:** 1M orders (50k pending, 950k historical). `ab -n 1000 -c 50`.

**A:** `/api/orders/pending-db` → `WHERE status='pending'` with partial index
**B:** `/api/orders/pending` → `ZRANGE` + `whereIn(ids)`

| Metric | DB (before) | Redis (after) | Delta |
|---|---:|---:|---:|
| Req/sec | 244 | **441** | **+80%** |
| Mean | 205 ms | **113 ms** | **−45%** |
| p99 | 362 ms | **208 ms** | **−43%** |

**Why:** the DB sorts 50k pending rows by `created_at` on every request and holds a connection for ~180 ms. ZRANGE returns pre-sorted IDs in microseconds; the DB hit shrinks to a 5 ms PK lookup.

**Caveat:** single uncontended request, DB is ~10 ms faster. The Redis win is concurrency-scaled.

Raw outputs: [`before_db_partial_index.txt`](./before_db_partial_index.txt), [`after_redis_zset.txt`](./after_redis_zset.txt).

---

## 2. Driver Geo Filter — PHP Haversine vs Redis GEO

**Setup:** 10k available drivers spread around Riyadh (lat 24.40–25.00, lng 46.40–47.00). `ab -n 500 -c 20`. Query: 5 km radius from (24.7, 46.7) — returns 189 drivers.

**A:** `/api/drivers/nearby-haversine` → load all available drivers from DB, filter by Haversine in PHP
**B:** `/api/drivers/nearby` → `GEOSEARCH` returns only nearby IDs, then DB load those

| Metric | PHP Haversine (before) | Redis GEO (after) | Delta |
|---|---:|---:|---:|
| Req/sec | 13.4 | **259.4** | **×19** |
| Mean | 1489 ms | **77 ms** | **−95%** |
| p99 | 1610 ms | **140 ms** | **−91%** |

**Why:** Haversine is O(N) over all available drivers — load 10k rows, hydrate to PHP objects, run trig on every one, throw away 9,800. GEOSEARCH is O(log N + K) where K is the result size (a few dozen), then we only load those from DB.

**Scaling note:** at 50k drivers, the Haversine path doesn't even complete — PHP hits the 128 MB memory limit hydrating that many `Driver` models per request. Redis GEO is unaffected by the dataset size of the source set.

Raw outputs: [`drivers_before_php_haversine.txt`](./drivers_before_php_haversine.txt), [`drivers_after_redis_geo.txt`](./drivers_after_redis_geo.txt).

---

## Conclusion: keep both layers in both cases

| Layer | Role |
|---|---|
| Redis ZSET / GEO | Hot read path. Where reads actually happen. |
| Partial index / driver_locations table | Recovery path. Lets backfill commands rebuild Redis without full-table scans. |

Redis is the cache, the DB indexes and tables are the source of truth and the fallback. They don't compete — each is the right tool for a different failure mode.
