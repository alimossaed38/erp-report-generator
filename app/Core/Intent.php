<?php

/**
 * Pure Arabic request parser for the assistant widget.
 *
 * Turns a natural-language request into a structured intent that the
 * AssistantController maps onto the existing report repositories. It never
 * touches the database — the "as of" reference date (latest date in the data)
 * is injected so date math stays deterministic and unit-testable.
 */
final class Intent
{
    private const MAX_LEN = 200;

    public static function parse(string $text, ?string $refDate = null): array
    {
        $ref = $refDate ?: date('Y-m-d');

        // Original text: digits normalised + whitespace collapsed. Customer
        // names are extracted from this so their spelling matches the data.
        $original = self::normalizeDigits($text);
        $original = trim(preg_replace('/\s+/u', ' ', $original) ?? $original);
        $original = Ui::slice($original, self::MAX_LEN);

        // Matching text: hamza/alef/ya/ta-marbuta folded + diacritics stripped,
        // so keyword lookups tolerate spelling variants.
        $norm = self::fold($original);

        $report = self::detectReport($norm);
        [$from, $to] = self::detectRange($norm, $ref);

        $intent = [
            'report' => $report,
            'from' => $from,
            'to' => $to,
            'search' => self::detectCustomer($original),
            'status' => self::detectStatus($norm),
            'type' => self::detectType($norm),
            'topN' => self::detectTopN($norm),
            'export' => false,
            'format' => 'csv',
        ];

        [$intent['export'], $intent['format']] = self::detectExport($norm);

        return $intent;
    }

