<?php

final class Request
{
    public static function get(string $key, ?string $default = null): ?string
    {
        if (!isset($_GET[$key]) || $_GET[$key] === '') {
            return $default;
        }

        return trim((string) $_GET[$key]);
    }

    public static function date(string $key): ?string
    {
        $value = self::get($key);
        if ($value === null) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }

    public static function int(string $key, int $default, int $min = 1, int $max = PHP_INT_MAX): int
    {
        $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);
        if ($value === false || $value === null) {
            $raw = $_GET[$key] ?? null;
            $value = filter_var($raw, FILTER_VALIDATE_INT);
        }

        if ($value === false || $value === null) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }

    public static function enum(string $key, array $allowed, ?string $default = null): ?string
    {
        $value = self::get($key, $default);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    public static function search(string $key = 'q', int $maxLength = 100): ?string
    {
        $value = self::get($key);
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($chars) ? implode('', array_slice($chars, 0, $maxLength)) : substr($value, 0, $maxLength);
    }
}
