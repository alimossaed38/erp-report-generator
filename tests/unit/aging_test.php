<?php
require __DIR__ . '/../bootstrap.php';

$repo = new SalesRepository();
$inv = $repo->agingInvoices();
$asOf = $repo->dateBounds()['max'];

assert($asOf !== null, 'dateBounds max present');
assert(count($inv) > 0, 'agingInvoices has outstanding rows');
foreach (['invoice_no', 'customer_name', 'invoice_date', 'due_date', 'total', 'amount_paid', 'outstanding'] as $key) {
    assert(array_key_exists($key, $inv[0]), "agingInvoices row has $key");
}
foreach ($inv as $row) {
    assert($row['outstanding'] > 0, 'agingInvoices only includes outstanding > 0');
}

$b = Analytics::agingBuckets($inv, $asOf);

$expectedTotal = array_sum(array_column($inv, 'outstanding'));
assert(abs($b['total'] - $expectedTotal) < 0.01, 'bucket total matches sum of outstanding');

$bucketSum = $b['current'] + $b['d1_30'] + $b['d31_60'] + $b['d61_90'] + $b['d90_plus'];
assert(abs($bucketSum - $b['total']) < 0.01, 'buckets sum to total');

$countSum = array_sum($b['counts']);
assert($countSum === count($inv), 'bucket counts sum to invoice count');

echo "aging_test OK\n";
