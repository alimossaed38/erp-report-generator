<?php
require __DIR__ . '/../bootstrap.php';

// Intent is a pure parser: Arabic text + reference date -> structured intent.
// The reference date stands in for the latest date in the data (max sales date).
$ref = '2026-07-13';

// --- Report type detection ---
assert(Intent::parse('مبيعات آخر 90 يوم', $ref)['report'] === 'sales', 'sales keyword');
assert(Intent::parse('الذمم المتأخرة', $ref)['report'] === 'aging', 'aging keyword');
assert(Intent::parse('الأصناف النافدة', $ref)['report'] === 'inventory', 'inventory keyword');
assert(Intent::parse('مصروفات هذا الشهر', $ref)['report'] === 'finance', 'finance keyword');
assert(Intent::parse('أكثر 10 عملاء إنفاقًا', $ref)['report'] === 'customers', 'customers keyword');
assert(Intent::parse('طقس اليوم', $ref)['report'] === null, 'unknown -> null report');

// "منتجات" + "مبيعًا" must resolve to sales (top products), not inventory.
$topProd = Intent::parse('أعلى 5 منتجات مبيعًا', $ref);
assert($topProd['report'] === 'sales', 'top products -> sales');
assert($topProd['topN'] === 5, 'topN parsed from digits');

// "أعلى/أكثر" without a number defaults topN to 10.
assert(Intent::parse('أعلى المنتجات مبيعًا', $ref)['topN'] === 10, 'topN defaults to 10');

// --- Arabic-Indic digits ---
$arabicDigits = Intent::parse('مبيعات آخر ٧ أيام', $ref);
assert($arabicDigits['report'] === 'sales', 'arabic digits report');
assert($arabicDigits['from'] === '2026-07-07', 'arabic digits: from = ref - 6 days');
assert($arabicDigits['to'] === '2026-07-13', 'arabic digits: to = ref');

// --- Relative date ranges ---
$months = Intent::parse('مبيعات آخر 3 أشهر', $ref);
assert($months['from'] === '2026-04-14', 'last 3 months from');
assert($months['to'] === '2026-07-13', 'last 3 months to');

$thisMonth = Intent::parse('مصروفات هذا الشهر', $ref);
assert($thisMonth['from'] === '2026-07-01', 'this month from = first of month');
assert($thisMonth['to'] === '2026-07-13', 'this month to = ref');

$lastMonth = Intent::parse('مبيعات الشهر الماضي', $ref);
assert($lastMonth['from'] === '2026-06-01', 'last month from');
assert($lastMonth['to'] === '2026-06-30', 'last month to = end of prev month');

$lastYear = Intent::parse('صافي التدفق آخر سنة', $ref);
assert($lastYear['report'] === 'finance', 'net cash -> finance');
assert($lastYear['from'] === '2025-07-14', 'last year from');
assert($lastYear['to'] === '2026-07-13', 'last year to');

// --- Absolute year and month+year ---
$year = Intent::parse('إيرادات 2026', $ref);
assert($year['report'] === 'finance', 'revenues -> finance');
assert($year['type'] === 'income', 'income type');
assert($year['from'] === '2026-01-01' && $year['to'] === '2026-12-31', 'explicit year full range');

$monthYear = Intent::parse('مبيعات يناير ٢٠٢٦', $ref);
assert($monthYear['from'] === '2026-01-01' && $monthYear['to'] === '2026-01-31', 'month name + year');

// --- Filters ---
assert(Intent::parse('الأصناف النافدة', $ref)['status'] === 'out', 'status out');
assert(Intent::parse('الأصناف تحت حد الطلب', $ref)['status'] === 'low', 'status low');
assert(Intent::parse('مصروفات هذا الشهر', $ref)['type'] === 'expense', 'type expense');

// --- Customer name extraction (kept in original spelling for LIKE match) ---
$cust = Intent::parse('فواتير عميل مؤسسة النور', $ref);
assert($cust['report'] === 'sales', 'invoices for customer -> sales');
assert($cust['search'] === 'مؤسسة النور', 'customer name extracted verbatim');

$custSummary = Intent::parse('ملخص عميل مؤسسة النور', $ref);
assert($custSummary['report'] === 'customers', 'customer summary -> customers');
assert($custSummary['search'] === 'مؤسسة النور', 'customer name on customers report');

// A plural "عملاء" request must NOT capture a bogus search term.
assert(Intent::parse('أكثر 10 عملاء إنفاقًا', $ref)['search'] === null, 'no false search for plural');

// --- Export intent ---
$export = Intent::parse('صدّر مبيعات آخر 7 أيام إكسل', $ref);
assert($export['export'] === true, 'export flagged');
assert($export['format'] === 'xls', 'excel -> xls format');
assert($export['from'] === '2026-07-07', 'export keeps date range');

$noExport = Intent::parse('مبيعات آخر 7 أيام', $ref);
assert($noExport['export'] === false, 'no export by default');

// --- Defaults: no period => null dates (all data) ---
$noDate = Intent::parse('قيمة المخزون', $ref);
assert($noDate['report'] === 'inventory', 'inventory value report');
assert($noDate['from'] === null && $noDate['to'] === null, 'no period -> null dates');

echo "intent_test OK\n";
