<?php
require __DIR__ . '/../../app/Core/Database.php';
require __DIR__ . '/../../app/Models/SalesRepository.php';

$repo = new SalesRepository();
$s = $repo->summary(null, null);
assert($s['count'] > 0, 'summary count > 0');
assert($s['total'] > 0, 'summary total > 0');
assert(abs($s['avg'] - ($s['total'] / $s['count'])) < 0.01, 'avg = total/count');

$monthly = $repo->monthly(null, null);
assert(count($monthly) > 0, 'monthly not empty');
assert(isset($monthly[0]['ym'], $monthly[0]['total']), 'monthly row shape');

$top = $repo->topProducts(null, null, 5);
assert(count($top) > 0 && count($top) <= 5, 'topProducts respects limit');
assert(isset($top[0]['name'], $top[0]['qty'], $top[0]['revenue']), 'top row shape');

$inv = $repo->invoices(null, null, 10);
assert(count($inv) > 0 && count($inv) <= 10, 'invoices respects limit');

// date filter narrows results
$all = $repo->summary(null, null)['count'];
$narrow = $repo->summary('2026-01-01', '2026-06-30')['count'];
assert($narrow <= $all, 'filtered count <= all');

echo "sales_repo_test OK\n";
