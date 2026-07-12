<?php

final class DashboardController extends Controller
{
    public function index(): void
    {
        $sales = new SalesRepository();
        $inventory = new InventoryRepository();
        $finance = new FinanceRepository();

        $salesMonthly = $sales->monthly(null, null);
        $financeMonthly = $finance->monthly(null, null);
        $salesTrend = Report::latestChange($salesMonthly, 'total');
        $financeTrend = Report::latestChange($financeMonthly, 'net');
        $topProducts = $sales->topProducts(null, null, 5);

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
            'salesBounds' => $sales->dateBounds(),
        ]);
    }
}
