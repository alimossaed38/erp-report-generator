<?php

declare(strict_types=1);

$checks = [
    ['PHP 8.2 أو أحدث', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION],
    ['امتداد PDO', extension_loaded('pdo'), extension_loaded('pdo') ? 'مفعّل' : 'غير مفعّل'],
    ['امتداد PDO_SQLite', in_array('sqlite', PDO::getAvailableDrivers(), true), implode(', ', PDO::getAvailableDrivers()) ?: 'لا توجد محركات PDO'],
    ['مجلد database قابل للكتابة', is_writable(__DIR__ . '/../database'), realpath(__DIR__ . '/../database') ?: 'غير موجود'],
    ['ملف قاعدة البيانات موجود', is_file(__DIR__ . '/../database/erp.sqlite'), __DIR__ . '/../database/erp.sqlite'],
];

$failed = false;
echo "Ali ERP Analytics — فحص المتطلبات\n";
echo str_repeat('=', 46) . "\n";
foreach ($checks as [$label, $ok, $details]) {
    $failed = $failed || !$ok;
    echo ($ok ? '[ OK ] ' : '[FAIL] ') . $label . ' — ' . $details . "\n";
}
echo str_repeat('-', 46) . "\n";
echo $failed
    ? "توجد متطلبات ناقصة. فعّلها ثم أعد الفحص.\n"
    : "كل المتطلبات الأساسية جاهزة.\n";
exit($failed ? 1 : 0);
