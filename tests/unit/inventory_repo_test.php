<?php
require __DIR__ . '/../../app/Core/Database.php';
require __DIR__ . '/../../app/Models/InventoryRepository.php';

$repo = new InventoryRepository();
$s = $repo->summary(null);
assert($s['items'] > 0, 'items > 0');
assert($s['value'] > 0, 'stock value > 0');
assert($s['low'] >= 0, 'low count >= 0');

$prods = $repo->products(null);
assert(count($prods) === $s['items'], 'products count matches summary items');
assert(array_key_exists('low', $prods[0]), 'product row has low flag');

$byCat = $repo->valueByCategory();
assert(count($byCat) > 0, 'value by category not empty');
assert(isset($byCat[0]['category'], $byCat[0]['value']), 'row shape');

$cats = $repo->categories();
assert(count($cats) > 0, 'categories not empty');

echo "inventory_repo_test OK\n";
