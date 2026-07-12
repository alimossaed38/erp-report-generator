<?php

final class SummaryController extends Controller
{
    private const BUCKET_LABELS = [
        'current' => 'جاري',
        'd1_30' => '1-30 يوم',
        'd31_60' => '31-60 يوم',
        'd61_90' => '61-90 يوم',
        'd90_plus' => 'أكثر من 90 يوم',
    ];

    public function index(): void
    {
        $sales = new SalesRepository();
        $inventory = new InventoryRepository();
        $finance = new FinanceRepository();
        $targets = new TargetRepository();

        $bounds = $sales->dateBounds();
        $currentYm = substr($bounds['max'] ?? '', 0, 7);

        $salesSummary = $sales->summary(null, null);
        $rawSalesMonthly = $sales->monthly(null, null);
        $salesGrowth = Analytics::growth($rawSalesMonthly, 'total');
        $lastGrowthRow = $salesGrowth ? end($salesGrowth) : null;
        $salesMomGrowth = $lastGrowthRow['mom'] ?? null;

        $margin = $sales->marginSummary(null, null);
        $monthlyMargin = $sales->monthlyMargin(null, null);

        $outstanding = $sales->outstanding(null, null);

        $agingInvoices = $sales->agingInvoices();
        $asOf = $bounds['max'];
        $agingBuckets = $asOf !== null ? Analytics::agingBuckets($agingInvoices, $asOf) : [
            'current' => 0.0, 'd1_30' => 0.0, 'd31_60' => 0.0, 'd61_90' => 0.0, 'd90_plus' => 0.0,
            'total' => 0.0, 'counts' => ['current' => 0, 'd1_30' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd90_plus' => 0],
        ];

        $currentMonthRow = null;
        foreach ($rawSalesMonthly as $row) {
            if ($row['ym'] === $currentYm) {
                $currentMonthRow = $row;
                break;
            }
        }
        $currentMonthTotal = (float) ($currentMonthRow['total'] ?? 0.0);
        $targetCurrent = Analytics::targetProgress($currentMonthTotal, $targets->forPeriod($currentYm));

        $rangeTarget = $targets->range($bounds['min'], $bounds['max']);
        $targetRange = Analytics::targetProgress($salesSummary['total'], $rangeTarget > 0.0 ? $rangeTarget : null);

        $invSummary = $inventory->summary(null);
        $finSummary = $finance->summary(null, null);

        $topCustomers = $sales->customerReport(null, null, null, 1, 5, 'revenue', 'desc')['rows'];
        $topProducts = $sales->topProducts(null, null, 5);

        $this->render('summary', [
            'title' => 'الملخص التنفيذي',
            'subtitle' => 'نظرة موجزة وقابلة للطباعة على أهم مؤشرات الأداء عبر المبيعات والمخزون والمالية.',
            'active' => 'summary',
            'salesSummary' => $salesSummary,
            'salesMomGrowth' => $salesMomGrowth,
            'margin' => $margin,
            'monthlyMargin' => $monthlyMargin,
            'outstanding' => $outstanding,
            'agingBuckets' => $agingBuckets,
            'bucketLabels' => self::BUCKET_LABELS,
            'asOf' => $asOf,
            'targetCurrent' => $targetCurrent,
            'targetRange' => $targetRange,
            'currentYm' => $currentYm,
            'invSummary' => $invSummary,
            'finSummary' => $finSummary,
            'topCustomers' => $topCustomers,
            'topProducts' => $topProducts,
            'bounds' => $bounds,
        ]);
    }
}
