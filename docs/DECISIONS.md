# Technical decisions

Short rationale for the choices made in TreeVisits. The guiding principle from
the brief was *clarity and simplicity over completeness*.

## Stack: PHP + Laravel

Laravel gives a lot for free that this assessment touches directly — routing,
request validation, an ORM, migrations, a test harness and a templating engine —
without pulling in extra dependencies. That keeps the amount of bespoke code low
and lets the domain logic stand out.

## Persistence: SQLite

The brief explicitly allows anything from an in-memory variable to a database.
SQLite is the middle ground: a *real* relational store (so the data model and the
per-hour aggregation are demonstrated with actual SQL) but with zero
infrastructure — it's just a file, no server to run. Switching to MySQL/Postgres
later is only a connection-string change.

## Data model: two tables

- **`customers`** holds denormalized counters (`visits_count`, `trees_planted`,
  `last_visit_at`). These are the values read on every request, so keeping them
  on the row makes reads O(1) instead of re-aggregating.
- **`visits`** stores one row per event. This raw history is what makes the
  "visits per hour" view possible and keeps the door open for any other
  time-based analytics later.

Keeping both is a deliberate trade-off: a little write-time bookkeeping in
exchange for cheap, flexible reads.

## Trees: `floor(visits / X)`

Trees planted is derived as `intdiv(visits_count, VISITS_PER_TREE)` and recomputed
on every visit, rather than incremented with separate counting state. It's
idempotent with respect to the counter, impossible to get out of sync, and makes
the rule obvious. `X` is read from the `VISITS_PER_TREE` config/env value.

The visit is recorded and the counters updated inside a single DB transaction
(with a row lock on the customer) so concurrent events from the same customer
can't corrupt the count.

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
