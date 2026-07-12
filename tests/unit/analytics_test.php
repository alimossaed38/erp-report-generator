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
// YoY: full 14-month window (2024-01 .. 2025-02); 2025-02 aligns to 2024-02.
$year = [];
for ($i = 0; $i < 14; $i++) { $mm = str_pad((string)($i % 12 + 1), 2, '0', STR_PAD_LEFT); $yy = 2024 + intdiv($i, 12); $year[] = ['ym' => "$yy-$mm", 'v' => 100 + $i]; }
$gy = Analytics::growth($year, 'v'); // index 13 = 2025-02 (v=113) vs 2024-02 (v=101)
assert($gy[13]['ym'] === '2025-02' && $gy[13]['yoy'] !== null && abs($gy[13]['yoy'] - (12/101*100)) < 0.01, 'YoY 2025-02 vs 2024-02');
// Data gap: remove 2024-02. Now 2025-02 has NO exact 12-month-prior row → yoy must be null (not a wrong-month fallback to index-12=2024-01).
$gap = $year; unset($gap[1]); $gap = array_values($gap);
$ggap = Analytics::growth($gap, 'v');
$row2025_02 = null; foreach ($ggap as $r) { if ($r['ym'] === '2025-02') { $row2025_02 = $r; } }
assert($row2025_02 !== null && $row2025_02['yoy'] === null, 'YoY null when exact 12-months-prior month is missing');
$tp = Analytics::targetProgress(80.0, 100.0);
assert(abs($tp['pct'] - 80.0) < 0.01 && $tp['met'] === false, 'target 80%');
assert(Analytics::targetProgress(50.0, null)['pct'] === null, 'null target → null pct');
echo "analytics_test OK\n";
