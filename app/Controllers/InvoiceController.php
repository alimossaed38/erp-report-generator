<?php

final class InvoiceController extends Controller
{
    public function view(): void
    {
        $repo = new SalesRepository();
        $no = Request::get('no');
        $invoice = $no !== null ? $repo->invoiceByNo($no) : null;

        if ($invoice === null) {
            $this->render('invoice_detail', [
                'title' => 'تفاصيل الفاتورة',
                'active' => 'sales',
                'found' => false,
                'no' => $no,
            ]);
            return;
        }

        $this->render('invoice_detail', [
            'title' => 'فاتورة رقم ' . $invoice['invoice_no'],
            'active' => 'sales',
            'found' => true,
            'invoice' => $invoice,
            'items' => $repo->invoiceItems((int) $invoice['id']),
        ]);
    }
}
