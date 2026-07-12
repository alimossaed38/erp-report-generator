<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        $sales = new SalesRepository();
        $inv = new InventoryRepository();
        $fin = new FinanceRepository();
        $this->render('dashboard', [
            'title' => 'لوحة التحكم',
            'active' => 'dashboard',
            'salesSummary' => $sales->summary(null, null),
            'salesMonthly' => $sales->monthly(null, null),
            'invSummary' => $inv->summary(null),
            'finSummary' => $fin->summary(null, null),
            'finMonthly' => $fin->monthly(null, null),
            'recentInvoices' => $sales->invoices(null, null, 8),
        ]);
    }
}
