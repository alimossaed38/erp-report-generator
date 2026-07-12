<?php
require __DIR__ . '/../../app/Core/Database.php';

$db = Database::connection();
$counts = [
    'products' => (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'sales_invoices' => (int) $db->query('SELECT COUNT(*) FROM sales_invoices')->fetchColumn(),
    'sales_items' => (int) $db->query('SELECT COUNT(*) FROM sales_items')->fetchColumn(),
    'transactions' => (int) $db->query('SELECT COUNT(*) FROM transactions')->fetchColumn(),
];
assert($counts['products'] >= 25, 'expected >=25 products, got ' . $counts['products']);
assert($counts['sales_invoices'] >= 150, 'expected >=150 invoices, got ' . $counts['sales_invoices']);
assert($counts['sales_items'] >= 150, 'expected >=150 sale items');
assert($counts['transactions'] >= 100, 'expected >=100 transactions');

// invoice total equals sum of its items (data integrity)
$bad = (int) $db->query(
    'SELECT COUNT(*) FROM sales_invoices i
     WHERE ROUND(i.total,2) <> ROUND(
       (SELECT COALESCE(SUM(line_total),0) FROM sales_items WHERE invoice_id = i.id), 2)'
)->fetchColumn();
assert($bad === 0, "invoice totals must equal sum of items; mismatches: $bad");

echo "seed_test OK\n";
