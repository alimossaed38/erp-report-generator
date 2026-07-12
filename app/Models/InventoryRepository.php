<?php

class InventoryRepository
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function summary(?string $category): array
    {
        $w = $category ? ' WHERE category = ?' : '';
        $p = $category ? [$category] : [];
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) items,
                    COALESCE(SUM(stock_qty * cost),0) value,
                    COALESCE(SUM(CASE WHEN stock_qty <= reorder_level THEN 1 ELSE 0 END),0) low
             FROM products$w"
        );
        $stmt->execute($p);
        $r = $stmt->fetch();
        return ['items' => (int)$r['items'], 'value' => (float)$r['value'], 'low' => (int)$r['low']];
    }

    public function products(?string $category): array
    {
        $w = $category ? ' WHERE category = ?' : '';
        $p = $category ? [$category] : [];
        $stmt = $this->db->prepare("SELECT * FROM products$w ORDER BY category, name");
        $stmt->execute($p);
        return array_map(function ($r) {
            $r['low'] = ((int)$r['stock_qty'] <= (int)$r['reorder_level']);
            return $r;
        }, $stmt->fetchAll());
    }

    public function valueByCategory(): array
    {
        $stmt = $this->db->query(
            "SELECT category, SUM(stock_qty * cost) value FROM products GROUP BY category ORDER BY value DESC"
        );
        return array_map(fn($r) => ['category' => $r['category'], 'value' => (float)$r['value']], $stmt->fetchAll());
    }

    public function categories(): array
    {
        return array_map(fn($r) => $r['category'],
            $this->db->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll());
    }
}
