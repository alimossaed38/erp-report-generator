<?php
require __DIR__ . '/../bootstrap.php';

$repo = new FinanceRepository();
$s = $repo->summary(null, null);
assert($s['income'] > 0, 'income > 0');
assert($s['expense'] > 0, 'expense > 0');
assert(abs($s['net'] - ($s['income'] - $s['expense'])) < 0.01, 'net = income - expense');
assert(isset($s['count'], $s['margin']), 'summary extras exist');

$m = $repo->monthly(null, null);
assert(count($m) > 0, 'monthly not empty');
assert(isset($m[0]['ym'], $m[0]['series']['income'], $m[0]['series']['expense']), 'monthly row shape');

$page = $repo->transactionPage(null, null, 'expense', null, null, 1, 10);
assert(count($page['rows']) > 0 && count($page['rows']) <= 10, 'transactions page limit');
assert($page['pagination']['total'] >= count($page['rows']), 'pagination total');
foreach ($page['rows'] as $row) assert($row['type'] === 'expense', 'type filter works');

echo "finance_repo_test OK\n";
