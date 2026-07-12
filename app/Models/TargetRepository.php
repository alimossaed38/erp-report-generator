<?php

final class TargetRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT period, sales_target FROM targets ORDER BY period');
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['period']] = (float) $row['sales_target'];
        }

        return $result;
    }

    public function forPeriod(string $period): ?float
    {
        $stmt = $this->db->prepare('SELECT sales_target FROM targets WHERE period = :period');
        $stmt->bindValue(':period', $period);
        $stmt->execute();
        $value = $stmt->fetchColumn();

        return $value === false ? null : (float) $value;
    }

    public function range(?string $from, ?string $to): float
    {
        $where = [];
        $params = [];

        if ($from) {
            $where[] = 'period >= :a';
            $params[':a'] = substr($from, 0, 7);
        }
        if ($to) {
            $where[] = 'period <= :b';
            $params[':b'] = substr($to, 0, 7);
        }

        $sql = 'SELECT COALESCE(SUM(sales_target), 0) FROM targets'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (float) $stmt->fetchColumn();
    }
}
