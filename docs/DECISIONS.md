# Technical decisions

Short rationale for the choices made in TreeVisits. The guiding principle from
the brief was *clarity and simplicity over completeness*.

## Stack: PHP + Laravel

Laravel gives a lot for free that this assessment touches directly — routing,
request validation, an ORM, migrations, a test harness and a templating engine —
without pulling in extra dependencies. That keeps the amount of bespoke code low
and lets the domain logic stand out.

## Persistence: MySQL

The brief allows anything from an in-memory variable to a database. The project
runs on **MySQL** (locally via XAMPP). It started on SQLite for zero-setup, but
moving to MySQL buys three things that matter here: it's closer to a production
deployment, the `lockForUpdate` in `VisitService` becomes a real row lock (a no-op
on SQLite), and the integration tests exercise the *actual* driver instead of a
stand-in.

The trade-off is that the per-hour aggregation now uses MySQL's `DATE_FORMAT`
(`app/Services/VisitService.php`), which is driver-specific — see
[`IMPROVEMENTS.md`](IMPROVEMENTS.md) for abstracting it if multi-DB support is ever needed.

## Data model: two tables

- **`customers`** holds denormalized counters (`visits_count`, `trees_planted`,
  `last_visit_at`). These are the values read on every request, so keeping them
  on the row makes reads O(1) instead of re-aggregating.
- **`visits`** stores one row per event. This raw history is what makes the
  "visits per hour" view possible and keeps the door open for any other
  time-based analytics later.

Keeping both is a deliberate trade-off: a little write-time bookkeeping in
exchange for cheap, flexible reads.

## Trees: `floor(visits / X)` as a domain value object

Trees planted is derived as `floor(visits_count / X)` and recomputed on every visit,
rather than incremented with separate counting state. It's idempotent with respect to
the counter, impossible to get out of sync, and makes the rule obvious.

The rule lives in a framework-free value object, `App\Domain\TreeReward`
(`app/Domain/TreeReward.php`) — no Eloquent, no config, no database. It exposes
`treesFor(visits)` and `plantedBetween(before, after)`. `VisitService` receives it by
constructor injection and only orchestrates persistence. The single place that reads
`VISITS_PER_TREE` is the binding in `AppServiceProvider`, which constructs the
`TreeReward` with that value.

This split is deliberate: it keeps the business rule decoupled from the framework so it
can be unit-tested in isolation, without paying for full hexagonal layering (no
repositories or DTOs) on a service this small.

The visit is recorded and the counters updated inside a single DB transaction (with a
row lock on the customer) so concurrent events from the same customer can't corrupt the
count.

## Testing: a pyramid, not just integration

`TreeReward` is covered by **pure unit tests** (`tests/Unit/TreeRewardTest.php`) that
extend PHPUnit's `TestCase` directly — no Laravel bootstrap, no database — so they run in
milliseconds and form the base of the pyramid. The HTTP/persistence behaviour sits on top
as fewer **integration tests** (`tests/Feature/*`) using `RefreshDatabase` against a
dedicated MySQL test schema (`treevisits_test`).

## API shape

A small REST surface:

- `POST /api/visits` is the device-facing ingestion endpoint.
- `GET /api/visits/hourly` feeds the dashboard.
- `GET /api/customers` and `GET /api/customers/{id}` expose customer state.

Core logic lives in `VisitService` so it's testable in isolation and the
controllers stay thin. Responses go through a `CustomerResource` for one
consistent JSON shape.

## Frontend: server-rendered Blade + Chart.js via CDN

A single Blade page that fetches `/api/visits/hourly` and renders a bar chart plus
a fallback table. No Vite/npm build step on purpose — it keeps "clone and run" to
PHP only and there's no asset pipeline to explain for what is a simple view.

## What was intentionally left out

No authentication, no rate limiting, no pagination on the customers list, and no
device registry — all out of scope for the brief and easy to layer on. The
physical device is assumed to send a stable `customer_id` it already knows.
