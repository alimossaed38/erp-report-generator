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

echo "sales_repo_test OK\n";
