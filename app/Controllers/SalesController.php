<?php

class SalesController extends Controller
{
    public function index(): void
    {
        $repo = new SalesRepository();
        $from = Request::get('from');
        $to = Request::get('to');
        $this->render('sales', [
            'title' => 'تقرير المبيعات',
            'active' => 'sales',
            'from' => $from,
            'to' => $to,
            'summary' => $repo->summary($from, $to),
            'monthly' => $repo->monthly($from, $to),
            'top' => $repo->topProducts($from, $to, 10),
            'invoices' => $repo->invoices($from, $to, 50),
        ]);
    }
}
