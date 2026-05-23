# WINCH — Transport Order Assignment

A Laravel + Vue.js solution to the WINCH Engineering home assignment: assign each transport order to the most suitable available driver, with operations team visibility into pending orders and per-driver order history.

The interesting parts are the architectural decisions, not the code volume. Two written documents go with this repo:

- **[ARCHITECTURE.md](./ARCHITECTURE.md)** — Part 2 of the assignment: the write-pressure / read-pressure scenario and how I'd solve it.
- **[benchmarks/SUMMARY.md](./benchmarks/SUMMARY.md)** — measured before/after comparisons for the read-path Redis migrations.

---

## Stack

- PHP 8.3+, Laravel 13
- PostgreSQL (for the partial unique index trick + GEO-friendly indexing — see Part 2)
- Redis (GEO set for driver locations, ZSET for pending orders)
- Vue 3 + Vue Router, Tailwind v4, built via Vite
- Pest v4 for tests (skipped — see "What was left out")

---

## Setup

Only prerequisite: Docker.

```bash
# 1. Start everything (PHP 8.3 + PostgreSQL 16 + Redis 7)
docker compose up -d --build

# 2. Install deps + build assets + migrate + seed (run once)
docker compose exec app sh -c "\
  composer install && \
  npm install && npm run build && \
  cp -n .env.example .env && \
  php artisan key:generate && \
  php artisan migrate:fresh --seed"

# 3. Open the app
# http://localhost:8000
```

That's it. The container runs `php artisan serve` on port 8000. PostgreSQL and Redis are reachable from your host on `5432` / `6379` if you want to inspect them.

**Background worker** (drains the staging table — see ARCHITECTURE.md):

```bash
docker compose exec app php artisan orders:process --loop
```

**Reset everything:**

```bash
docker compose down -v && docker compose up -d --build
```

