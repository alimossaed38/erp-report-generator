<?php

final class Config
{
    private static ?array $values = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$values === null) {
            $file = __DIR__ . '/../../config/app.php';
            self::$values = is_file($file) ? (require $file) : [];
        }

        return self::$values[$key] ?? $default;
    }
}
