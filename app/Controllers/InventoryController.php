<?php

final class InventoryController extends Controller
{
    public function index(): void
    {
        $repo = new InventoryRepository();
        $category = Request::get('category');
        $search = Request::search();
        $status = Request::enum('status', ['low', 'available', 'out'], null);
        $page = Request::int('page', 1);
        $perPage = Request::enum('per_page', array_map('strval', Config::get('per_page_options', [10, 25, 50, 100])), '25');
        $sort = Request::enum('sort', ['name', 'category', 'stock', 'price', 'value'], 'name');
        $direction = Request::enum('dir', ['asc', 'desc'], 'asc');
        $pageData = $repo->productPage($category, $search, $status, $page, (int) $perPage, $sort, $direction);

        $this->render('inventory', [
            'title' => 'إدارة وتحليل المخزون',
            'subtitle' => 'راقب قيمة المخزون، الأصناف الحرجة، الربح المتوقع ومستويات إعادة الطلب.',
            'active' => 'inventory',
            'category' => $category,
            'q' => $search,
            'status' => $status,
            'perPage' => (int) $perPage,
            'sort' => $sort,
            'direction' => $direction,
            'categories' => $repo->categories(),
            'summary' => $repo->summary($category, $search, $status),
            'products' => $pageData['rows'],
            'pagination' => $pageData['pagination'],
            'byCategory' => $repo->valueByCategory(),
            'lowStock' => $repo->lowStock(8),
        ]);
    }
}
