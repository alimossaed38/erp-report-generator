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
            "SELECT invoice_no, customer_name, invoice_date, total, due_date, amount_paid
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

        $rows = array_map([self::class, 'withPaymentStatus'], $stmt->fetchAll());

        return ['rows' => $rows, 'pagination' => $meta];
    }

    private static function withPaymentStatus(array $row): array
    {
        $total = (float) $row['total'];
        $paid = (float) $row['amount_paid'];
        $outstanding = round($total - $paid, 2);

        $row['outstanding'] = $outstanding;
        $row['pay_status'] = match (true) {
            $outstanding <= 0.005 => 'paid',
            $paid <= 0.005 => 'unpaid',
            default => 'partial',
        };

        return $row;
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

    public function marginSummary(?string $from, ?string $to, ?string $search = null): array
    {
        [$where, $params] = $this->filters($from, $to, $search);
        $revenueStmt = $this->db->prepare("SELECT COALESCE(SUM(total), 0) revenue FROM sales_invoices$where");
        $revenueStmt->execute($params);
        $revenue = (float) $revenueStmt->fetchColumn();

        [$whereJoined, $paramsJoined] = $this->filters($from, $to, $search, 'i');
        $cogsStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(si.qty * p.cost), 0) cogs
             FROM sales_items si
             JOIN sales_invoices i ON i.id = si.invoice_id
             JOIN products p ON p.id = si.product_id$whereJoined"
        );
        $cogsStmt->execute($paramsJoined);
        $cogs = (float) $cogsStmt->fetchColumn();

        $profit = $revenue - $cogs;
        $marginPct = abs($revenue) < 0.00001 ? null : ($profit / $revenue) * 100;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'profit' => $profit,
            'margin_pct' => $marginPct,
        ];
    }

    public function monthlyMargin(?string $from, ?string $to): array
    {
        [$where, $params] = $this->filters($from, $to);
        $revenueStmt = $this->db->prepare(
            "SELECT substr(invoice_date, 1, 7) ym, SUM(total) revenue
             FROM sales_invoices$where
             GROUP BY ym"
        );
        $revenueStmt->execute($params);
        $revenueByYm = [];
        foreach ($revenueStmt->fetchAll() as $row) {
            $revenueByYm[$row['ym']] = (float) $row['revenue'];
        }

        [$whereJoined, $paramsJoined] = $this->filters($from, $to, null, 'i');
        $cogsStmt = $this->db->prepare(
            "SELECT substr(i.invoice_date, 1, 7) ym, SUM(si.qty * p.cost) cogs
             FROM sales_items si
             JOIN sales_invoices i ON i.id = si.invoice_id
             JOIN products p ON p.id = si.product_id$whereJoined
             GROUP BY ym"
        );
        $cogsStmt->execute($paramsJoined);
        $cogsByYm = [];
        foreach ($cogsStmt->fetchAll() as $row) {
            $cogsByYm[$row['ym']] = (float) $row['cogs'];
        }

        $yms = array_unique(array_merge(array_keys($revenueByYm), array_keys($cogsByYm)));
        sort($yms);

        return array_map(static function (string $ym) use ($revenueByYm, $cogsByYm): array {
            $revenue = $revenueByYm[$ym] ?? 0.0;
            $cogs = $cogsByYm[$ym] ?? 0.0;

            return [
                'ym' => $ym,
                'revenue' => $revenue,
                'cogs' => $cogs,
                'profit' => $revenue - $cogs,
            ];
        }, $yms);
    }

    public function outstanding(?string $from, ?string $to): array
    {
        [$where, $params] = $this->filters($from, $to);
        $asOf = $this->dateBounds()['max'];

        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN (total - amount_paid) > 0 THEN (total - amount_paid) ELSE 0 END), 0) total_outstanding,
                    COALESCE(SUM(CASE WHEN due_date < :asOf AND (total - amount_paid) > 0 THEN (total - amount_paid) ELSE 0 END), 0) overdue,
                    COALESCE(SUM(CASE WHEN (total - amount_paid) > 0 THEN 1 ELSE 0 END), 0) invoice_count
                FROM sales_invoices$where";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':asOf', $asOf);
        $stmt->execute();
        $row = $stmt->fetch();

        return [
            'total_outstanding' => (float) $row['total_outstanding'],
            'overdue' => (float) $row['overdue'],
            'invoice_count' => (int) $row['invoice_count'],
        ];
    }

    public function agingInvoices(): array
    {
        $stmt = $this->db->query(
            "SELECT invoice_no, customer_name, invoice_date, due_date, total, amount_paid
             FROM sales_invoices
             WHERE (total - amount_paid) > 0
             ORDER BY due_date ASC"
        );

        return array_map(static function (array $row): array {
            $row['outstanding'] = round((float) $row['total'] - (float) $row['amount_paid'], 2);
            return $row;
        }, $stmt->fetchAll());
    }

    public function customerReport(
        ?string $from,
        ?string $to,
        ?string $search,
        int $page,
        int $perPage,
        string $sort = 'revenue',
        string $direction = 'desc'
    ): array {
        [$where, $params] = $this->filters($from, $to, $search);
        $sortMap = [
            'revenue' => 'revenue',
            'invoices' => 'invoices',
            'last_purchase' => 'last_purchase',
            'customer_name' => 'customer_name',
        ];
        $orderBy = $sortMap[$sort] ?? 'revenue';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        $count = $this->db->prepare("SELECT COUNT(DISTINCT customer_name) FROM sales_invoices$where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $meta = Report::pagination($total, $page, $perPage);
        $offset = ($meta['page'] - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT customer_name,
                    COUNT(*) invoices,
                    COALESCE(SUM(total), 0) revenue,
                    COALESCE(AVG(total), 0) avg,
                    MAX(invoice_date) last_purchase,
                    COALESCE(SUM(total - amount_paid), 0) outstanding
             FROM sales_invoices$where
             GROUP BY customer_name
             ORDER BY $orderBy $direction, customer_name ASC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = array_map(static fn(array $row): array => [
            'customer_name' => $row['customer_name'],
            'invoices' => (int) $row['invoices'],
            'revenue' => (float) $row['revenue'],
            'avg' => (float) $row['avg'],
            'last_purchase' => $row['last_purchase'],
            'outstanding' => round((float) $row['outstanding'], 2),
        ], $stmt->fetchAll());

        return ['rows' => $rows, 'pagination' => $meta];
    }

    public function customerDetail(string $name): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) invoices,
                    COALESCE(SUM(total), 0) revenue,
                    COALESCE(AVG(total), 0) avg,
                    MIN(invoice_date) first_purchase,
                    MAX(invoice_date) last_purchase,
                    COALESCE(SUM(total - amount_paid), 0) outstanding
             FROM sales_invoices
             WHERE customer_name = :name"
        );
        $stmt->bindValue(':name', $name);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row || (int) $row['invoices'] === 0) {
            return null;
        }

        return [
            'customer_name' => $name,
            'invoices' => (int) $row['invoices'],
            'revenue' => (float) $row['revenue'],
            'avg' => (float) $row['avg'],
            'first_purchase' => $row['first_purchase'],
            'last_purchase' => $row['last_purchase'],
            'outstanding' => round((float) $row['outstanding'], 2),
        ];
    }

    public function customerInvoices(string $name): array
    {
        $stmt = $this->db->prepare(
            "SELECT invoice_no, invoice_date, due_date, total, amount_paid
             FROM sales_invoices
             WHERE customer_name = :name
             ORDER BY invoice_date DESC, id DESC"
        );
        $stmt->bindValue(':name', $name);
        $stmt->execute();

        return array_map([self::class, 'withPaymentStatus'], $stmt->fetchAll());
    }

    public function customerMonthly(string $name): array
    {
        $stmt = $this->db->prepare(
            "SELECT substr(invoice_date, 1, 7) ym, SUM(total) total
             FROM sales_invoices
             WHERE customer_name = :name
             GROUP BY ym ORDER BY ym"
        );
        $stmt->bindValue(':name', $name);
        $stmt->execute();

        return array_map(static fn(array $row): array => [
            'ym' => $row['ym'],
            'total' => (float) $row['total'],
        ], $stmt->fetchAll());
    }

    public function invoiceByNo(string $no): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, invoice_no, customer_name, invoice_date, due_date, total, amount_paid
             FROM sales_invoices
             WHERE invoice_no = :no"
        );
        $stmt->bindValue(':no', $no);
        $stmt->execute();
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row = self::withPaymentStatus($row);
        $row['id'] = (int) $row['id'];

        return $row;
    }

    public function invoiceItems(int $invoiceId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.name name, si.qty qty, si.unit_price unit_price, si.line_total line_total
             FROM sales_items si
             JOIN products p ON p.id = si.product_id
             WHERE si.invoice_id = :id
             ORDER BY si.id ASC"
        );
        $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn(array $row): array => [
            'name' => $row['name'],
            'qty' => (int) $row['qty'],
            'unit_price' => (float) $row['unit_price'],
            'line_total' => (float) $row['line_total'],
        ], $stmt->fetchAll());
    }
}
