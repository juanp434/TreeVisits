# Future work

TreeVisits was kept deliberately simple. These are the improvements
worth making if it grew into a real service, grouped by area and roughly ordered by
priority within each.

## API & robustness

- **Idempotency on `POST /api/visits`** — a device retransmitting an event currently
  double-counts. Accept an optional event id / idempotency key and dedupe.
  (`app/Services/VisitService.php::registerVisit`)
- **Paginate `GET /api/customers`** — it returns every row today.
  (`app/Http/Controllers/CustomerController.php::index`)
- **Rate limiting / throttling** on the ingestion endpoint. (`routes/api.php`)
- **Device authentication** — the API is open; add a per-device token (or Sanctum).
- **Batch ingestion endpoint** — devices may buffer events offline and send in bulk.
- **API versioning** (`/api/v1`) and a generated **OpenAPI** spec alongside the Postman collection.

## Data & persistence

- **DB-agnostic hourly aggregation** — `visitsPerHour` uses MySQL's `DATE_FORMAT`, which is
  driver-specific; abstract it (e.g. switch on the connection driver) to also support
  Postgres/SQLite. (`app/Services/VisitService.php::visitsPerHour`)
- **Timezone handling** — hourly buckets are in stored time (UTC); a shop/customer timezone
  may be needed for meaningful "per hour" views.
- **Composite index `(customer_id, occurred_at)`** on `visits` to speed up the per-customer
  filter. (`database/migrations/*_create_visits_table.php`)
- **Precomputed hourly rollups** — for high volume, maintain a rollup table instead of
  aggregating raw rows on every dashboard load.
- **Historical trees** — trees are derived as `floor(visits / X)` and recompute if `X`
  changes. If the rule must be historical, record tree-planting events instead.

## Concurrency & scale

- **Harden the visit registration race** — `lockForUpdate()->firstOrCreate()` only locks an
  existing row; two concurrent *first* visits for the same customer can both miss and collide
  on the unique index. Prefer an atomic upsert / `INSERT ... ON DUPLICATE KEY` plus an atomic
  counter increment. (`app/Services/VisitService.php::registerVisit`)
- **Queue the visit processing** to absorb traffic spikes (ingest fast, count async).
- **Cache the `/api/visits/hourly` response** (short TTL) since the dashboard polls it.

## Frontend

- **Live updates** — auto-refresh/polling or websockets; today it fetches once on load.
- **Loading & error states** in `resources/views/dashboard.blade.php` (the `fetch` has none).
- **Date-range and per-customer filters** in the UI — the API already supports `customer_id`.

## Quality & CI

- **Static analysis & style** — PHPStan/Larastan + Laravel Pint.
- **CI pipeline** (GitHub Actions) running tests + lint on every push.
- **More tests** — concurrency, invalid/future `occurred_at`, pagination.
- **Factory + seeder** to generate sample data for demos.
