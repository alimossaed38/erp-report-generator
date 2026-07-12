<?php

final class FinanceController extends Controller
{
    public function index(): void
    {
        $repo = new FinanceRepository();
        [$from, $to] = Report::orderedRange(Request::date('from'), Request::date('to'));
        $type = Request::enum('type', ['income', 'expense'], null);
        $category = Request::get('category');
        $search = Request::search();
        $page = Request::int('page', 1);
        $perPage = Request::enum('per_page', array_map('strval', Config::get('per_page_options', [10, 25, 50, 100])), '25');
        $sort = Request::enum('sort', ['txn_date', 'type', 'category', 'amount'], 'txn_date');
        $direction = Request::enum('dir', ['asc', 'desc'], 'desc');
        $pageData = $repo->transactionPage($from, $to, $type, $category, $search, $page, (int) $perPage, $sort, $direction);
        $summary = $repo->summary($from, $to, $type, $category, $search);

        [$previousFrom, $previousTo] = Report::previousRange($from, $to);
        $previousSummary = ($previousFrom && $previousTo && !$type && !$category && !$search)
            ? $repo->summary($previousFrom, $previousTo)
            : null;
        $bounds = $repo->dateBounds();

        $this->render('finance', [
            'title' => 'التحليل المالي',
            'subtitle' => 'تابع الإيرادات والمصروفات وصافي التدفق مع تحليل بنود الإنفاق.',
            'active' => 'finance',
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'category' => $category,
            'q' => $search,
            'perPage' => (int) $perPage,
            'sort' => $sort,
            'direction' => $direction,
            'summary' => $summary,
            'previousSummary' => $previousSummary,
            'monthly' => $repo->monthly($from, $to),
            'transactions' => $pageData['rows'],
            'pagination' => $pageData['pagination'],
            'categories' => $repo->categories(),
            'expenses' => $repo->expenseByCategory($from, $to),
            'quickRanges' => Report::quickRanges($bounds['max']),
            'bounds' => $bounds,
        ]);
    }
}
