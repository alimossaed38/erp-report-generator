<?php

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException('امتداد PDO_SQLite غير مفعّل. فعّل extension=pdo_sqlite في php.ini ثم أعد تشغيل الخادم.');
        }

        $dir = __DIR__ . '/../../database';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('تعذر إنشاء مجلد قاعدة البيانات.');
        }

        $path = $dir . '/erp.sqlite';
        self::$pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        self::$pdo->exec('PRAGMA busy_timeout = 5000');
        self::$pdo->exec('PRAGMA journal_mode = WAL');
        self::ensureIndexes(self::$pdo);

        return self::$pdo;
    }

    private static function ensureIndexes(PDO $db): void
    {
        $tableCount = (int) $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name IN ('products','sales_invoices','sales_items','transactions')")->fetchColumn();
        if ($tableCount < 4) {
            return;
        }

        $indexes = [
            'CREATE INDEX IF NOT EXISTS idx_sales_invoices_date ON sales_invoices(invoice_date)',
            'CREATE INDEX IF NOT EXISTS idx_sales_invoices_customer ON sales_invoices(customer_name)',
            'CREATE INDEX IF NOT EXISTS idx_sales_items_invoice ON sales_items(invoice_id)',
            'CREATE INDEX IF NOT EXISTS idx_sales_items_product ON sales_items(product_id)',
            'CREATE INDEX IF NOT EXISTS idx_products_category ON products(category)',
            'CREATE INDEX IF NOT EXISTS idx_products_stock ON products(stock_qty, reorder_level)',
            'CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions(txn_date)',
            'CREATE INDEX IF NOT EXISTS idx_transactions_type ON transactions(type)',
            'CREATE INDEX IF NOT EXISTS idx_transactions_category ON transactions(category)',
        ];
        foreach ($indexes as $sql) {
            $db->exec($sql);
        }
    }
}
