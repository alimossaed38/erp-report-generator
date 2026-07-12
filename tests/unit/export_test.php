<?php
require __DIR__ . '/../../app/Core/Database.php';
require __DIR__ . '/../../app/Core/Controller.php';
require __DIR__ . '/../../app/Core/Request.php';
require __DIR__ . '/../../app/Models/SalesRepository.php';
require __DIR__ . '/../../app/Models/InventoryRepository.php';
require __DIR__ . '/../../app/Models/FinanceRepository.php';
require __DIR__ . '/../../app/Controllers/ExportController.php';

$_GET = ['report' => 'sales', 'format' => 'csv'];
ob_start();
(new ExportController())->index();
$out = ob_get_clean();
assert(str_contains($out, 'invoice_no') || str_contains($out, 'رقم الفاتورة'), 'csv has header');
assert(substr_count($out, "\n") > 5, 'csv has multiple rows');

$_GET = ['report' => 'inventory', 'format' => 'xls'];
ob_start();
(new ExportController())->index();
$out = ob_get_clean();
assert(str_contains($out, '<table'), 'xls is html table');

echo "export_test OK\n";
