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
