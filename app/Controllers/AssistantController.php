<?php

/**
 * Embedded report assistant. Parses an Arabic request with Intent, then reuses
 * the existing report repositories to answer it — no new SQL, no external model.
 */
final class AssistantController extends Controller
{
    private const ROW_LIMIT = 8;

    private const REPORT_NAMES = [
        'sales' => 'المبيعات',
        'customers' => 'العملاء',
        'aging' => 'أعمار الذمم',
        'inventory' => 'المخزون',
        'finance' => 'المالية',
    ];

    private const SUGGESTIONS = [
        'مبيعات آخر ٩٠ يوم',
        'أعلى ٥ منتجات مبيعًا',
        'الذمم المتأخرة',
        'الأصناف النافدة',
        'مصروفات هذا الشهر',
        'أكثر ١٠ عملاء إنفاقًا',
    ];

    public function ask(): void
    {
        $q = Request::search('q', 200) ?? '';
        $ref = (new SalesRepository())->dateBounds()['max'];
        $this->json($this->build($q, $ref));
    }

    public function build(string $q, ?string $ref): array
    {
        if (trim($q) === '') {
            return $this->unknown();
        }

        $intent = Intent::parse($q, $ref);
        if ($intent['report'] === null) {
            return $this->unknown();
        }

        return match ($intent['report']) {
            'sales' => $this->sales($intent),
            'customers' => $this->customers($intent),
            'aging' => $this->aging($intent, $ref),
            'inventory' => $this->inventory($intent),
            'finance' => $this->finance($intent),
        };
    }

    private function unknown(): array
    {
        return [
            'ok' => false,
            'message' => 'لم أفهم الطلب. جرّب أحد الأمثلة:',
            'suggestions' => self::SUGGESTIONS,
        ];
    }

    private function sales(array $i): array
    {
        $repo = new SalesRepository();
        $s = $repo->summary($i['from'], $i['to'], $i['search']);

        $kpis = [
            self::kpi('الإجمالي', Ui::money($s['total'])),
            self::kpi('عدد الفواتير', Ui::number($s['count'])),
            self::kpi('متوسط الفاتورة', Ui::money($s['avg'])),
            self::kpi('العملاء', Ui::number($s['customers'])),
        ];

        if ($i['topN'] !== null) {
            $rows = $repo->topProducts($i['from'], $i['to'], $i['topN']);
            $table = self::table(['المنتج', 'التصنيف', 'الكمية', 'الإيراد'], array_map(
                static fn(array $r): array => [$r['name'], $r['category'], Ui::number($r['qty']), Ui::money($r['revenue'])],
                $rows
            ));
        } else {
            $rows = $repo->invoicePage($i['from'], $i['to'], $i['search'], 1, self::ROW_LIMIT, 'invoice_date', 'desc')['rows'];
            $table = self::table(['رقم الفاتورة', 'العميل', 'التاريخ', 'الإجمالي'], array_map(
                static fn(array $r): array => [$r['invoice_no'], $r['customer_name'], $r['invoice_date'], Ui::money($r['total'])],
                $rows
            ));
        }

        return $this->ok($i, [
            'kpis' => $kpis,
            'table' => $table,
            'reportUrl' => Ui::url('/sales', ['from' => $i['from'], 'to' => $i['to'], 'q' => $i['search']]),
            'exportUrl' => Ui::url('/export', ['report' => 'sales', 'format' => $i['format'], 'from' => $i['from'], 'to' => $i['to'], 'q' => $i['search']]),
        ]);
    }

    private function customers(array $i): array
    {
        $repo = new SalesRepository();
        $limit = $i['topN'] ?? self::ROW_LIMIT;
        $page = $repo->customerReport($i['from'], $i['to'], $i['search'], 1, $limit, 'revenue', 'desc');
        $s = $repo->summary($i['from'], $i['to'], $i['search']);

        $kpis = [
            self::kpi('عدد العملاء', Ui::number($page['pagination']['total'])),
            self::kpi('إجمالي المبيعات', Ui::money($s['total'])),
        ];
        $table = self::table(['العميل', 'الفواتير', 'الإيراد', 'آخر شراء'], array_map(
            static fn(array $r): array => [$r['customer_name'], Ui::number($r['invoices']), Ui::money($r['revenue']), (string) $r['last_purchase']],
            $page['rows']
        ));

        return $this->ok($i, [
            'kpis' => $kpis,
            'table' => $table,
            'reportUrl' => Ui::url('/customers', ['from' => $i['from'], 'to' => $i['to'], 'q' => $i['search']]),
            'exportUrl' => null,
        ]);
    }

