<?php

final class ExportController extends Controller
{
    public function index(): void
    {
        $report = Request::enum('report', ['sales', 'inventory', 'finance'], 'sales');
        $format = Request::enum('format', ['csv', 'xls'], 'csv');
        [$headers, $rows, $title] = $this->dataset($report);
        $stamp = date('Ymd-His');
        $filename = $report . '-report-' . $stamp . '.' . $format;

        match ($format) {
            'xls' => $this->xls($headers, $rows, $filename, $title),
            default => $this->csv($headers, $rows, $filename),
        };
    }

    private function dataset(string $report): array
    {
        if ($report === 'inventory') {
            $category = Request::get('category');
            $search = Request::search();
            $status = Request::enum('status', ['low', 'available', 'out'], null);
            $rows = (new InventoryRepository())->productPage($category, $search, $status, 1, 10000, 'name', 'asc')['rows'];
            $data = array_map(static fn(array $product): array => [
                $product['name'],
                $product['category'],
                (int) $product['stock_qty'],
                (int) $product['reorder_level'],
                (float) $product['cost'],
                (float) $product['price'],
                (float) $product['stock_value'],
                $product['out'] ? 'نفد المخزون' : ($product['low'] ? 'منخفض' : 'متوفر'),
            ], $rows);
            return [['المنتج', 'التصنيف', 'الكمية', 'حد إعادة الطلب', 'التكلفة', 'سعر البيع', 'قيمة المخزون', 'الحالة'], $data, 'تقرير المخزون'];
        }

        if ($report === 'finance') {
            [$from, $to] = Report::orderedRange(Request::date('from'), Request::date('to'));
            $type = Request::enum('type', ['income', 'expense'], null);
            $category = Request::get('category');
            $search = Request::search();
            $rows = (new FinanceRepository())->transactionPage($from, $to, $type, $category, $search, 1, 10000, 'txn_date', 'desc')['rows'];
            $data = array_map(static fn(array $transaction): array => [
                $transaction['txn_date'],
                $transaction['type'] === 'income' ? 'إيراد' : 'مصروف',
                $transaction['category'],
                (float) $transaction['amount'],
                $transaction['description'],
            ], $rows);
            return [['التاريخ', 'النوع', 'التصنيف', 'المبلغ', 'الوصف'], $data, 'التقرير المالي'];
        }

        [$from, $to] = Report::orderedRange(Request::date('from'), Request::date('to'));
        $search = Request::search();
        $rows = (new SalesRepository())->invoicePage($from, $to, $search, 1, 10000, 'invoice_date', 'desc')['rows'];
        $data = array_map(static fn(array $invoice): array => [
            $invoice['invoice_no'],
            $invoice['customer_name'],
            $invoice['invoice_date'],
            (float) $invoice['total'],
        ], $rows);
        return [['رقم الفاتورة', 'العميل', 'التاريخ', 'الإجمالي'], $data, 'تقرير المبيعات'];
    }

    private function csv(array $headers, array $rows, string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_map([$this, 'safeSpreadsheetCell'], $row));
        }
        fclose($out);
    }

    private function xls(array $headers, array $rows, string $filename, string $title): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');

        echo "\xEF\xBB\xBF";
        echo '<!doctype html><html lang="ar" dir="rtl"><head><meta charset="utf-8">';
        echo '<style>body{font-family:Tahoma,Arial}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccd3df;padding:8px;text-align:right}th{background:#eaf0ff;color:#173b78}h1{font-size:20px}</style></head><body>';
        echo '<h1>' . Ui::e($title) . '</h1><p>تاريخ الإنشاء: ' . Ui::e(date('Y-m-d H:i')) . '</p><table><thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . Ui::e($header) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . Ui::e($this->safeSpreadsheetCell($cell)) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
    }

    private function safeSpreadsheetCell(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }
}
