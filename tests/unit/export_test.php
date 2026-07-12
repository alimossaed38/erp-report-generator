<?php
require __DIR__ . '/../bootstrap.php';

$_GET = ['report' => 'sales', 'format' => 'csv'];
ob_start();
(new ExportController())->index();
$out = ob_get_clean();
assert(str_contains($out, 'رقم الفاتورة'), 'csv has Arabic header');
assert(substr_count($out, "\n") > 5, 'csv has multiple rows');

$_GET = ['report' => 'inventory', 'format' => 'xls'];
ob_start();
(new ExportController())->index();
$out = ob_get_clean();
assert(str_contains($out, '<table'), 'xls is an HTML table');
assert(str_contains($out, 'تقرير المخزون'), 'xls has title');

// JSON export was removed; an unknown format must fall back to CSV (not JSON).
$_GET = ['report' => 'finance', 'format' => 'json'];
ob_start();
(new ExportController())->index();
$out = ob_get_clean();
assert(json_decode($out, true) === null, 'json export must be disabled (no JSON payload)');
assert(str_contains($out, 'التاريخ'), 'unknown format falls back to CSV with Arabic header');

echo "export_test OK\n";
