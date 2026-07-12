<?php

class FinanceController extends Controller
{
    public function index(): void
    {
        $repo = new FinanceRepository();
        $from = Request::get('from');
        $to = Request::get('to');
        $this->render('finance', [
            'title' => 'التقرير المالي',
            'active' => 'finance',
            'from' => $from,
            'to' => $to,
            'summary' => $repo->summary($from, $to),
            'monthly' => $repo->monthly($from, $to),
            'transactions' => $repo->transactions($from, $to, 50),
        ]);
    }
}
