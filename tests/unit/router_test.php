<?php
require __DIR__ . '/../bootstrap.php';

$r = new Router();
$hit = null;
$r->add('/ping', function () use (&$hit) { $hit = 'pong'; });
$r->dispatch('/ping');
assert($hit === 'pong', 'route /ping should invoke handler');

ob_start();
$r->dispatch('/does-not-exist');
$out = ob_get_clean();
assert(str_contains($out, '404'), 'unknown route renders 404');

echo "router_test OK\n";
