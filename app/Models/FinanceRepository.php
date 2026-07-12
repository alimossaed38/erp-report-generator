<?php

class FinanceRepository
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    private function range(?string $from, ?string $to): array
    {
        $where = [];
        $params = [];
        if ($from) { $where[] = 'txn_date >= ?'; $params[] = $from; }
        if ($to)   { $where[] = 'txn_date <= ?'; $params[] = $to; }
        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    public function summary(?string $from, ?string $to): array
    {
        [$w, $p] = $this->range($from, $to);
        $stmt = $this->db->prepare(
            "SELECT
               COALESCE(SUM(CASE WHEN type='income' THEN amount END),0) income,
               COALESCE(SUM(CASE WHEN type='expense' THEN amount END),0) expense
             FROM transactions$w"
        );
        $stmt->execute($p);
        $r = $stmt->fetch();
        $income = (float)$r['income']; $expense = (float)$r['expense'];
        return ['income' => $income, 'expense' => $expense, 'net' => $income - $expense];
    }

    public function monthly(?string $from, ?string $to): array
    {
        [$w, $p] = $this->range($from, $to);
        $stmt = $this->db->prepare(
            "SELECT substr(txn_date,1,7) ym,
                    COALESCE(SUM(CASE WHEN type='income' THEN amount END),0) income,
                    COALESCE(SUM(CASE WHEN type='expense' THEN amount END),0) expense
             FROM transactions$w GROUP BY ym ORDER BY ym"
        );
        $stmt->execute($p);
        return array_map(fn($r) => [
            'ym' => $r['ym'],
            'series' => ['income' => (float)$r['income'], 'expense' => (float)$r['expense']],
        ], $stmt->fetchAll());
    }

    public function transactions(?string $from, ?string $to, int $limit = 50): array
    {
        [$w, $p] = $this->range($from, $to);
        $p[] = $limit;
        $stmt = $this->db->prepare(
            "SELECT txn_date, type, category, amount, description
             FROM transactions$w ORDER BY txn_date DESC, id DESC LIMIT ?"
        );
        $stmt->execute($p);
        return $stmt->fetchAll();
    }
}
