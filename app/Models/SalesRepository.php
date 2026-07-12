<?php

class SalesRepository
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    private function range(?string $from, ?string $to): array
    {
        $where = [];
        $params = [];
        if ($from) { $where[] = 'invoice_date >= ?'; $params[] = $from; }
        if ($to)   { $where[] = 'invoice_date <= ?'; $params[] = $to; }
        $sql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        return [$sql, $params];
    }

    public function summary(?string $from, ?string $to): array
    {
        [$w, $p] = $this->range($from, $to);
        $row = $this->db->prepare("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM sales_invoices$w");
        $row->execute($p);
        $r = $row->fetch();
        $count = (int) $r['c'];
        $total = (float) $r['t'];
        return ['total' => $total, 'count' => $count, 'avg' => $count ? $total / $count : 0.0];
    }

    public function monthly(?string $from, ?string $to): array
    {
        [$w, $p] = $this->range($from, $to);
        $stmt = $this->db->prepare(
            "SELECT substr(invoice_date,1,7) ym, SUM(total) total
             FROM sales_invoices$w GROUP BY ym ORDER BY ym"
        );
        $stmt->execute($p);
        return array_map(fn($r) => ['ym' => $r['ym'], 'total' => (float) $r['total']], $stmt->fetchAll());
    }

    public function topProducts(?string $from, ?string $to, int $limit = 10): array
    {
        $where = [];
        $params = [];
        if ($from) { $where[] = 'i.invoice_date >= ?'; $params[] = $from; }
        if ($to)   { $where[] = 'i.invoice_date <= ?'; $params[] = $to; }
        $w = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $params[] = $limit;
        $stmt = $this->db->prepare(
            "SELECT p.name name, SUM(si.qty) qty, SUM(si.line_total) revenue
             FROM sales_items si
             JOIN sales_invoices i ON i.id = si.invoice_id
             JOIN products p ON p.id = si.product_id$w
             GROUP BY p.id ORDER BY revenue DESC LIMIT ?"
        );
        $stmt->execute($params);
        return array_map(fn($r) => [
            'name' => $r['name'], 'qty' => (int) $r['qty'], 'revenue' => (float) $r['revenue']
        ], $stmt->fetchAll());
    }

    public function invoices(?string $from, ?string $to, int $limit = 50): array
    {
        [$w, $p] = $this->range($from, $to);
        $p[] = $limit;
        $stmt = $this->db->prepare(
            "SELECT invoice_no, customer_name, invoice_date, total
             FROM sales_invoices$w ORDER BY invoice_date DESC, id DESC LIMIT ?"
        );
        $stmt->execute($p);
        return $stmt->fetchAll();
    }
}
