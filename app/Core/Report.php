<?php

final class Report
{
    public static function orderedRange(?string $from, ?string $to): array
    {
        if ($from && $to && $from > $to) {
            return [$to, $from];
        }

        return [$from, $to];
    }

    public static function quickRanges(?string $maxDate): array
    {
        if (!$maxDate) {
            return [];
        }

        $end = new DateTimeImmutable($maxDate);
        return [
            '30d' => ['label' => 'آخر 30 يوم', 'from' => $end->modify('-29 days')->format('Y-m-d'), 'to' => $maxDate],
            '90d' => ['label' => 'آخر 90 يوم', 'from' => $end->modify('-89 days')->format('Y-m-d'), 'to' => $maxDate],
            'year' => ['label' => 'آخر 12 شهر', 'from' => $end->modify('-1 year +1 day')->format('Y-m-d'), 'to' => $maxDate],
        ];
    }

    public static function previousRange(?string $from, ?string $to): array
    {
        if (!$from || !$to) {
            return [null, null];
        }

        $start = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);
        if ($end < $start) {
            return [null, null];
        }

        $days = (int) $start->diff($end)->days + 1;
        $previousTo = $start->modify('-1 day');
        $previousFrom = $previousTo->modify('-' . ($days - 1) . ' days');

        return [$previousFrom->format('Y-m-d'), $previousTo->format('Y-m-d')];
    }

    public static function change(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.00001) {
            return null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    public static function pagination(int $total, int $page, int $perPage): array
    {
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $pages);
        $from = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
        $to = min($total, $page * $perPage);

        return compact('total', 'page', 'perPage', 'pages', 'from', 'to');
    }

    public static function latestChange(array $rows, string $valueKey): ?float
    {
        if (count($rows) < 2) {
            return null;
        }

        $last = (float) $rows[array_key_last($rows)][$valueKey];
        $previous = (float) $rows[array_key_last($rows) - 1][$valueKey];
        return self::change($last, $previous);
    }
}
