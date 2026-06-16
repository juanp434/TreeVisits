# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

TreeVisits is a Laravel service for the *"X visits = 1 tree"* brief: a shop device posts visit
events, the service counts visits per customer, stores their last visit, and plants one tree per
`X` visits. A Blade dashboard shows visits aggregated per hour. Stack: PHP 8.3 + Laravel 13 + MySQL.

## Environment (Windows — important)

PHP and Composer are **not on PATH** in spawned shells. Prepend the PHP dir at the start of any
command that calls `php`/`composer`:

```powershell
$env:Path = "C:\Users\G513\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe;" + $env:Path
```

`composer` resolves via a `composer.bat` wrapper in that same dir (it shells out to
`C:\Users\G513\composer\composer.phar`). The active `php.ini` lives in the PHP dir and enables
`pdo_mysql`, `mysqli`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`.

**MySQL runs via XAMPP** (`C:\xampp\mysql\bin\`). It is not a Windows service here — start it with
`C:\xampp\mysql\bin\mysqld.exe --standalone` (run in background). Default creds: user `root`, no
password. Databases: `treevisits` (app) and `treevisits_test` (test suite). Connect/admin with
`C:\xampp\mysql\bin\mysql.exe -u root`.

## Commands

```powershell
php artisan serve              # dev server at http://localhost:8000 (needs MySQL up)
php artisan migrate            # apply migrations to the `treevisits` MySQL database
php artisan migrate:fresh      # drop + re-run (wipes data)
php artisan test               # full suite (runs against `treevisits_test`, see phpunit.xml)
php artisan test --filter test_a_tree_is_planted_after_the_configured_number_of_visits  # single test
php artisan config:clear       # clear cached config after editing .env / config/*
```

`composer install` after cloning; copy `.env.example` to `.env` and `php artisan key:generate` if
`.env` is missing (it is gitignored).

## Architecture

Request flow: device → `routes/api.php` → thin controller → `VisitService` → MySQL → JSON via
`CustomerResource`.

- **API routes are registered manually.** Laravel 11+ ships no `routes/api.php`; it is wired in
  `bootstrap/app.php` via the `api:` argument to `withRouting()`. Sanctum was intentionally not
  installed. The `/api` prefix is applied automatically.
- **Domain rule is decoupled:** the "X visits = 1 tree" logic lives in the framework-free value
  object `app/Domain/TreeReward.php` (`treesFor()`, `plantedBetween()`), bound in
  `AppServiceProvider` from `config('trees.visits_per_tree')` — the only place that reads the config.
  It is covered by pure unit tests (`tests/Unit/TreeRewardTest.php`, extends PHPUnit's `TestCase`,
  no DB).
- **Core logic is isolated in `app/Services/VisitService.php`** — controllers (`VisitController`,
  `CustomerController`) stay thin. `registerVisit()` injects `TreeReward`, runs in a DB transaction
  with a row lock, and trees are **derived** (`treesFor(visits_count)`) recomputed on every visit
  (not incremented), so the counter can never drift. `tree_planted` is true only on the visit that
  crosses a boundary.
- **Two-table model:** `customers` holds denormalized counters (`visits_count`, `trees_planted`,
  `last_visit_at`) for O(1) reads; `visits` stores one row per event (`occurred_at`) so the
  per-hour view can aggregate raw history. Hourly aggregation uses MySQL `DATE_FORMAT(occurred_at, '%Y-%m-%dT%H:00')`
  — driver-specific SQL that would need changing for another DB. `Customer` sets `$attributes`
  defaults (0) so a freshly created row isn't null in memory before the DB defaults are read back.
- **`VISITS_PER_TREE`** (config `config/trees.php`, env `VISITS_PER_TREE`, default 5) is the single
  tunable. Tests pin it to 3 via `phpunit.xml` and override per-test with `config([...])`.
- **Frontend** is one server-rendered Blade view (`resources/views/dashboard.blade.php`) that fetches
  `/api/visits/hourly` and renders Chart.js from a CDN — no Vite/npm build step on purpose.

`customer_id` in the API is the customer's `external_id` (the stable id the device sends); the
internal numeric `id` is never exposed.

See `docs/DECISIONS.md` for the rationale behind these choices.
