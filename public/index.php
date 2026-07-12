<?php
declare(strict_types=1);

// Serve static assets that live outside public/ (css, js) for the built-in server.
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if (preg_match('#^/assets/(css|js)/([A-Za-z0-9_.-]+)$#', $uriPath, $m)) {
    $file = __DIR__ . '/../assets/' . $m[1] . '/' . $m[2];
    if (is_file($file)) {
        $types = ['css' => 'text/css', 'js' => 'application/javascript'];
        header('Content-Type: ' . $types[$m[1]] . '; charset=utf-8');
        readfile($file);
        return;
    }
    http_response_code(404); return;
}

spl_autoload_register(function (string $class) {
    foreach (['Core', 'Controllers', 'Models'] as $dir) {
        $file = __DIR__ . '/../app/' . $dir . '/' . $class . '.php';
        if (is_file($file)) { require $file; return; }
    }
});

require __DIR__ . '/../app/Core/Router.php';
require __DIR__ . '/../app/Core/Controller.php';
require __DIR__ . '/../app/Core/Database.php';
require __DIR__ . '/../app/Core/Request.php';

$router = new Router();
$router->add('/', [DashboardController::class, 'index']);
$router->add('/sales', [SalesController::class, 'index']);
$router->add('/inventory', [InventoryController::class, 'index']);
$router->add('/finance', [FinanceController::class, 'index']);
$router->add('/export', [ExportController::class, 'index']);

$router->dispatch($_SERVER['REQUEST_URI'] ?? '/');
