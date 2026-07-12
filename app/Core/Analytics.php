<?php

final class Analytics
{
    public static function growth(array $monthly, string $valueKey): array
    {
        return array_map(static function (array $row, int $index) use ($monthly, $valueKey): array {
            $current = (float) $row[$valueKey];

            $mom = null;
            if ($index > 0) {
                $mom = Report::change($current, (float) $monthly[$index - 1][$valueKey]);
            }

            $yoy = null;
            $priorIndex = null;
            if (isset($row['ym'])) {
                foreach ($monthly as $i => $candidate) {
                    if (isset($candidate['ym']) && self::isTwelveMonthsBefore($candidate['ym'], $row['ym'])) {
                        $priorIndex = $i;
                        break;
                    }
                }
            }
            if ($priorIndex === null && $index - 12 >= 0) {
                $priorIndex = $index - 12;
            }
            if ($priorIndex !== null) {
                $yoy = Report::change($current, (float) $monthly[$priorIndex][$valueKey]);
            }

            $row['mom'] = $mom;
            $row['yoy'] = $yoy;

            return $row;
        }, $monthly, array_keys($monthly));
    }

    private static function isTwelveMonthsBefore(string $candidateYm, string $currentYm): bool
    {
        [$cy, $cm] = array_map('intval', explode('-', $candidateYm));
        [$ty, $tm] = array_map('intval', explode('-', $currentYm));

        return ($ty * 12 + $tm) - ($cy * 12 + $cm) === 12;
    }

    public static function movingAverage(array $monthly, string $valueKey, int $window = 3): array
    {
        $values = array_values($monthly);

        return array_map(static function (array $row, int $index) use ($values, $valueKey, $window): array {
            $ma = null;
            if ($index >= $window - 1) {
                $sum = 0.0;
                for ($i = $index - $window + 1; $i <= $index; $i++) {
                    $sum += (float) $values[$i][$valueKey];
                }
                $ma = $sum / $window;
            }

            $row['ma'] = $ma;

            return $row;
        }, $values, array_keys($values));
    }

    public static function agingBuckets(array $invoices, string $asOf): array
    {
        $asOfDate = new DateTimeImmutable($asOf);

        $buckets = [
            'current' => 0.0,
            'd1_30' => 0.0,
            'd31_60' => 0.0,
            'd61_90' => 0.0,
            'd90_plus' => 0.0,
        ];
        $counts = [
            'current' => 0,
            'd1_30' => 0,
            'd31_60' => 0,
            'd61_90' => 0,
            'd90_plus' => 0,
        ];
        $total = 0.0;

        foreach ($invoices as $invoice) {
            $outstanding = (float) $invoice['outstanding'];
            $dueDate = new DateTimeImmutable($invoice['due_date']);
            $daysLate = (int) round(($asOfDate->getTimestamp() - $dueDate->getTimestamp()) / 86400);

            $bucket = match (true) {
                $daysLate <= 0 => 'current',
                $daysLate <= 30 => 'd1_30',
                $daysLate <= 60 => 'd31_60',
                $daysLate <= 90 => 'd61_90',
                default => 'd90_plus',
            };

            $buckets[$bucket] += $outstanding;
            $counts[$bucket]++;
            $total += $outstanding;
        }

        return [
            'current' => $buckets['current'],
            'd1_30' => $buckets['d1_30'],
            'd31_60' => $buckets['d31_60'],
            'd61_90' => $buckets['d61_90'],
            'd90_plus' => $buckets['d90_plus'],
            'total' => $total,
            'counts' => $counts,
        ];
    }

    public static function targetProgress(float $actual, ?float $target): array
    {
        $pct = null;
        if ($target !== null && abs($target) >= 0.00001) {
            $pct = ($actual / $target) * 100;
        }

        $remaining = $target !== null ? $target - $actual : null;
        $met = $target !== null && $actual >= $target;

        return [
            'actual' => $actual,
            'target' => $target,
            'pct' => $pct,
            'remaining' => $remaining,
            'met' => $met,
        ];
    }
}
