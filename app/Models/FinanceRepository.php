<?php

final class FinanceRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    private function filters(
        ?string $from,
        ?string $to,
        ?string $type = null,
        ?string $category = null,
        ?string $search = null
    ): array {
        $where = [];
        $params = [];

        if ($from) {
            $where[] = 'txn_date >= :from';
            $params[':from'] = $from;
        }
        if ($to) {
            $where[] = 'txn_date <= :to';
            $params[':to'] = $to;
        }
        if ($type) {
            $where[] = 'type = :type';
            $params[':type'] = $type;
        }
        if ($category) {
            $where[] = 'category = :category';
            $params[':category'] = $category;
        }
        if ($search) {
            $where[] = '(description LIKE :search OR category LIKE :search)';
            $params[':search'] = '%' . $search . '%';
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    public function summary(
        ?string $from,
        ?string $to,
        ?string $type = null,
        ?string $category = null,
        ?string $search = null
    ): array {
        [$where, $params] = $this->filters($from, $to, $type, $category, $search);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) income,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) expense,
                    COUNT(*) count
             FROM transactions$where"
        );
        $stmt->execute($params);
        $row = $stmt->fetch();
        $income = (float) $row['income'];
        $expense = (float) $row['expense'];

        return [
            'income' => $income,
            'expense' => $expense,
            'net' => $income - $expense,
            'count' => (int) $row['count'],
            'margin' => $income > 0 ? (($income - $expense) / $income) * 100 : 0,
        ];
    }

    public function monthly(?string $from, ?string $to): array
    {
        [$where, $params] = $this->filters($from, $to);
        $stmt = $this->db->prepare(
            "SELECT substr(txn_date, 1, 7) ym,
                    COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) income,
                    COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) expense
             FROM transactions$where
             GROUP BY ym ORDER BY ym"
        );
        $stmt->execute($params);

        return array_map(static fn(array $row): array => [
            'ym' => $row['ym'],
            'income' => (float) $row['income'],
            'expense' => (float) $row['expense'],
            'net' => (float) $row['income'] - (float) $row['expense'],
            'series' => [
                'income' => (float) $row['income'],
                'expense' => (float) $row['expense'],
            ],
        ], $stmt->fetchAll());
    }

    public function transactionPage(
        ?string $from,
        ?string $to,
        ?string $type,
        ?string $category,
        ?string $search,
        int $page,
        int $perPage,
        string $sort = 'txn_date',
        string $direction = 'desc'
    ): array {
        [$where, $params] = $this->filters($from, $to, $type, $category, $search);
        $sortMap = [
            'txn_date' => 'txn_date',
            'type' => 'type',
            'category' => 'category',
            'amount' => 'amount',
        ];
        $orderBy = $sortMap[$sort] ?? 'txn_date';
        $direction = strtolower($direction) === 'asc' ? 'ASC' : 'DESC';

        $count = $this->db->prepare("SELECT COUNT(*) FROM transactions$where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $meta = Report::pagination($total, $page, $perPage);
        $offset = ($meta['page'] - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT txn_date, type, category, amount, description
             FROM transactions$where
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

    public function transactions(?string $from, ?string $to, int $limit = 50): array
    {
        return $this->transactionPage($from, $to, null, null, null, 1, $limit)['rows'];
    }

    public function categories(): array
    {
        return array_map(static fn(array $row): string => $row['category'],
            $this->db->query('SELECT DISTINCT category FROM transactions ORDER BY category')->fetchAll());
    }

    public function expenseByCategory(?string $from, ?string $to, int $limit = 8): array
    {
        [$where, $params] = $this->filters($from, $to, 'expense');
        $stmt = $this->db->prepare(
            "SELECT category, SUM(amount) total
             FROM transactions$where
             GROUP BY category ORDER BY total DESC LIMIT :limit"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static fn(array $row): array => [
            'category' => $row['category'],
            'total' => (float) $row['total'],
        ], $stmt->fetchAll());
    }

    public function dateBounds(): array
    {
        $row = $this->db->query('SELECT MIN(txn_date) min_date, MAX(txn_date) max_date FROM transactions')->fetch();
        return ['min' => $row['min_date'] ?? null, 'max' => $row['max_date'] ?? null];
    }
}
