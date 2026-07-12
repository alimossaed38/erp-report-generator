<?php
require __DIR__ . '/../../app/Core/Database.php';
require __DIR__ . '/../../app/Models/FinanceRepository.php';

$repo = new FinanceRepository();
$s = $repo->summary(null, null);
assert($s['income'] > 0, 'income > 0');
assert($s['expense'] > 0, 'expense > 0');
assert(abs($s['net'] - ($s['income'] - $s['expense'])) < 0.01, 'net = income - expense');

$m = $repo->monthly(null, null);
assert(count($m) > 0, 'monthly not empty');
assert(isset($m[0]['ym'], $m[0]['series']['income'], $m[0]['series']['expense']), 'monthly row shape');

$t = $repo->transactions(null, null, 10);
assert(count($t) > 0 && count($t) <= 10, 'transactions limit');

echo "finance_repo_test OK\n";
