<?php

final class DashboardController extends Controller
{
    public function index(): void
    {
        $sales = new SalesRepository();
        $inventory = new InventoryRepository();
        $finance = new FinanceRepository();

        $financeMonthly = $finance->monthly(null, null);
        $salesTrend = Report::latestChange($sales->monthly(null, null), 'total');
        $financeTrend = Report::latestChange($financeMonthly, 'net');
        $topProducts = $sales->topProducts(null, null, 5);

        $salesMonthly = Analytics::movingAverage(Analytics::growth($sales->monthly(null, null), 'total'), 'total');
        $outstanding = $sales->outstanding(null, null);
        $margin = $sales->marginSummary(null, null);
        $bounds = $sales->dateBounds();
        $currentYm = substr($bounds['max'] ?? '', 0, 7);
        $currentMonthRow = null;
        foreach ($salesMonthly as $row) {
            if ($row['ym'] === $currentYm) {
                $currentMonthRow = $row;
                break;
            }
        }
        $currentMonthTotal = $currentMonthRow['total'] ?? 0.0;
        $target = Analytics::targetProgress((float) $currentMonthTotal, (new TargetRepository())->forPeriod($currentYm));

        $this->render('dashboard', [
            'title' => 'لوحة المعلومات',
            'subtitle' => 'نظرة تنفيذية شاملة على أداء المبيعات والمخزون والتدفقات المالية.',
            'active' => 'dashboard',
            'salesSummary' => $sales->summary(null, null),
            'salesMonthly' => $salesMonthly,
            'salesTrend' => $salesTrend,
            'invSummary' => $inventory->summary(null),
            'finSummary' => $finance->summary(null, null),
            'finMonthly' => $financeMonthly,
            'financeTrend' => $financeTrend,
            'recentInvoices' => $sales->invoices(null, null, 7),
            'topProducts' => $topProducts,
            'lowStock' => $inventory->lowStock(6),
            'salesBounds' => $bounds,
            'outstanding' => $outstanding,
            'margin' => $margin,
            'target' => $target,
            'currentYm' => $currentYm,
        ]);
    }
}
