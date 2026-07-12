<?php
require __DIR__ . '/../bootstrap.php';

$repo = new SalesRepository();
$s = $repo->summary(null, null);
assert($s['count'] > 0, 'summary count > 0');
assert($s['total'] > 0, 'summary total > 0');
assert(abs($s['avg'] - ($s['total'] / $s['count'])) < 0.01, 'avg = total/count');
assert($s['customers'] > 0, 'distinct customers');

$monthly = $repo->monthly(null, null);
assert(count($monthly) > 0, 'monthly not empty');
assert(isset($monthly[0]['ym'], $monthly[0]['total'], $monthly[0]['invoices']), 'monthly row shape');

$top = $repo->topProducts(null, null, 5);
assert(count($top) > 0 && count($top) <= 5, 'topProducts respects limit');
assert(isset($top[0]['name'], $top[0]['qty'], $top[0]['revenue'], $top[0]['category']), 'top row shape');

$page = $repo->invoicePage('2026-01-01', '2026-06-30', 'مؤسسة', 1, 10, 'total', 'desc');
assert(count($page['rows']) <= 10, 'invoice page limit');
assert($page['pagination']['total'] >= count($page['rows']), 'invoice pagination total');

$allPage = $repo->invoicePage(null, null, null, 1, 10, 'total', 'desc');
assert(count($allPage['rows']) > 0, 'invoice page has rows');
$firstRow = $allPage['rows'][0];
assert(isset($firstRow['pay_status'], $firstRow['outstanding'], $firstRow['due_date'], $firstRow['amount_paid']), 'invoice row has payment fields');
assert(in_array($firstRow['pay_status'], ['paid', 'partial', 'unpaid'], true), 'pay_status valid value');

$margin = $repo->marginSummary(null, null);
assert(isset($margin['revenue'], $margin['cogs'], $margin['profit'], $margin['margin_pct']), 'marginSummary keys');
assert(abs($margin['profit'] - ($margin['revenue'] - $margin['cogs'])) < 0.01, 'profit = revenue - cogs');

$monthlyMargin = $repo->monthlyMargin(null, null);
assert(count($monthlyMargin) > 0, 'monthlyMargin not empty');
assert(isset($monthlyMargin[0]['ym'], $monthlyMargin[0]['revenue'], $monthlyMargin[0]['cogs'], $monthlyMargin[0]['profit']), 'monthlyMargin row shape');

$outstanding = $repo->outstanding(null, null);
assert($outstanding['total_outstanding'] >= 0, 'outstanding total >= 0');
assert($outstanding['overdue'] >= 0, 'outstanding overdue >= 0');
assert($outstanding['invoice_count'] >= 0, 'outstanding invoice_count >= 0');

$aging = $repo->agingInvoices();
foreach ($aging as $agingRow) {
    assert($agingRow['outstanding'] > 0, 'agingInvoices only has outstanding > 0 rows');
    assert(isset($agingRow['invoice_no'], $agingRow['customer_name'], $agingRow['invoice_date'], $agingRow['due_date'], $agingRow['total'], $agingRow['amount_paid']), 'agingInvoices row shape');
}

echo "sales_repo_test OK\n";
