<?php
require __DIR__ . '/../bootstrap.php';

$repo = new InventoryRepository();
$s = $repo->summary(null);
assert($s['items'] > 0, 'items > 0');
assert($s['value'] > 0, 'stock value > 0');
assert($s['units'] >= 0 && $s['potential_profit'] >= 0, 'extended metrics');

$page = $repo->productPage(null, null, 'low', 1, 10);
assert(count($page['rows']) <= 10, 'products page limit');
foreach ($page['rows'] as $row) assert($row['low'] === true, 'low filter works');

$byCat = $repo->valueByCategory();
assert(count($byCat) > 0, 'value by category not empty');
assert(isset($byCat[0]['category'], $byCat[0]['value'], $byCat[0]['units']), 'row shape');

$cats = $repo->categories();
assert(count($cats) > 0, 'categories not empty');

echo "inventory_repo_test OK\n";
