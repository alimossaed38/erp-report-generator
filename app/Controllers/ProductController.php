<?php

final class ProductController extends Controller
{
    public function view(): void
    {
        $repo = new InventoryRepository();
        $id = Request::int('id', 0, 0);
        $product = $id > 0 ? $repo->productByIdWithStats($id) : null;

        if ($product === null) {
            $this->render('product_detail', [
                'title' => 'تفاصيل المنتج',
                'active' => 'inventory',
                'found' => false,
                'id' => $id,
            ]);
            return;
        }

        $this->render('product_detail', [
            'title' => 'المنتج: ' . $product['name'],
            'active' => 'inventory',
            'found' => true,
            'product' => $product,
            'monthly' => $repo->productSalesMonthly($id),
            'invoices' => $repo->productInvoices($id),
        ]);
    }
}
