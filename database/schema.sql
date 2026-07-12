DROP TABLE IF EXISTS sales_items;
DROP TABLE IF EXISTS sales_invoices;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    category TEXT NOT NULL,
    price REAL NOT NULL,
    cost REAL NOT NULL,
    stock_qty INTEGER NOT NULL,
    reorder_level INTEGER NOT NULL
);

CREATE TABLE sales_invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_no TEXT NOT NULL,
    customer_name TEXT NOT NULL,
    invoice_date TEXT NOT NULL,
    total REAL NOT NULL
);

CREATE TABLE sales_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_id INTEGER NOT NULL REFERENCES sales_invoices(id),
    product_id INTEGER NOT NULL REFERENCES products(id),
    qty INTEGER NOT NULL,
    unit_price REAL NOT NULL,
    line_total REAL NOT NULL
);

CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL CHECK (type IN ('income','expense')),
    category TEXT NOT NULL,
    amount REAL NOT NULL,
    txn_date TEXT NOT NULL,
    description TEXT NOT NULL
);

CREATE INDEX idx_sales_invoices_date ON sales_invoices(invoice_date);
CREATE INDEX idx_sales_invoices_customer ON sales_invoices(customer_name);
CREATE INDEX idx_sales_items_invoice ON sales_items(invoice_id);
CREATE INDEX idx_sales_items_product ON sales_items(product_id);
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_products_stock ON products(stock_qty, reorder_level);
CREATE INDEX idx_transactions_date ON transactions(txn_date);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_category ON transactions(category);
