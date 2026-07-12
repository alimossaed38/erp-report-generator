<?php
declare(strict_types=1);

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
