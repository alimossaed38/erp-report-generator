<?php

final class CustomersController extends Controller
{
    public function index(): void
    {
        $repo = new SalesRepository();
        [$from, $to] = Report::orderedRange(Request::date('from'), Request::date('to'));
        $search = Request::search();
        $page = Request::int('page', 1);
        $perPage = Request::enum('per_page', array_map('strval', Config::get('per_page_options', [10, 25, 50, 100])), '25');
        $sort = Request::enum('sort', ['customer_name', 'revenue', 'invoices', 'last_purchase'], 'revenue');
        $direction = Request::enum('dir', ['asc', 'desc'], 'desc');
        $pageData = $repo->customerReport($from, $to, $search, $page, (int) $perPage, $sort, $direction);
        $bounds = $repo->dateBounds();

        $this->render('customers', [
            'title' => 'تحليل العملاء',
            'subtitle' => 'استعرض أداء كل عميل من حيث الإيرادات وعدد الفواتير والمستحقات.',
            'active' => 'customers',
            'from' => $from,
            'to' => $to,
            'q' => $search,
            'perPage' => (int) $perPage,
            'sort' => $sort,
            'direction' => $direction,
            'rows' => $pageData['rows'],
            'pagination' => $pageData['pagination'],
            'quickRanges' => Report::quickRanges($bounds['max']),
            'bounds' => $bounds,
        ]);
    }

    public function view(): void
    {
        $repo = new SalesRepository();
        $name = Request::get('name');
        $customer = $name !== null ? $repo->customerDetail($name) : null;

        if ($customer === null) {
            $this->render('customer_detail', [
                'title' => 'تفاصيل العميل',
                'active' => 'customers',
                'found' => false,
                'name' => $name,
            ]);
            return;
        }

        $this->render('customer_detail', [
            'title' => 'العميل: ' . $customer['customer_name'],
            'active' => 'customers',
            'found' => true,
            'customer' => $customer,
            'invoices' => $repo->customerInvoices($name),
            'monthly' => $repo->customerMonthly($name),
        ]);
    }
}