    private static function normalizeDigits(string $text): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $ascii = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($arabic, $ascii, $text);
    }

    private static function fold(string $text): string
    {
        $text = preg_replace('/[\x{064B}-\x{0652}\x{0640}]/u', '', $text) ?? $text; // diacritics + tatweel
        $text = str_replace(['أ', 'إ', 'آ', 'ى', 'ة'], ['ا', 'ا', 'ا', 'ي', 'ه'], $text);
        return mb_strtolower($text, 'UTF-8');
    }

    private static function has(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }
        return false;
    }

    private static function detectReport(string $norm): ?string
    {
        if (self::has($norm, ['ذمم', 'مستحق', 'متاخر', 'اعمار'])) {
            return 'aging';
        }
        // Sales wins over inventory when the request is about products SOLD.
        if (self::has($norm, ['مبيع', 'فاتور', 'فواتير'])) {
            return 'sales';
        }
        if (self::has($norm, ['مخزون', 'مخزن', 'اصناف', 'صنف', 'نافد', 'نفد', 'حد الطلب', 'حد اعاده', 'منتج'])) {
            return 'inventory';
        }
        if (self::has($norm, ['مالي', 'مصروف', 'مصاريف', 'ايراد', 'تدفق', 'صافي'])) {
            return 'finance';
        }
        if (self::has($norm, ['عملاء', 'عميل', 'زبائن', 'زبون'])) {
            return 'customers';
        }
        return null;
    }

    private static function detectRange(string $norm, string $ref): array
    {
        $end = new DateTimeImmutable($ref);

        // Month name + year, e.g. "يناير 2026".
        $months = ['يناير', 'فبراير', 'مارس', 'ابريل', 'مايو', 'يونيو', 'يوليو', 'اغسطس', 'سبتمبر', 'اكتوبر', 'نوفمبر', 'ديسمبر'];
        foreach ($months as $i => $name) {
            if (mb_strpos($norm, $name) !== false && preg_match('/(20\d{2})/', $norm, $y)) {
                $m = str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
                $start = new DateTimeImmutable($y[1] . '-' . $m . '-01');
                return [$start->format('Y-m-d'), $start->format('Y-m-t')];
            }
        }

        // "آخر N يوم"
        if (preg_match('/اخر\s+(\d+)\s*(يوم|يوما|ايام)/u', $norm, $m)) {
            $n = max(1, (int) $m[1]);
            return [$end->modify('-' . ($n - 1) . ' days')->format('Y-m-d'), $end->format('Y-m-d')];
        }
        // "آخر N شهر/أشهر"
        if (preg_match('/اخر\s+(\d+)\s*(شهر|اشهر)/u', $norm, $m)) {
            $n = max(1, (int) $m[1]);
            return [$end->modify('-' . $n . ' months')->modify('+1 day')->format('Y-m-d'), $end->format('Y-m-d')];
        }
        // "آخر [N] سنة/سنوات/عام"
        if (preg_match('/اخر\s+(?:(\d+)\s*)?(سنه|سنوات|عام|اعوام)/u', $norm, $m)) {
            $n = max(1, (int) ($m[1] ?: 1));
            return [$end->modify('-' . $n . ' years')->modify('+1 day')->format('Y-m-d'), $end->format('Y-m-d')];
        }

        // "الشهر الماضي/السابق"
        if (self::has($norm, ['الشهر الماضي', 'الشهر السابق'])) {
            $prev = $end->modify('first day of previous month');
            return [$prev->format('Y-m-01'), $prev->format('Y-m-t')];
        }
        // "هذا الشهر / الشهر الحالي"
        if (self::has($norm, ['هذا الشهر', 'الشهر الحالي'])) {
            return [$end->format('Y-m-01'), $end->format('Y-m-d')];
        }
        // "العام/السنة الماضية"
        if (self::has($norm, ['العام الماضي', 'السنه الماضيه', 'العام السابق'])) {
            $year = (int) $end->format('Y') - 1;
            return [$year . '-01-01', $year . '-12-31'];
        }
        // "هذا العام / هذه السنة"
        if (self::has($norm, ['هذا العام', 'هذه السنه', 'العام الحالي'])) {
            return [$end->format('Y') . '-01-01', $end->format('Y-m-d')];
        }

        // Bare year, e.g. "2026".
        if (preg_match('/(20\d{2})/', $norm, $y)) {
            return [$y[1] . '-01-01', $y[1] . '-12-31'];
        }

        return [null, null];
    }

    private static function detectCustomer(string $original): ?string
    {
        // Trigger on singular "عميل/زبون" (with optional ل/لل prefix) followed by a name.
        if (preg_match('/(?:لل?)?(?:عميل|زبون)\s+(.+?)(?:\s+(?:آخر|هذا|هذه|في|خلال|من|صدّر|صدر|تصدير|إكسل|اكسل|csv|excel)\b|$)/u', $original, $m)) {
            $name = trim($m[1]);
            return $name !== '' ? $name : null;
        }
        return null;
    }

    private static function detectStatus(string $norm): ?string
    {
        if (self::has($norm, ['نافد', 'نفد', 'نافذ'])) {
            return 'out';
        }
        if (self::has($norm, ['منخفض', 'تحت الطلب', 'حد الطلب', 'حد اعاده'])) {
            return 'low';
        }
        return null;
    }

    private static function detectType(string $norm): ?string
    {
        if (self::has($norm, ['مصروف', 'مصاريف'])) {
            return 'expense';
        }
        if (mb_strpos($norm, 'ايراد') !== false) {
            return 'income';
        }
        return null;
    }

    private static function detectTopN(string $norm): ?int
    {
        if (preg_match('/(?:اعلي|اكثر|افضل|top)\s*(\d+)?/u', $norm, $m)) {
            return isset($m[1]) && $m[1] !== '' ? max(1, (int) $m[1]) : 10;
        }
        return null;
    }

    private static function detectExport(string $norm): array
    {
        $wants = self::has($norm, ['صدر', 'تصدير', 'csv', 'excel', 'اكسل']);
        $format = self::has($norm, ['excel', 'اكسل', 'xls']) ? 'xls' : 'csv';
        return [$wants, $format];
    }
}
