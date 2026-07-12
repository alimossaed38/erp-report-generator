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
