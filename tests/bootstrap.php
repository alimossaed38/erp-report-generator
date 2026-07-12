<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    foreach (['Core', 'Controllers', 'Models'] as $dir) {
        $file = __DIR__ . '/../app/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

date_default_timezone_set((string) Config::get('timezone', 'UTC'));
