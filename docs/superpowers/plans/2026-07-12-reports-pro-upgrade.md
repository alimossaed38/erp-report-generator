# Reports Pro-Upgrade — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Upgrade the v2.0 "Ali ERP Analytics" reports with deeper analytics + targets, drill-down pages, additional ERP reports (AR aging, executive summary), and branded print/PDF — all reliable for company decision-making.

**Architecture:** Build on v2.0 patterns. Repositories use named-param `filters()` + `Report::pagination`. New `Analytics` static helper for growth/moving-average/aging/targets. New `TargetRepository`. New controllers/views for drill-down and new reports. Exact-match router with query-param detail pages. Local JS charts, Arabic RTL, `Ui`/`Config`/`Report` helpers.

**Tech Stack:** PHP 8.3.28 (WampServer), SQLite, existing local `assets/js/app.js` chart engine, no Composer.

## Global Constraints

- PHP binary: `C:/wamp64/bin/php/php8.3.28/php.exe` (no global php). Serve: `-S 127.0.0.1:8010 -t public public/index.php` (router script REQUIRED for `/assets`).
- All output escaped via `Ui::e`; money via `Ui::money`; URLs via `Ui::url`; icons via `Ui::icon`. SQL strictly parameterized (named params; `bindValue(..., PDO::PARAM_INT)` for LIMIT/OFFSET) — mirror existing repositories exactly.
- Arabic RTL, UTF-8, `JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG` for any chart `data-values` JSON in attributes.
- Seed stays deterministic (LCG, seed=12345 sequence; no `rand()`/time).
- As-of date for aging = `SalesRepository::dateBounds()['max']`.
- Net terms = 30 days (`Config::get('net_terms_days', 30)`).
- Charts: reuse `assets/js/app.js` conventions — `<canvas data-values='…' data-type='line|bar' data-label='…'>`; multi-series `{label, series:{...}}`.
- Every task: run `tests/run_unit.php` (must end "TESTS COMPLETED", exit 0) before commit; commit with the Co-Authored-By trailer and `git push`.
- Tests live in `tests/unit/*_test.php` and `require __DIR__ . '/../bootstrap.php';` (mirror existing tests).
- Regenerate DB after schema/seed changes: `php database/seed.php`.

---

### Task A: Data foundation — payment + due dates + targets

**Files:**
- Modify: `database/schema.sql` (add columns + `targets` table + index)
- Modify: `database/seed.php` (deterministic payment data + targets)
- Create: `app/Models/TargetRepository.php`
- Modify: `public/index.php` (autoload already covers Models; no change unless needed)
- Test: `tests/unit/targets_test.php`; Modify `tests/unit/seed_test.php`

**Interfaces (produced):**
- `sales_invoices` gains `due_date TEXT NOT NULL`, `amount_paid REAL NOT NULL DEFAULT 0`.
- `targets(period TEXT PRIMARY KEY, sales_target REAL NOT NULL)`.
- `TargetRepository::all(): array` → `['YYYY-MM' => float]`.
- `TargetRepository::forPeriod(string $period): ?float`.
- `TargetRepository::range(?string $from, ?string $to): float` (sum of monthly targets whose `period` between substr(from,1,7)…substr(to,1,7); if null range → sum all).

- [ ] **Step 1: Write failing tests**

