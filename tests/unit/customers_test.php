<?php
require __DIR__ . '/../bootstrap.php';

$repo = new SalesRepository();

$page = $repo->customerReport(null, null, null, 1, 10, 'revenue', 'desc');
assert(isset($page['rows'], $page['pagination']), 'customerReport shape');
assert(count($page['rows']) <= 10, 'customerReport respects per page');
assert(count($page['rows']) > 0, 'customerReport has rows');
$first = $page['rows'][0];
foreach (['customer_name', 'invoices', 'revenue', 'avg', 'last_purchase', 'outstanding'] as $key) {
    assert(array_key_exists($key, $first), "customerReport row has $key");
}
if (count($page['rows']) > 1) {
    assert($page['rows'][0]['revenue'] >= $page['rows'][1]['revenue'], 'customerReport sorted revenue desc');
}

$sampleCustomer = $first['customer_name'];

$detail = $repo->customerDetail($sampleCustomer);
assert($detail !== null, 'customerDetail existing customer non-null');
assert($detail['revenue'] > 0, 'customerDetail revenue > 0');
foreach (['customer_name', 'invoices', 'revenue', 'avg', 'first_purchase', 'last_purchase', 'outstanding'] as $key) {
    assert(array_key_exists($key, $detail), "customerDetail row has $key");
}

$missing = $repo->customerDetail('لا يوجد');
assert($missing === null, 'customerDetail unknown customer is null');

$customerInvoices = $repo->customerInvoices($sampleCustomer);
assert(count($customerInvoices) > 0, 'customerInvoices not empty');
assert(isset($customerInvoices[0]['invoice_no'], $customerInvoices[0]['pay_status']), 'customerInvoices row shape');

$customerMonthly = $repo->customerMonthly($sampleCustomer);
assert(count($customerMonthly) > 0, 'customerMonthly not empty');
assert(isset($customerMonthly[0]['ym'], $customerMonthly[0]['total']), 'customerMonthly row shape');

$sampleInvoiceNo = $repo->invoicePage(null, null, null, 1, 1)['rows'][0]['invoice_no'];
$invoice = $repo->invoiceByNo($sampleInvoiceNo);
assert($invoice !== null, 'invoiceByNo existing invoice non-null');
assert(isset($invoice['due_date'], $invoice['amount_paid'], $invoice['outstanding'], $invoice['pay_status']), 'invoiceByNo has payment fields');

$missingInvoice = $repo->invoiceByNo('NOPE-0000');
assert($missingInvoice === null, 'invoiceByNo unknown invoice is null');

$items = $repo->invoiceItems((int) $invoice['id']);
assert(count($items) > 0, 'invoiceItems not empty');
assert(isset($items[0]['name'], $items[0]['qty'], $items[0]['unit_price'], $items[0]['line_total']), 'invoiceItems row shape');

$inventoryRepo = new InventoryRepository();
$product = $inventoryRepo->productByIdWithStats(1);
assert($product !== null, 'productByIdWithStats existing product non-null');
assert(array_key_exists('margin_pct', $product), 'productByIdWithStats has margin_pct');
foreach (['sold_qty', 'revenue', 'cogs', 'profit'] as $key) {
    assert(array_key_exists($key, $product), "productByIdWithStats has $key");
}

$missingProduct = $inventoryRepo->productByIdWithStats(999999);
assert($missingProduct === null, 'productByIdWithStats unknown id is null');

$productMonthly = $inventoryRepo->productSalesMonthly(1);
assert(is_array($productMonthly), 'productSalesMonthly returns array');
if (count($productMonthly) > 0) {
    assert(isset($productMonthly[0]['ym'], $productMonthly[0]['qty'], $productMonthly[0]['revenue']), 'productSalesMonthly row shape');
}

$productInvoices = $inventoryRepo->productInvoices(1);
assert(is_array($productInvoices), 'productInvoices returns array');
if (count($productInvoices) > 0) {
    assert(isset($productInvoices[0]['invoice_no'], $productInvoices[0]['invoice_date'], $productInvoices[0]['qty'], $productInvoices[0]['line_total']), 'productInvoices row shape');
}

echo "customers_test OK\n";
