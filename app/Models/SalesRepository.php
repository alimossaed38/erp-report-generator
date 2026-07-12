<?php

final class SalesRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    private function filters(?string $from, ?string $to, ?string $search = null, string $alias = ''): array
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $where = [];
        $params = [];

        if ($from) {
            $where[] = $prefix . 'invoice_date >= :from';
            $params[':from'] = $from;
        }
        if ($to) {
            $where[] = $prefix . 'invoice_date <= :to';
            $params[':to'] = $to;
        }
        if ($search) {
            $where[] = '(' . $prefix . 'invoice_no LIKE :search OR ' . $prefix . 'customer_name LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    public function summary(?string $from, ?string $to, ?string $search = null): array
    {
        [$where, $params] = $this->filters($from, $to, $search);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) count,
                    COALESCE(SUM(total), 0) total,
                    COALESCE(AVG(total), 0) average,
                    COUNT(DISTINCT customer_name) customers
             FROM sales_invoices$where"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();

        return [
            'total' => (float) $row['total'],
            'count' => (int) $row['count'],
            'avg' => (float) $row['average'],
            'customers' => (int) $row['customers'],
        ];
    }

    public function monthly(?string $from, ?string $to): array
    {
        [$where, $params] = $this->filters($from, $to);
        $stmt = $this->db->prepare(
            "SELECT substr(invoice_date, 1, 7) ym, SUM(total) total, COUNT(*) invoices
             FROM sales_invoices$where
             GROUP BY ym ORDER BY ym"
        );
        $stmt->execute($params);

        return array_map(static fn(array $row): array => [
            'ym' => $row['ym'],
            'total' => (float) $row['total'],
            'invoices' => (int) $row['invoices'],
        ], $stmt->fetchAll());
    }

    public function topProducts(?string $from, ?string $to, int $limit = 10): array
    {
        [$where, $params] = $this->filters($from, $to, null, 'i');
        $stmt = $this->db->prepare(
            "SELECT p.name, p.category, SUM(si.qty) qty, SUM(si.line_total) revenue
             FROM sales_items si
             JOIN sales_invoices i ON i.id = si.invoice_id
             JOIN products p ON p.id = si.product_id$where
             GROUP BY p.id
             ORDER BY revenue DESC
             LIMIT :limit"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn(array $row): array => [
            'name' => $row['name'],
            'category' => $row['category'],
            'qty' => (int) $row['qty'],
            'revenue' => (float) $row['revenue'],
        ], $stmt->fetchAll());
    }

    public function invoicePage(
        ?string $from,
        ?string $to,
        ?string $search,
        int $page,
        int $perPage,
        string $sort = 'invoice_date',
        string $direction = 'desc'
    ): array {
        [$where, $params] = $this->filters($from, $to, $search);
        $sortMap = [
            'invoice_no' => 'invoice_no',
            'customer' => 'customer_name',
            'invoice_date' => 'invoice_date',
            'total' => 'total',
        ];
        $orderBy = $sortMap[$sort] ?? 'invoice_date';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        $count = $this->db->prepare("SELECT COUNT(*) FROM sales_invoices$where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $meta = Report::pagination($total, $page, $perPage);
        $offset = ($meta['page'] - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT invoice_no, customer_name, invoice_date, total
             FROM sales_invoices$where
             ORDER BY $orderBy $direction, id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'pagination' => $meta];
    }

    public function invoices(?string $from, ?string $to, int $limit = 50): array
    {
        return $this->invoicePage($from, $to, null, 1, $limit)['rows'];
    }

    public function dateBounds(): array
    {
        $row = $this->db->query('SELECT MIN(invoice_date) min_date, MAX(invoice_date) max_date FROM sales_invoices')->fetch();
        return ['min' => $row['min_date'] ?? null, 'max' => $row['max_date'] ?? null];
    }
}