`tests/unit/targets_test.php`:
```php
<?php
require __DIR__ . '/../bootstrap.php';
$repo = new TargetRepository();
$all = $repo->all();
assert(count($all) >= 12, 'expected >=12 monthly targets');
assert(array_values($all)[0] > 0, 'target amount > 0');
$one = $repo->forPeriod(array_key_first($all));
assert($one !== null && $one > 0, 'forPeriod returns amount');
assert($repo->forPeriod('1900-01') === null, 'unknown period → null');
$sumAll = array_sum($all);
assert(abs($repo->range(null, null) - $sumAll) < 0.01, 'range(null,null)=sum all');
echo "targets_test OK\n";
```
Append to `tests/unit/seed_test.php` (before its final echo) assertions:
```php
$cols = array_column($db->query("PRAGMA table_info(sales_invoices)")->fetchAll(), 'name');
assert(in_array('due_date', $cols, true) && in_array('amount_paid', $cols, true), 'invoice payment columns exist');
$bad = (int) $db->query("SELECT COUNT(*) FROM sales_invoices WHERE amount_paid < 0 OR amount_paid > total + 0.01")->fetchColumn();
assert($bad === 0, 'amount_paid within [0,total]');
$targets = (int) $db->query("SELECT COUNT(*) FROM targets")->fetchColumn();
assert($targets >= 12, 'targets seeded');
$unpaid = (int) $db->query("SELECT COUNT(*) FROM sales_invoices WHERE amount_paid < total")->fetchColumn();
assert($unpaid > 0, 'some invoices have outstanding balance');
```

- [ ] **Step 2: Run tests — verify they fail** (`php tests/unit/targets_test.php` → cannot find TargetRepository; seed_test → missing columns).

- [ ] **Step 3: Update `database/schema.sql`**
  - In the `sales_invoices` CREATE, add after `total REAL NOT NULL`: `,\n    due_date TEXT NOT NULL,\n    amount_paid REAL NOT NULL DEFAULT 0`.
  - After the transactions table, add:
```sql
CREATE TABLE targets (
    period TEXT PRIMARY KEY,
    sales_target REAL NOT NULL
);
```
  - Add index near the others: `CREATE INDEX idx_sales_invoices_due ON sales_invoices(due_date);`

- [ ] **Step 4: Update `database/seed.php`** (keep LCG deterministic)
  - Change the invoice INSERT to include `due_date, amount_paid`. Compute `due_date` = `invoice_date` + 30 days (use `DateTimeImmutable($date)->modify('+30 days')->format('Y-m-d')`). After computing `$total`, decide payment deterministically using `$rand`: `$roll = $rand(1,100);` → if `$roll <= 65` paid=`$total`; elseif `$roll <= 80` paid = round(total × ($rand(30,80)/100), 2) (partial); else paid = 0. Update the invoice row with total, due_date, amount_paid (extend the existing UPDATE or set at INSERT time — recommended: INSERT with placeholder due_date/0 then a single `UPDATE sales_invoices SET total=?, due_date=?, amount_paid=? WHERE id=?`).
  - After the transactions loop, seed `targets`: for each of the 12 months (same ym derivation), compute that month's actual sales `SELECT COALESCE(SUM(total),0) FROM sales_invoices WHERE substr(invoice_date,1,7)=?`, then `sales_target = round(actual × ((85 + $rand(0,30))/100))` and INSERT into targets(period, sales_target). (This yields a mix above/below target.)

- [ ] **Step 5: Create `app/Models/TargetRepository.php`** — mirror repo style (constructor gets `Database::connection()`), methods as in Interfaces. `range` builds a parameterized `WHERE period >= :a AND period <= :b` using `substr` of from/to (or no where when both null) and returns `(float) SUM(sales_target)`.

- [ ] **Step 6: Regenerate DB + run tests** (`php database/seed.php` → `php tests/run_unit.php` → all green, incl. targets_test + seed_test).

- [ ] **Step 7: Commit + push** — `feat(data): invoice payments + due dates + monthly targets`.

---

### Task B: Analytics core (growth / moving average / aging / targets) + repo analytics

**Files:**
- Create: `app/Core/Analytics.php`
- Modify: `public/index.php` (add `require .../app/Core/Analytics.php` beside other Core requires; and confirm `TargetRepository` autoloads via Models)
- Modify: `app/Models/SalesRepository.php` (add margin + outstanding + aging query methods; add payment fields to invoice rows)
- Test: `tests/unit/analytics_test.php`; Modify `tests/unit/sales_repo_test.php` (assert new methods)