    private function aging(array $i, ?string $ref): array
    {
        $repo = new SalesRepository();
        $invoices = $repo->agingInvoices();
        $asOf = $ref ?: date('Y-m-d');
        $buckets = Analytics::agingBuckets($invoices, $asOf);
        $overdue = $buckets['total'] - $buckets['current'];

        $kpis = [
            self::kpi('إجمالي المستحق', Ui::money($buckets['total'])),
            self::kpi('المتأخر', Ui::money($overdue)),
            self::kpi('عدد الفواتير', Ui::number(count($invoices))),
        ];
        $table = self::table(['الفاتورة', 'العميل', 'تاريخ الاستحقاق', 'المتبقي'], array_map(
            static fn(array $r): array => [$r['invoice_no'], $r['customer_name'], $r['due_date'], Ui::money($r['outstanding'])],
            array_slice($invoices, 0, self::ROW_LIMIT)
        ));

        return $this->ok($i, [
            'kpis' => $kpis,
            'table' => $table,
            'reportUrl' => '/aging',
            'exportUrl' => null,
        ]);
    }

    private function inventory(array $i): array
    {
        $repo = new InventoryRepository();
        $s = $repo->summary(null, $i['search'], $i['status']);
        $rows = $repo->productPage(null, $i['search'], $i['status'], 1, self::ROW_LIMIT, 'name', 'asc')['rows'];

        $kpis = [
            self::kpi('قيمة المخزون', Ui::money($s['value'])),
            self::kpi('عدد الأصناف', Ui::number($s['items'])),
            self::kpi('النافد', Ui::number($s['out_of_stock'])),
            self::kpi('تحت الطلب', Ui::number($s['low'])),
        ];
        $table = self::table(['المنتج', 'التصنيف', 'الكمية', 'قيمة المخزون'], array_map(
            static fn(array $r): array => [$r['name'], $r['category'], Ui::number($r['stock_qty']), Ui::money($r['stock_value'])],
            $rows
        ));

        return $this->ok($i, [
            'kpis' => $kpis,
            'table' => $table,
            'reportUrl' => Ui::url('/inventory', ['q' => $i['search'], 'status' => $i['status']]),
            'exportUrl' => Ui::url('/export', ['report' => 'inventory', 'format' => $i['format'], 'q' => $i['search'], 'status' => $i['status']]),
        ]);
    }

    private function finance(array $i): array
    {
        $repo = new FinanceRepository();
        $s = $repo->summary($i['from'], $i['to'], $i['type'], null, $i['search']);
        $rows = $repo->transactionPage($i['from'], $i['to'], $i['type'], null, $i['search'], 1, self::ROW_LIMIT, 'txn_date', 'desc')['rows'];

        $kpis = [
            self::kpi('الإيرادات', Ui::money($s['income'])),
            self::kpi('المصروفات', Ui::money($s['expense'])),
            self::kpi('الصافي', Ui::money($s['net'])),
            self::kpi('عدد الحركات', Ui::number($s['count'])),
        ];
        $table = self::table(['التاريخ', 'النوع', 'التصنيف', 'المبلغ'], array_map(
            static fn(array $r): array => [$r['txn_date'], $r['type'] === 'income' ? 'إيراد' : 'مصروف', $r['category'], Ui::money($r['amount'])],
            $rows
        ));

        return $this->ok($i, [
            'kpis' => $kpis,
            'table' => $table,
            'reportUrl' => Ui::url('/finance', ['from' => $i['from'], 'to' => $i['to'], 'type' => $i['type']]),
            'exportUrl' => Ui::url('/export', ['report' => 'finance', 'format' => $i['format'], 'from' => $i['from'], 'to' => $i['to'], 'type' => $i['type']]),
        ]);
    }

    private function ok(array $intent, array $payload): array
    {
        return array_merge(['ok' => true, 'understood' => $this->understood($intent)], $payload);
    }

    private function understood(array $i): string
    {
        $parts = ['تقرير ' . (self::REPORT_NAMES[$i['report']] ?? '')];
        if ($i['from'] && $i['to']) {
            $parts[] = 'من ' . $i['from'] . ' إلى ' . $i['to'];
        }
        if ($i['search']) {
            $parts[] = 'العميل: ' . $i['search'];
        }
        if ($i['status'] === 'out') {
            $parts[] = 'الأصناف النافدة';
        } elseif ($i['status'] === 'low') {
            $parts[] = 'تحت حد الطلب';
        }
        if ($i['type'] === 'income') {
            $parts[] = 'الإيرادات';
        } elseif ($i['type'] === 'expense') {
            $parts[] = 'المصروفات';
        }
        return implode(' · ', $parts);
    }

    private static function kpi(string $label, string $value): array
    {
        return ['label' => $label, 'value' => $value];
    }

    private static function table(array $columns, array $rows): array
    {
        return ['columns' => $columns, 'rows' => $rows];
    }
}
