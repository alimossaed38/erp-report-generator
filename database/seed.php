<?php
require __DIR__ . '/../app/Core/Database.php';

$dbPath = __DIR__ . '/erp.sqlite';
if (is_file($dbPath)) { unlink($dbPath); }

$db = Database::connection();
$db->exec(file_get_contents(__DIR__ . '/schema.sql'));

// Deterministic pseudo-random (LCG) — stable across runs.
$state = 12345;
$rand = function (int $min, int $max) use (&$state): int {
    $state = ($state * 1103515245 + 12345) & 0x7fffffff;
    return $min + ($state % ($max - $min + 1));
};

$categories = ['إلكترونيات', 'أثاث مكتبي', 'قرطاسية', 'أجهزة', 'ملحقات'];
$productNames = [
    'حاسوب محمول', 'شاشة عرض', 'لوحة مفاتيح', 'فأرة', 'طابعة', 'ماسح ضوئي',
    'كرسي مكتب', 'مكتب خشبي', 'خزانة ملفات', 'رف كتب', 'دفتر ملاحظات', 'أقلام حبر',
    'ورق طباعة', 'حبر طابعة', 'كاميرا ويب', 'سماعة رأس', 'ميكروفون', 'موزع USB',
    'قرص صلب', 'ذاكرة تخزين', 'كابل شبكة', 'راوتر', 'جهاز عرض', 'سبورة',
    'آلة حاسبة', 'مكبر صوت', 'شاحن', 'حامل هاتف', 'مقص', 'دباسة',
];

// products
$pStmt = $db->prepare('INSERT INTO products (name, category, price, cost, stock_qty, reorder_level) VALUES (?,?,?,?,?,?)');
$productIds = [];
foreach ($productNames as $i => $name) {
    $cost = $rand(50, 3000);
    $price = (int) round($cost * (1.2 + $rand(0, 40) / 100)); // 20%–60% markup
    $stock = $rand(0, 120);
    $reorder = $rand(10, 30);
    $pStmt->execute([$name, $categories[$i % count($categories)], $price, $cost, $stock, $reorder]);
    $productIds[] = (int) $db->lastInsertId();
}

// sales invoices + items across 12 months (2025-07 .. 2026-06)
$customers = ['مؤسسة النور', 'شركة الأفق', 'مكتب الرياض', 'مجموعة الخليج', 'مؤسسة البناء',
    'شركة المستقبل', 'متجر السلام', 'مؤسسة الرواد', 'شركة الإبداع', 'مكتب الإنجاز'];
$invStmt = $db->prepare('INSERT INTO sales_invoices (invoice_no, customer_name, invoice_date, total) VALUES (?,?,?,?)');
$itemStmt = $db->prepare('INSERT INTO sales_items (invoice_id, product_id, qty, unit_price, line_total) VALUES (?,?,?,?,?)');

$prices = [];
foreach ($db->query('SELECT id, price FROM products') as $row) { $prices[(int)$row['id']] = (float)$row['price']; }

$invoiceNo = 1000;
for ($m = 0; $m < 12; $m++) {
    $year = 2025 + intdiv(6 + $m, 12);
    $month = ((6 + $m) % 12) + 1; // 7..12 then 1..6
    $count = $rand(12, 22); // invoices per month
    for ($k = 0; $k < $count; $k++) {
        $day = str_pad((string) $rand(1, 28), 2, '0', STR_PAD_LEFT);
        $mm = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        $date = "$year-$mm-$day";
        $invStmt->execute(["INV-" . (++$invoiceNo), $customers[$rand(0, count($customers)-1)], $date, 0]);
        $invId = (int) $db->lastInsertId();
        $lines = $rand(1, 5);
        $total = 0.0;
        for ($l = 0; $l < $lines; $l++) {
            $pid = $productIds[$rand(0, count($productIds)-1)];
            $qty = $rand(1, 8);
            $unit = $prices[$pid];
            $lineTotal = $qty * $unit;
            $itemStmt->execute([$invId, $pid, $qty, $unit, $lineTotal]);
            $total += $lineTotal;
        }
        $db->prepare('UPDATE sales_invoices SET total = ? WHERE id = ?')->execute([$total, $invId]);
    }
}

// transactions: income (from sales-ish) + expenses across same 12 months
$expenseCats = ['رواتب', 'إيجار', 'كهرباء', 'تسويق', 'صيانة', 'مشتريات'];
$txnStmt = $db->prepare('INSERT INTO transactions (type, category, amount, txn_date, description) VALUES (?,?,?,?,?)');
for ($m = 0; $m < 12; $m++) {
    $year = 2025 + intdiv(6 + $m, 12);
    $month = ((6 + $m) % 12) + 1;
    $mm = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
    // monthly income lump(s)
    for ($i = 0; $i < $rand(3, 6); $i++) {
        $day = str_pad((string) $rand(1, 28), 2, '0', STR_PAD_LEFT);
        $txnStmt->execute(['income', 'مبيعات', $rand(5000, 40000), "$year-$mm-$day", 'إيراد مبيعات']);
    }
    // monthly expenses
    for ($i = 0; $i < $rand(4, 8); $i++) {
        $day = str_pad((string) $rand(1, 28), 2, '0', STR_PAD_LEFT);
        $cat = $expenseCats[$rand(0, count($expenseCats)-1)];
        $txnStmt->execute(['expense', $cat, $rand(1000, 25000), "$year-$mm-$day", 'مصروف ' . $cat]);
    }
}

echo "Seed complete.\n";