**Interfaces (produced):**
- `Analytics::growth(array $monthly, string $valueKey): array` — returns the same list, each row gains `mom` (?float %) and `yoy` (?float %). MoM = change vs previous element; YoY = change vs element 12 positions earlier (by ym match if present, else index-12); null when unavailable. Use `Report::change`.
- `Analytics::movingAverage(array $monthly, string $valueKey, int $window = 3): array` — each row gains `ma` (?float): average of current + previous `window-1` values; null until `window` values exist.
- `Analytics::agingBuckets(array $invoices, string $asOf): array` — input rows each have `outstanding` (float) and `due_date` (string). Returns `['current'=>f,'d1_30'=>f,'d31_60'=>f,'d61_90'=>f,'d90_plus'=>f,'total'=>f,'counts'=>['current'=>i,…,'d90_plus'=>i]]`. Bucket by `daysLate = days(asOf) - days(due_date)`: `<=0` current; `1–30` d1_30; `31–60` d31_60; `61–90` d61_90; `>90` d90_plus. Sum of buckets == total.
- `Analytics::targetProgress(float $actual, ?float $target): array` — `['actual'=>f,'target'=>?f,'pct'=>?f,'remaining'=>?f,'met'=>bool]`; pct=null when target null/0.
- `SalesRepository::marginSummary(?from,?to,?search): ['revenue'=>f,'cogs'=>f,'profit'=>f,'margin_pct'=>?f]` (cogs = Σ si.qty × p.cost over matching invoices; margin_pct = profit/revenue×100 or null).
- `SalesRepository::monthlyMargin(?from,?to): [ ['ym','revenue','cogs','profit'] ]` ordered by ym.
- `SalesRepository::outstanding(?from,?to): ['total_outstanding'=>f,'overdue'=>f,'invoice_count'=>i]` where overdue uses due_date < asOf (asOf = dateBounds max).
- `SalesRepository::agingInvoices(): [ rows with invoice_no, customer_name, invoice_date, due_date, total, amount_paid, outstanding ] ` for all invoices with outstanding>0.
- Invoice rows returned by `invoicePage`/`invoices` gain `due_date, amount_paid` and computed `outstanding`, `pay_status` ('paid'|'partial'|'unpaid').

- [ ] **Step 1: Write `tests/unit/analytics_test.php`** with known values:
```php
<?php
require __DIR__ . '/../bootstrap.php';
$m = [['ym'=>'2025-01','v'=>100],['ym'=>'2025-02','v'=>150],['ym'=>'2025-03','v'=>150]];
$g = Analytics::growth($m, 'v');
assert($g[0]['mom'] === null, 'first mom null');
assert(abs($g[1]['mom'] - 50.0) < 0.01, 'mom 100->150 = 50%');
assert($g[2]['mom'] !== null && abs($g[2]['mom']) < 0.01, '150->150 = 0%');
$ma = Analytics::movingAverage($m, 'v', 3);
assert($ma[0]['ma'] === null && $ma[1]['ma'] === null, 'ma null before window');
assert(abs($ma[2]['ma'] - (400/3)) < 0.01, 'ma of 100,150,150');
$inv = [
  ['outstanding'=>100.0,'due_date'=>'2026-07-01'], // asOf 2026-06-01 → current
  ['outstanding'=>50.0,'due_date'=>'2026-05-20'],  // 12 days late → d1_30
  ['outstanding'=>25.0,'due_date'=>'2026-03-01'],  // ~92 late → d90_plus
];
$a = Analytics::agingBuckets($inv, '2026-06-01');
assert(abs($a['total'] - 175.0) < 0.01, 'aging total');
assert(abs($a['current'] - 100.0) < 0.01, 'current bucket');
assert(abs($a['d1_30'] - 50.0) < 0.01, 'd1_30 bucket');
assert(abs($a['d90_plus'] - 25.0) < 0.01, 'd90_plus bucket');
assert(abs(($a['current']+$a['d1_30']+$a['d31_60']+$a['d61_90']+$a['d90_plus']) - $a['total']) < 0.01, 'buckets sum to total');
$tp = Analytics::targetProgress(80.0, 100.0);
assert(abs($tp['pct'] - 80.0) < 0.01 && $tp['met'] === false, 'target 80%');
assert(Analytics::targetProgress(50.0, null)['pct'] === null, 'null target → null pct');
echo "analytics_test OK\n";
```

