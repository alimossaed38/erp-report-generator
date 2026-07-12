<?php

class Request
{
    public static function get(string $key, ?string $default = null): ?string
    {
        if (!isset($_GET[$key]) || $_GET[$key] === '') {
            return $default;
        }
        return trim((string) $_GET[$key]);
    }
}
