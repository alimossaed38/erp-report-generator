<?php

class ExportController extends Controller
{
    public function index(): void
    {
        $report = Request::get('report', 'sales');
        $format = Request::get('format', 'csv');
        $from = Request::get('from');
        $to = Request::get('to');
        $category = Request::get('category');

        [$headers, $rows] = $this->dataset($report, $from, $to, $category);
        $filename = $report . '-report.' . ($format === 'xls' ? 'xls' : 'csv');

        if ($format === 'xls') {
            $this->xls($headers, $rows, $filename);
        } else {
            $this->csv($headers, $rows, $filename);
        }
    }

    private function dataset(string $report, ?string $from, ?string $to, ?string $category): array
    {
        if ($report === 'inventory') {
            $rows = array_map(fn($p) => [
                $p['name'], $p['category'], $p['stock_qty'], $p['reorder_level'],
                number_format((float)$p['price'], 0), $p['low'] ? 'ناقص' : 'متوفر',
            ], (new InventoryRepository())->products($category));
            return [['المنتج','التصنيف','الكمية','حد الطلب','السعر','الحالة'], $rows];
        }
        if ($report === 'finance') {
            $rows = array_map(fn($t) => [
                $t['txn_date'], $t['type']==='income'?'إيراد':'مصروف', $t['category'],
                number_format((float)$t['amount'], 0), $t['description'],
            ], (new FinanceRepository())->transactions($from, $to, 1000));
            return [['التاريخ','النوع','التصنيف','المبلغ','الوصف'], $rows];
        }
        // default: sales
        $rows = array_map(fn($i) => [
            $i['invoice_no'], $i['customer_name'], $i['invoice_date'],
            number_format((float)$i['total'], 0),
        ], (new SalesRepository())->invoices($from, $to, 1000));
        return [['رقم الفاتورة','العميل','التاريخ','الإجمالي'], $rows];
    }

    private function csv(array $headers, array $rows, string $filename): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM so Excel reads Arabic
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $r) { fputcsv($out, $r); }
        fclose($out);
    }

    private function xls(array $headers, array $rows, string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF<meta charset='utf-8'><table border='1'>";
        echo '<tr>';
        foreach ($headers as $h) { echo '<th>' . htmlspecialchars($h) . '</th>'; }
        echo '</tr>';
        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($r as $c) { echo '<td>' . htmlspecialchars((string)$c) . '</td>'; }
            echo '</tr>';
        }
        echo '</table>';
    }
}
