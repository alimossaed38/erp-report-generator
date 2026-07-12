<?php

final class InventoryRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    private function filters(?string $category, ?string $search = null, ?string $status = null): array
    {
        $where = [];
        $params = [];

        if ($category) {
            $where[] = 'category = :category';
            $params[':category'] = $category;
        }
        if ($search) {
            $where[] = '(name LIKE :search OR category LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }
        if ($status === 'low') {
            $where[] = 'stock_qty <= reorder_level';
        } elseif ($status === 'available') {
            $where[] = 'stock_qty > reorder_level';
        } elseif ($status === 'out') {
            $where[] = 'stock_qty = 0';
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    public function summary(?string $category, ?string $search = null, ?string $status = null): array
    {
        [$where, $params] = $this->filters($category, $search, $status);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) items,
                    COALESCE(SUM(stock_qty), 0) units,
                    COALESCE(SUM(stock_qty * cost), 0) value,
                    COALESCE(SUM((price - cost) * stock_qty), 0) potential_profit,
                    COALESCE(SUM(CASE WHEN stock_qty <= reorder_level THEN 1 ELSE 0 END), 0) low,
                    COALESCE(SUM(CASE WHEN stock_qty = 0 THEN 1 ELSE 0 END), 0) out_of_stock
             FROM products$where"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        return [
            'items' => (int) $row['items'],
            'units' => (int) $row['units'],
            'value' => (float) $row['value'],
            'potential_profit' => (float) $row['potential_profit'],
            'low' => (int) $row['low'],
            'out_of_stock' => (int) $row['out_of_stock'],
        ];
    }

    public function productPage(
        ?string $category,
        ?string $search,
        ?string $status,
        int $page,
        int $perPage,
        string $sort = 'name',
        string $direction = 'asc'
    ): array {
        [$where, $params] = $this->filters($category, $search, $status);
        $sortMap = [
            'name' => 'name',
            'category' => 'category',
            'stock' => 'stock_qty',
            'price' => 'price',
            'value' => '(stock_qty * cost)',
        ];
        $orderBy = $sortMap[$sort] ?? 'name';
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        $count = $this->db->prepare("SELECT COUNT(*) FROM products$where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $meta = Report::pagination($total, $page, $perPage);
        $offset = ($meta['page'] - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT *, (stock_qty * cost) stock_value, ((price - cost) * stock_qty) potential_profit
             FROM products$where
             ORDER BY $orderBy $direction, id ASC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = array_map(static function (array $row): array {
            $row['low'] = (int) $row['stock_qty'] <= (int) $row['reorder_level'];
            $row['out'] = (int) $row['stock_qty'] === 0;
            return $row;
        }, $stmt->fetchAll());

        return ['rows' => $rows, 'pagination' => $meta];
    }

    public function products(?string $category): array
    {
        return $this->productPage($category, null, null, 1, 1000)['rows'];
    }

    public function valueByCategory(): array
    {
        $rows = $this->db->query(
            'SELECT category, SUM(stock_qty * cost) value, SUM(stock_qty) units
             FROM products GROUP BY category ORDER BY value DESC'
        )->fetchAll();

        return array_map(static fn(array $row): array => [
            'category' => $row['category'],
            'value' => (float) $row['value'],
            'units' => (int) $row['units'],
        ], $rows);
    }

    public function categories(): array
    {
        return array_map(static fn(array $row): string => $row['category'],
            $this->db->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll());
    }

    public function lowStock(int $limit = 6): array
    {
        $stmt = $this->db->prepare(
            'SELECT name, category, stock_qty, reorder_level
             FROM products
             WHERE stock_qty <= reorder_level
             ORDER BY stock_qty ASC, name ASC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function productByIdWithStats(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch();

        if (!$product) {
            return null;
        }

        $statsStmt = $this->db->prepare(
            'SELECT COALESCE(SUM(qty), 0) sold_qty, COALESCE(SUM(line_total), 0) revenue
             FROM sales_items
             WHERE product_id = :id'
        );
        $statsStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $statsStmt->execute();
        $stats = $statsStmt->fetch();

        $soldQty = (int) $stats['sold_qty'];
        $revenue = (float) $stats['revenue'];
        $cogs = $soldQty * (float) $product['cost'];
        $profit = $revenue - $cogs;
        $marginPct = abs($revenue) < 0.00001 ? null : ($profit / $revenue) * 100;

        $product['sold_qty'] = $soldQty;
        $product['revenue'] = $revenue;
        $product['cogs'] = $cogs;
        $product['profit'] = $profit;
        $product['margin_pct'] = $marginPct;
        $product['low'] = (int) $product['stock_qty'] <= (int) $product['reorder_level'];
        $product['out'] = (int) $product['stock_qty'] === 0;

        return $product;
    }

    public function productSalesMonthly(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT substr(i.invoice_date, 1, 7) ym, SUM(si.qty) qty, SUM(si.line_total) revenue
             FROM sales_items si
             JOIN sales_invoices i ON i.id = si.invoice_id
             WHERE si.product_id = :id
             GROUP BY ym ORDER BY ym'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn(array $row): array => [
            'ym' => $row['ym'],
            'qty' => (int) $row['qty'],
            'revenue' => (float) $row['revenue'],
        ], $stmt->fetchAll());
    }

    public function productInvoices(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.invoice_no invoice_no, i.invoice_date invoice_date, si.qty qty, si.line_total line_total
             FROM sales_items si
             JOIN sales_invoices i ON i.id = si.invoice_id
             WHERE si.product_id = :id
             ORDER BY i.invoice_date DESC, i.id DESC'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn(array $row): array => [
            'invoice_no' => $row['invoice_no'],
            'invoice_date' => $row['invoice_date'],
            'qty' => (int) $row['qty'],
            'line_total' => (float) $row['line_total'],
        ], $stmt->fetchAll());
    }
}
