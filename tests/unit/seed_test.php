<?php
require __DIR__ . '/../bootstrap.php';

$db = Database::connection();
$counts = [
    'products' => (int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'sales_invoices' => (int) $db->query('SELECT COUNT(*) FROM sales_invoices')->fetchColumn(),
    'sales_items' => (int) $db->query('SELECT COUNT(*) FROM sales_items')->fetchColumn(),
    'transactions' => (int) $db->query('SELECT COUNT(*) FROM transactions')->fetchColumn(),
];
assert($counts['products'] >= 25, 'expected >=25 products');
assert($counts['sales_invoices'] >= 150, 'expected >=150 invoices');
assert($counts['sales_items'] >= 150, 'expected >=150 sale items');
assert($counts['transactions'] >= 100, 'expected >=100 transactions');

$bad = (int) $db->query(
    'SELECT COUNT(*) FROM sales_invoices i
     WHERE ROUND(i.total,2) <> ROUND(
       (SELECT COALESCE(SUM(line_total),0) FROM sales_items WHERE invoice_id = i.id), 2)'
)->fetchColumn();
assert($bad === 0, 'invoice totals must equal sum of items');

$cols = array_column($db->query("PRAGMA table_info(sales_invoices)")->fetchAll(), 'name');
assert(in_array('due_date', $cols, true) && in_array('amount_paid', $cols, true), 'invoice payment columns exist');
$bad = (int) $db->query("SELECT COUNT(*) FROM sales_invoices WHERE amount_paid < 0 OR amount_paid > total + 0.01")->fetchColumn();
assert($bad === 0, 'amount_paid within [0,total]');
$targets = (int) $db->query("SELECT COUNT(*) FROM targets")->fetchColumn();
assert($targets >= 12, 'targets seeded');
$unpaid = (int) $db->query("SELECT COUNT(*) FROM sales_invoices WHERE amount_paid < total")->fetchColumn();
assert($unpaid > 0, 'some invoices have outstanding balance');

echo "seed_test OK\n";
