# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**Ali ERP Analytics** — an Arabic (RTL) business-intelligence reporting app built on plain **PHP 8.2+ and SQLite**, with **no framework, no Composer, and no runtime dependencies**. It renders an executive dashboard plus sales, customers, AR aging, inventory, and finance reports, all server-rendered. UI text, comments, and error messages are in Arabic.

## Environment (this Windows machine)

- No global `php` on PATH. Use the WampServer binary: `C:/wamp64/bin/php/php8.3.28/php.exe` (other versions live under `C:/wamp64/bin/php/`).
- `gh` CLI: `C:/Program Files/GitHub CLI/gh.exe`. Remote: https://github.com/alimossaed38/erp-report-generator
- Requires the `pdo_sqlite` extension enabled in `php.ini`.

## Commands

```bash
# Serve — the trailing `public/index.php` router script is REQUIRED, or /assets/* 404s
# (assets live outside the docroot and are streamed by the front controller).
php -S 127.0.0.1:8010 -t public public/index.php     # or: tools\start-windows.bat

# Regenerate the demo database (DESTRUCTIVE — deletes database/erp.sqlite, reseeds)
php database/seed.php

# Run all unit suites — must print "TESTS COMPLETED" and exit 0
php tests/run_unit.php

# Run a single unit suite (assertions must be enabled or nothing is tested)
php -d zend.assertions=1 -d assert.exception=1 tests/unit/sales_repo_test.php

# Verify PHP version + extensions + database writability
php tools/check_requirements.php
```

Tests run against the **real seeded `database/erp.sqlite`**, not a fixture — reseed first if data looks wrong. `run_unit.php` spawns each `*_test.php` in its own PHP process with assertions on; the tests use bare `assert()` and skip (not fail) when `pdo_sqlite` is missing, except `router_test.php`.

## Architecture

Request flow: **`public/index.php` (front controller) → `Router` → `Controller` → `Repository` (Models) → `Controller::render` → `Views/layout.php` + view partial**.

- **`public/index.php`** is the only entry point. It: serves `/assets/{css,js}/*` directly (whitelisted regex, outside docroot); registers a manual autoloader that resolves any class from `app/Core`, `app/Controllers`, or `app/Models` by filename; sets security headers (CSP, nosniff, frame-options); installs a Throwable handler that renders a generic Arabic 500 page; then declares every route and dispatches. **Add new pages by registering a route here** and creating the matching controller.

- **`Router`** does exact-path matching only (no params, no wildcards). Path params are passed as query strings (e.g. `/customers/view?name=...`, `/invoices/view?no=...`). Handlers are `[Controller::class, 'method']` pairs.

- **Controllers** (`app/Controllers/*Controller.php`) extend `Controller`. They parse/validate input via `Request`, call one or more repositories + static analytics helpers, and call `$this->render('viewname', [...data])`. `render()` `extract()`s the data array and `require`s `Views/layout.php`, which in turn includes the view named by `$__view`. There is no separate templating engine — views are PHP.

- **Models are repositories** (`app/Models/*Repository.php`), not entities. Each takes `Database::connection()` in its constructor and exposes query methods returning plain arrays of scalars (every column is explicitly cast). `SalesRepository` is the largest and backs sales, customers, aging, and invoice/product drill-downs.

- **`app/Core` helpers** (all static, stateless unless noted):
  - `Database` — singleton PDO to `database/erp.sqlite`. On first connect it enables foreign keys, WAL, a 5s busy timeout, and idempotently creates indexes (`CREATE INDEX IF NOT EXISTS`) — so indexes exist even if the DB predates `schema.sql`.
  - `Config` — reads `config/app.php` once, cached. Access via `Config::get('key', $default)`. **All tenant/branding config lives in `config/app.php`** (name, company, currency, timezone, `per_page_options`, logo SVG, print footer, `net_terms_days`).
  - `Request` — input validation/sanitization. Always go through it for `$_GET`: `date()` rejects malformed dates, `int()` clamps to min/max, `enum()` whitelists, `search()` trims and unicode-truncates. Never read `$_GET` directly.
  - `Ui` — output helpers: `e()` (htmlspecialchars — escape everything rendered), `money`/`number`/`percent` formatters, `url()` builder, and inline SVG `icon()`. Views should escape via `Ui::e`.
  - `Report` — pure date/paging math: `orderedRange`, `previousRange` (for period-over-period comparison), `quickRanges`, `change` (% delta), `pagination`.
  - `Analytics` — pure analytics: MoM/YoY `growth`, `movingAverage`, AR `agingBuckets`, `targetProgress`. Note `growth` aligns YoY by the `ym` key and deliberately returns `null` (rather than mis-comparing) when the exact 12-months-prior month is missing.
  - `helpers.php` — legacy global `money()`; prefer `Ui::money`.

- **Database schema** (`database/schema.sql`): `products`, `sales_invoices` (has `due_date`, `amount_paid`), `sales_items`, `transactions` (income/expense), `targets` (per-period sales target). `seed.php` drops and rebuilds everything with a **deterministic LCG (seed 12345)** so data is identical across runs — don't introduce nondeterminism into the seed.

- **Report assistant** (`AssistantController` + `app/Core/Intent.php`): a floating chat widget (rendered in `layout.php`, driven by `assets/js/app.js`) that answers Arabic report requests. `Intent::parse($text, $refDate)` is a **pure, unit-tested** parser (keyword + regex, folds hamza/alef/diacritics and Arabic-Indic digits) that turns a request into `{report, from, to, search, status, type, topN, export, format}`; it takes an injected reference date (the max sales date) so it stays DB-free and deterministic. `AssistantController::build()` maps that intent onto the **existing repository methods** (no new SQL, no external model — the system stays offline) and returns JSON consumed by the widget over `/assistant/ask`. All widget output is inserted via `textContent`/`createElement` (no HTML injection).

- **Exports** (`ExportController`): CSV and Excel-HTML only (no JSON — it was removed intentionally). Both stream with a UTF-8 BOM for Arabic, and `safeSpreadsheetCell` prefixes `=+-@` cells with `'` to block spreadsheet formula injection. Export reuses repository page methods with a large `perPage` (10000) to dump all matching rows.

## Conventions

- **Security is load-bearing, not incidental**: all SQL uses prepared statements with bound params (see `SalesRepository::filters`); all HTML output is escaped with `Ui::e`; a strict CSP in `index.php` forbids external scripts/styles — **charts and JS are local (`assets/js/app.js`), never CDN**. Preserve these when editing.
- Sort/filter columns are whitelisted via `$sortMap` lookups in repositories — never interpolate a raw sort/direction value into SQL.
- Keep new PHP `declare(strict_types=1)`-clean and match the existing explicit-cast style when returning repository rows.
- Docs and design specs live in `docs/` (`PROJECT_REPORT.md`, `UPGRADE_REPORT.md`, `SYSTEM_REPORT.html/.pdf`) and `docs/superpowers/`. E2E verification is done via Playwright MCP; reference screenshots are tracked in `tests/screenshots/`.