If Redis ever drifts from DB (it shouldn't — observers + the seeder keep it in sync), rebuild from DB:

```bash
docker compose exec app php artisan drivers:backfill-locations-to-redis
docker compose exec app php artisan orders:backfill-pending-to-redis
```

---

## API

| Method | Path | Purpose |
|---|---|---|
| `GET` | `/api/orders/pending` | Paginated pending orders (reads Redis ZSET, hydrates from DB) |
| `POST` | `/api/orders` | Stage a new order in `unprocessed_orders` (returns 202) |
| `POST` | `/api/orders/{id}/assign` | Run the filter/score pipeline and assign best available driver |
| `GET` | `/api/drivers` | Paginated drivers list with vehicle + location |
| `GET` | `/api/drivers/{id}/orders?status=...` | One driver's order history with status filter |

Benchmark-only endpoints (kept for reproducibility, marked `BENCHMARK-ONLY` in code):

- `GET /api/orders/pending-db` — same as `/pending` but reads DB instead of Redis
- `GET /api/drivers/nearby?lat=X&lng=Y&radius=K` — Redis GEO path
- `GET /api/drivers/nearby-haversine?...` — PHP Haversine path

---

## Background worker

The write-absorption flow needs a worker to drain the staging table:

```bash
php artisan orders:process              # one batch (default 500)
php artisan orders:process --loop       # keep draining until empty
php artisan orders:process --batch=100  # smaller batches
```

In production this would run on a schedule or as a daemon. See `ARCHITECTURE.md` for the reasoning.

---

## Architecture

Strict DDD layout per the assignment's mandatory structure:

```
src/
├── Domain/
│   ├── Driver/         # Driver, Vehicle, DriverLocation
│   ├── Order/          # Order, UnprocessedOrder, the assign pipeline
│   └── Shared/         # Base Action class
└── Presentation/
    └── Api/            # Controllers, Requests, Resources, Routes
```

**Contract rule** — every domain exposes itself via a `*Contract` interface, never via concrete classes. Cross-domain calls go through the contract only (e.g. `OrderService` injects `DriverServiceContract`, never `DriverService`). Service providers bind contracts to implementations.

**Action classes** — each use case is a small Action with `handle()`. Services are thin orchestrators that call actions. This keeps each piece small enough to read in one screen.

**The assign pipeline** — Kubernetes-inspired:
1. `GetAvailableDriversNearbyAction` — Redis GEOSEARCH returns nearby driver IDs, then DB load with vehicle eager
2. **Filters** — pluggable `DriverFilterContract` implementations (`VehicleTypeFilter`, `VehicleCapacityFilter`). Each takes a `Collection<Driver>` and returns a narrower one.
3. **Scorers** — pluggable `DriverScorerContract` implementations (`DistanceScorer`, `VehicleCapacityFitScorer`). The final pick is the highest combined score.
4. **Transaction** — `DB::transaction` wraps `MarkOrderAsAssignedAction` + `DriverService::markUnavailable` so both succeed or both roll back.

Adding a new filter or scorer is a single class file. No existing code changes — open/closed in practice.

---

## Redis usage

Three places, each with a contract + a Redis implementation:

| Use case | Contract | Redis key | Backed by |
|---|---|---|---|
| Driver proximity lookup | `DriverLocationStoreContract` | `drivers:locations` (GEO) | `RedisDriverLocationStore` |
| Pending orders queue | `PendingOrderStoreContract` | `orders:pending` (ZSET, score = `created_at`) | `RedisPendingOrderStore` |
| Write absorption (staging) | _DB-only_ | — | `unprocessed_orders` table |

Both Redis stores are kept in sync by Eloquent observers:
- `DriverLocationObserver` — `saved` / `deleted` on `DriverLocation`
- `OrderObserver` — `saved` / `deleted` on `Order`, removes from ZSET when status leaves `pending`

Observers don't fire on bulk inserts or query-builder updates. Two places work around this:
- `DatabaseSeeder` uses `WithoutModelEvents`, so it mirrors to Redis explicitly at the end.
- `ProcessUnprocessedOrdersAction` uses bulk INSERT, so it manually pushes to the ZSET after.

---

## Key decisions

- **PostgreSQL over MySQL** — partial unique index (`WHERE status IN ('assigned', 'being_served')`) for the one-active-order-per-driver guarantee. Possible in MySQL only with workarounds (the "null trick" using generated columns). Was added then removed during development — happy to add back.
- **Redis is the cache, PostgreSQL is the source of truth** — every Redis structure has a corresponding DB row, and a backfill command to rebuild Redis from DB. If Redis dies, no data is lost.
- **DDD with strict contracts** — every cross-domain call goes through a `*Contract` interface. This was assignment-mandated, but it also keeps the assign pipeline cleanly testable without a working driver implementation.
- **No queue for the worker** — `orders:process` is a sync artisan command. Production would run it on schedule or as a Horizon job. Kept simple here.
- **Filter / Score pipeline as Strategy pattern** — each is a single-method interface. Adding a "must have refrigeration" filter or "driver-rating" scorer is a new file, no edits to existing code.

---

## What was left out and why

- **No automated tests.** The assignment marks them optional and I prioritized the benchmarks instead since they're the more interesting artifact for evaluating engineering judgment. The Pest scaffolding is in place; happy to add coverage on request.
- **No POST /api/orders in the Vue UI.** The endpoint exists and is curl-tested; building an order-creation form added no architectural value.
- **No authentication.** The ops team page assumes a single-user trusted environment for the demo.
- **No "start trip" endpoint** (assigned → being_served). The data model supports it; the action would mirror `MarkOrderAsAssignedAction` exactly.
- **`unprocessed_orders` is drained synchronously by an artisan command.** Production would run it as a daemon, on a Laravel scheduler tick, or via Horizon. The flow itself is the architectural piece — the runtime is configuration.

---

## Useful commands

Prefix any of these with `docker compose exec app` to run inside the container:

```bash
php artisan migrate:fresh --seed                       # rebuild DB + Redis (default seed)
php artisan db:seed --class=BenchmarkSeeder            # heavy: 1M orders, 50k pending
php artisan db:seed --class=BenchmarkDriversSeeder     # heavy: 10k available drivers
php artisan orders:process --loop                      # drain unprocessed_orders
php artisan drivers:backfill-locations-to-redis        # rebuild Redis GEO from DB
php artisan orders:backfill-pending-to-redis           # rebuild Redis ZSET from DB
```
