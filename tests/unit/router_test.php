<?php
require __DIR__ . '/../../app/Core/Router.php';

$r = new Router();
$hit = null;
$r->add('/ping', function () use (&$hit) { $hit = 'pong'; });
$r->dispatch('/ping');
assert($hit === 'pong', 'route /ping should invoke handler');

$r->add('/none', function () use (&$hit) { $hit = 'x'; });
$r->dispatch('/does-not-exist');
assert($hit === 'pong', 'unknown route should not invoke handlers (404)');

echo "router_test OK\n";