- [ ] **Step 2: Run → fails** (no Analytics class).

- [ ] **Step 3: Implement `app/Core/Analytics.php`** per Interfaces. Use `DateTimeImmutable` for day math in aging. Keep pure (no DB).

- [ ] **Step 4: Wire require in `public/index.php`** (add `require __DIR__ . '/../app/Core/Analytics.php';` with the other Core requires).

- [ ] **Step 5: Extend `SalesRepository`** — add the methods above, mirroring the existing `filters()`/binding style. For invoice rows' `pay_status`/`outstanding`, compute in PHP `array_map` after fetch (like Inventory's `low`/`out`). asOf via `$this->dateBounds()['max']`.

- [ ] **Step 6: Extend `tests/unit/sales_repo_test.php`** — assert `marginSummary` keys and `profit = revenue - cogs`; `outstanding()['total_outstanding'] >= 0`; an invoice row has `pay_status` and `outstanding`.

- [ ] **Step 7: Run all tests → green. Commit + push** — `feat(analytics): growth/moving-average/aging/targets + sales margin & AR queries`.

---

### Task C: Upgrade Sales report + Dashboard

**Files:** Modify `app/Controllers/SalesController.php`, `app/Views/sales.php`, `app/Controllers/DashboardController.php`, `app/Views/dashboard.php`. (Read each before editing; mirror current structure.)

**Behavior:**
- **Sales:** controller adds `margin = $repo->marginSummary($from,$to,$search)`, `target = Analytics::targetProgress($summary['total'], (new TargetRepository())->range($from,$to))`, and passes them. View adds: a **profit KPI** (`margin['profit']`) and **margin % KPI** (`Ui::percent`), a **target progress** bar (pct, actual/target via `Ui::money`), and makes the customer cell a link: `<a href="<?= Ui::e(Ui::url('/customers/view', ['name'=>$row['customer_name']])) ?>">`. Keep existing filters/sort/pagination intact.
- **Dashboard:** controller computes: sales monthly with `Analytics::growth` + `Analytics::movingAverage`; `outstanding = $sales->outstanding(null,null)`; current-month `target = Analytics::targetProgress(currentMonthSales, TargetRepository::forPeriod(currentYm))` where currentYm = substr(dateBounds max,1,7) and currentMonthSales from monthly list; margin via `$sales->marginSummary(null,null)`. View adds/updates KPI cards: **نمو المبيعات (MoM)** using `Ui::percent(last mom)`, **المستحقات (AR)** (`outstanding['total_outstanding']` + overdue sub-metric), **الهدف مقابل الفعلي** (progress bar for current month), **هامش الربح** (`margin['margin_pct']`). Add a moving-average dataset to the sales line chart (multi-series `{label, series:{المبيعات, متوسط متحرك}}` OR overlay) and an executive-summary strip linking to `/summary`.

