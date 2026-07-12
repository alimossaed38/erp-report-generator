<?php

class InventoryController extends Controller
{
    public function index(): void
    {
        $repo = new InventoryRepository();
        $category = Request::get('category');
        $this->render('inventory', [
            'title' => 'تقرير المخزون',
            'active' => 'inventory',
            'category' => $category,
            'categories' => $repo->categories(),
            'summary' => $repo->summary($category),
            'products' => $repo->products($category),
            'byCategory' => $repo->valueByCategory(),
        ]);
    }
}
