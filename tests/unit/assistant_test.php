<?php
require __DIR__ . '/../bootstrap.php';

$c = new AssistantController();
$ref = (new SalesRepository())->dateBounds()['max'];
assert($ref !== null, 'seed has a max sales date');

// Helper: every successful response has the same shape.
$assertOk = static function (array $r, string $label): void {
    assert(($r['ok'] ?? false) === true, "$label: ok=true");
    assert(is_string($r['understood']) && $r['understood'] !== '', "$label: understood text");
    assert(is_array($r['kpis']) && count($r['kpis']) > 0, "$label: has kpis");
    assert(isset($r['table']['columns'], $r['table']['rows']), "$label: table shape");
    foreach ($r['table']['rows'] as $row) {
        assert(count($row) === count($r['table']['columns']), "$label: row width matches columns");
    }
};

// --- Sales ---
$sales = $c->build('مبيعات آخر 90 يوم', $ref);
$assertOk($sales, 'sales');
assert(mb_strpos($sales['understood'], 'المبيعات') !== false, 'sales: understood names report');
assert(str_starts_with($sales['reportUrl'], '/sales'), 'sales: reportUrl');
assert(str_starts_with((string) $sales['exportUrl'], '/export?report=sales'), 'sales: exportUrl');

// Top products variant.
$top = $c->build('أعلى 5 منتجات مبيعًا', $ref);
$assertOk($top, 'top products');
assert(count($top['table']['rows']) <= 5, 'top products: respects topN');

// --- Customers (not exportable) ---
$cust = $c->build('أكثر 5 عملاء إنفاقًا', $ref);
$assertOk($cust, 'customers');
assert(str_starts_with($cust['reportUrl'], '/customers'), 'customers: reportUrl');
assert($cust['exportUrl'] === null, 'customers: no export');

// --- Aging (not exportable) ---
$aging = $c->build('الذمم المتأخرة', $ref);
$assertOk($aging, 'aging');
assert(str_starts_with($aging['reportUrl'], '/aging'), 'aging: reportUrl');
assert($aging['exportUrl'] === null, 'aging: no export');

// --- Inventory ---
$inv = $c->build('الأصناف النافدة', $ref);
$assertOk($inv, 'inventory');
assert(str_starts_with($inv['reportUrl'], '/inventory'), 'inventory: reportUrl');
assert(str_starts_with((string) $inv['exportUrl'], '/export?report=inventory'), 'inventory: exportUrl');

// --- Finance ---
$fin = $c->build('مصروفات هذا الشهر', $ref);
$assertOk($fin, 'finance');
assert(str_starts_with($fin['reportUrl'], '/finance'), 'finance: reportUrl');
assert(str_starts_with((string) $fin['exportUrl'], '/export?report=finance'), 'finance: exportUrl');

// --- Unrecognised request ---
$bad = $c->build('طقس اليوم', $ref);
assert(($bad['ok'] ?? true) === false, 'unknown: ok=false');
assert(is_array($bad['suggestions']) && count($bad['suggestions']) > 0, 'unknown: suggestions offered');

echo "assistant_test OK\n";