- [ ] **Step 1:** Read `SalesController.php` + `sales.php`; add margin/target to controller, then KPIs + progress bar + customer links to view.
- [ ] **Step 2:** HTTP smoke: start server; `/sales` 200 and contains "هامش" and a `/customers/view` link; filtered `/sales?from=…&to=…` still works. Stop server.
- [ ] **Step 3:** Read `DashboardController.php` + `dashboard.php`; add growth/MA/AR/target/margin; update KPI cards + chart + summary strip.
- [ ] **Step 4:** HTTP smoke: `/` 200, contains "المستحقات" and "الهدف"; console clean (verify in Task's Playwright pass later). Stop server.
- [ ] **Step 5:** `php tests/run_unit.php` green (no regressions). Commit + push — `feat(sales,dashboard): margin, growth, targets, AR, moving average`.

---

### Task D: Upgrade Inventory + Finance

**Files:** Modify `app/Controllers/InventoryController.php`, `app/Views/inventory.php`, `app/Controllers/FinanceController.php`, `app/Views/finance.php`.

**Behavior:**
- **Inventory:** product rows already include `potential_profit` and `stock_value`; add a **margin %** display per row (`(price-cost)/price*100` via a small inline calc or add to repo row) and a **هامش الربح المتوقع** KPI (sum potential_profit — `summary['potential_profit']`). Make product name a link `<a href="<?= Ui::e(Ui::url('/products/view', ['id'=>$row['id']])) ?>">`.
- **Finance:** controller adds `monthlyMargin = (new SalesRepository())->monthlyMargin($from,$to)`; view adds a **profit-margin trend** chart (line: profit by ym) beside/below existing income-vs-expense chart. (Keep existing type filter/pagination.)

- [ ] **Step 1:** Inventory controller/view: margin KPI + per-row margin + product links.
- [ ] **Step 2:** HTTP smoke `/inventory` 200, contains a `/products/view` link + "هامش". Stop server.
- [ ] **Step 3:** Finance controller/view: monthly margin chart.
- [ ] **Step 4:** HTTP smoke `/finance` 200, contains the new margin chart heading. Stop server.
- [ ] **Step 5:** tests green. Commit + push — `feat(inventory,finance): product margin + links + profit-margin trend`.

---

### Task E: Drill-down — customers report + customer/product/invoice detail

**Files:**
- Create: `app/Controllers/CustomersController.php` (index + view), `app/Controllers/ProductController.php` (view), `app/Controllers/InvoiceController.php` (view)
- Create views: `app/Views/customers.php`, `app/Views/customer_detail.php`, `app/Views/product_detail.php`, `app/Views/invoice_detail.php`
- Modify: `app/Models/SalesRepository.php` (customerReport, customerDetail, customerInvoices, customerMonthly, invoiceByNo, invoiceItems), `app/Models/InventoryRepository.php` (productByIdWithStats, productSalesMonthly, productInvoices)
- Modify: `public/index.php` (register routes `/customers`, `/customers/view`, `/products/view`, `/invoices/view`)
- Modify: `app/Views/layout.php` (add sidebar nav items: العملاء `/customers`, icon `users`)
- Test: `tests/unit/customers_test.php`

**Interfaces (repo):**
- `customerReport(?from,?to,?search,page,perPage,sort,dir): ['rows'=>[ ['customer_name','invoices','revenue','avg','last_purchase','outstanding'] ], 'pagination'=>…]` (GROUP BY customer_name; outstanding = Σ(total-amount_paid)). sortMap: revenue, invoices, last_purchase, customer_name.
- `customerDetail(name): ?['customer_name','invoices','revenue','avg','first_purchase','last_purchase','outstanding']` (null if none).
- `customerInvoices(name): rows` (invoice_no, invoice_date, due_date, total, amount_paid, outstanding, pay_status).
- `customerMonthly(name): [ ['ym','total'] ]`.
- `invoiceByNo(no): ?invoice row (+ due_date, amount_paid, outstanding, pay_status)`; `invoiceItems(invoiceId): [ ['name','qty','unit_price','line_total'] ]`.
- `productByIdWithStats(id): ?[ product fields + 'sold_qty','revenue','cogs','profit','margin_pct' ]`; `productSalesMonthly(id): [ ['ym','qty','revenue'] ]`; `productInvoices(id): [ ['invoice_no','invoice_date','qty','line_total'] ]`.

**View contracts:** mirror existing report pages (layout hero + report-toolbar + KPI `article`s + `.card` tables + `partials/pagination.php`). Each detail page reads its key via `Request::get('name'|'id'|'no')`; if the entity is not found, render a friendly empty state (heading + message), not a 500. Customer/product detail include a chart (monthly). Invoice detail shows header (customer, dates, pay status badge) + line-items table + totals. Add a print button (`data-print`) to each.

- [ ] **Step 1:** Write `tests/unit/customers_test.php`: `customerReport(null,null,null,1,10,'revenue','desc')` returns rows with the 6 keys, revenue desc; `customerDetail(<an existing name>)` non-null and `revenue>0`; `customerDetail('لا يوجد')` === null; `invoiceByNo(<existing>)` non-null and `invoiceItems(id)` non-empty; `productByIdWithStats(1)` has margin_pct. Run → fails.
- [ ] **Step 2:** Implement repo methods (mirror `filters()`/binding + `Report::pagination`).
- [ ] **Step 3:** Run customers_test → green.
- [ ] **Step 4:** Implement controllers + views + routes + sidebar item.
- [ ] **Step 5:** HTTP smoke: `/customers` 200 (contains a `/customers/view` link); `/customers/view?name=<existing>` 200 (contains فواتير); `/invoices/view?no=<existing>` 200 (line items); `/products/view?id=1` 200; a bad key (`?name=xx`) returns 200 with a friendly empty state (not 500). Stop server.
- [ ] **Step 6:** tests green. Commit + push — `feat(drilldown): customers report + customer/product/invoice detail pages`.

---

### Task F: AR Aging report

**Files:** Create `app/Controllers/AgingController.php`, `app/Views/aging.php`; Modify `public/index.php` (route `/aging`), `app/Views/layout.php` (sidebar item أعمار الذمم, icon `receipt`); Test `tests/unit/aging_test.php`.

**Behavior:** Controller: `$invoices = $repo->agingInvoices(); $asOf = $repo->dateBounds()['max']; $buckets = Analytics::agingBuckets($invoices, $asOf);` and passes buckets + invoice rows (each with computed `days_late`). View: KPI cards per bucket (current/1-30/31-60/61-90/90+ amounts + counts), a bar chart of bucket amounts (`data-type=bar`), and a table of outstanding invoices (customer link, due_date, days late, outstanding) sorted by days late desc. Print button.

- [ ] **Step 1:** `tests/unit/aging_test.php`: build buckets from `agingInvoices()`+asOf; assert `total` == Σ outstanding of `agingInvoices()` (within 0.01) and buckets sum to total. Run → fails (no route/view needed for unit; test Analytics+repo integration). Implement any missing repo piece.
- [ ] **Step 2:** Implement controller + view + route + sidebar.
- [ ] **Step 3:** HTTP smoke `/aging` 200, contains bucket headings; the displayed "إجمالي المستحق" equals sum of bucket cards. Stop server.
- [ ] **Step 4:** tests green. Commit + push — `feat(aging): AR aging report with buckets`.

---

### Task G: Executive summary

**Files:** Create `app/Controllers/SummaryController.php`, `app/Views/summary.php`; Modify `public/index.php` (route `/summary`), `app/Views/layout.php` (sidebar item الملخص التنفيذي, icon `trend`).

**Behavior:** Controller aggregates across domains (reuse existing repos + Analytics): total sales + growth, profit + margin, AR outstanding + overdue, target progress (current month + range), inventory value + low/out counts, top customers (customerReport top 5) + top products, income/expense net. View: a clean one-page executive layout — headline KPI grid, small sales+margin trend, aging summary strip, top customers & products lists — designed to print well (this is the primary PDF target). Print button prominent.

- [ ] **Step 1:** Implement controller (compose from existing repo methods; no new SQL ideally).
- [ ] **Step 2:** Implement view (mirror card/KPI styles). Ensure all figures come from repos/Analytics (no logic in view beyond formatting).
- [ ] **Step 3:** HTTP smoke `/summary` 200, contains sales/profit/AR/inventory KPIs and top lists. Stop server.
- [ ] **Step 4:** tests green (no new unit needed if purely compositional; if any new repo method added, cover it). Commit + push — `feat(summary): executive summary report`.

---

### Task H: Branded print / PDF

**Files:** Modify `config/app.php` (add `company_logo` SVG string, `company_tagline`, `report_footer`, `net_terms_days`), `app/Views/layout.php` (print header/footer blocks reading Config; visible only in print), `assets/css/app.css` (expand `@media print` + `@page`).

**Behavior:**
- `config/app.php` adds: `'company_logo' => '<svg …>…</svg>'` (simple inline monogram SVG), `'company_tagline' => '…'`, `'report_footer' => 'مستند داخلي — سري'`, `'net_terms_days' => 30`.
- `layout.php`: add a `<div class="print-header">` (logo + company name + tagline + the page `$title` + optional `$printMeta` like date range + generated date `date('Y-m-d H:i')`) and `<div class="print-footer">` (company + `report_footer`), both `display:none` normally.
- `app.css` `@media print`: show `.print-header/.print-footer`; hide sidebar/topbar/filters/pagination/export buttons/theme toggle; `@page { size: A4; margin: 18mm 14mm; }`; white background, dark text, bordered tables that avoid row-splitting (`tr{break-inside:avoid}`); repeat table headers (`thead{display:table-header-group}`). Keep charts visible but constrained.
- Ensure the print button (`data-print` → `window.print()`) exists on all report + detail + summary pages (add where missing).

- [ ] **Step 1:** Update `config/app.php`; verify `php -l` and that `Config::get('company_logo')` returns the SVG.
- [ ] **Step 2:** Update `layout.php` print header/footer.
- [ ] **Step 3:** Expand `app.css` print rules.
- [ ] **Step 4:** HTTP smoke: pages still 200 and render normally (print blocks hidden on screen — grep that `.print-header` is present in HTML). Stop server.
- [ ] **Step 5:** tests green. Commit + push — `feat(print): branded print/PDF header, footer, @page layout`.

---

### Task I: Full verification (unit + Playwright) + docs

**Files:** Modify `docs/PROJECT_REPORT.md` (or add `docs/UPGRADE_REPORT.md`) with new features, screenshots, verification results; Create screenshots under `tests/screenshots/`.

- [ ] **Step 1:** `php tests/run_unit.php` → all suites green; paste output.
- [ ] **Step 2:** Playwright pass (driven by controller): for `/`, `/sales`, `/inventory`, `/finance`, `/customers`, `/customers/view?name=<existing>`, `/products/view?id=1`, `/invoices/view?no=<existing>`, `/aging`, `/summary`: navigate (tall fixed viewport, wait for charts), assert key Arabic headings/KPIs, verify drill-down links resolve, verify aging buckets sum equals displayed outstanding, and screenshot each. Console must be error-free.
- [ ] **Step 3:** Write the upgrade report (features, screenshots, unit + E2E results, commit list). Commit + push.

---

## Self-Review Notes
- **Spec coverage:** analytics+targets (A,B,C), drill-down (E), aging (F), executive summary (G), branded print (H), page upgrades (C,D), verification+docs (I). All spec sections mapped.
- **Contracts:** Repo method signatures and Analytics signatures are defined once (Tasks A/B/E) and consumed by later tasks; view data-contracts reference exact keys. asOf date sourced consistently from `dateBounds()['max']`.
- **Pattern adherence:** every new repo/controller/view instructed to mirror the existing v2 equivalents (named-param SQL, `Report::pagination`, `Ui`/`Config` helpers, `partials/pagination.php`).
- **Determinism:** seed changes use the existing LCG; targets derived from seeded actuals.
