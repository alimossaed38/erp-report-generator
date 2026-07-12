<?php
declare(strict_types=1);

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if (preg_match('#^/assets/(css|js)/([A-Za-z0-9_.-]+)$#', $uriPath, $m)) {
    $file = __DIR__ . '/../assets/' . $m[1] . '/' . $m[2];
    if (is_file($file)) {
        $types = ['css' => 'text/css', 'js' => 'application/javascript'];
        header('Content-Type: ' . $types[$m[1]] . '; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        readfile($file);
        return;
    }
    http_response_code(404);
    return;
}

spl_autoload_register(function (string $class): void {
    foreach (['Core', 'Controllers', 'Models'] as $dir) {
        $file = __DIR__ . '/../app/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

require __DIR__ . '/../app/Core/Analytics.php';

date_default_timezone_set((string) Config::get('timezone', 'UTC'));

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'");

set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    error_log($e->__toString());
    echo '<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>خطأ</title>';
    echo '<body style="font-family:Tahoma,Arial;padding:40px;background:#f8fafc;color:#0f172a">';
    echo '<h1>تعذر إكمال الطلب</h1><p>حدث خطأ غير متوقع. راجع سجل الخادم ثم أعد المحاولة.</p></body></html>';
});

$router = new Router();
$router->add('/', [DashboardController::class, 'index']);
$router->add('/sales', [SalesController::class, 'index']);
$router->add('/inventory', [InventoryController::class, 'index']);
$router->add('/finance', [FinanceController::class, 'index']);
$router->add('/export', [ExportController::class, 'index']);
$router->add('/customers', [CustomersController::class, 'index']);
$router->add('/customers/view', [CustomersController::class, 'view']);
$router->add('/products/view', [ProductController::class, 'view']);
$router->add('/invoices/view', [InvoiceController::class, 'view']);

$router->dispatch($_SERVER['REQUEST_URI'] ?? '/');
