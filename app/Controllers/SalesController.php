<?php

final class SalesController extends Controller
{
    public function index(): void
    {
        $repo = new SalesRepository();
        [$from, $to] = Report::orderedRange(Request::date('from'), Request::date('to'));
        $search = Request::search();
        $page = Request::int('page', 1);
        $perPage = Request::enum('per_page', array_map('strval', Config::get('per_page_options', [10, 25, 50, 100])), '25');
        $sort = Request::enum('sort', ['invoice_no', 'customer', 'invoice_date', 'total'], 'invoice_date');
        $direction = Request::enum('dir', ['asc', 'desc'], 'desc');
        $pageData = $repo->invoicePage($from, $to, $search, $page, (int) $perPage, $sort, $direction);

        [$previousFrom, $previousTo] = Report::previousRange($from, $to);
        $summary = $repo->summary($from, $to, $search);
        $previousSummary = ($previousFrom && $previousTo && !$search)
            ? $repo->summary($previousFrom, $previousTo)
            : null;
        $bounds = $repo->dateBounds();

        $this->render('sales', [
            'title' => 'تحليل المبيعات',
            'subtitle' => 'حلّل الإيرادات والفواتير والعملاء مع مقارنة الفترات وتصدير النتائج.',
            'active' => 'sales',
            'from' => $from,
            'to' => $to,
            'q' => $search,
            'perPage' => (int) $perPage,
            'sort' => $sort,
            'direction' => $direction,
            'summary' => $summary,
            'previousSummary' => $previousSummary,
            'monthly' => $repo->monthly($from, $to),
            'top' => $repo->topProducts($from, $to, 8),
            'invoices' => $pageData['rows'],
            'pagination' => $pageData['pagination'],
            'quickRanges' => Report::quickRanges($bounds['max']),
            'bounds' => $bounds,
        ]);
    }
}
