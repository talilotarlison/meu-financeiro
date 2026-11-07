CREATE TABLE accounts (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('checking','savings','cash','digital','payment','investment','other')),
    institution TEXT NOT NULL DEFAULT '',
    opening_balance INTEGER NOT NULL DEFAULT 0,
    opening_date TEXT NOT NULL,
    color TEXT NOT NULL DEFAULT '#168b68',
    description TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id)
);
CREATE INDEX idx_accounts_user_active ON accounts(user_id,is_active);

CREATE TABLE categories (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('income','expense')),
    color TEXT NOT NULL DEFAULT '#78909c',
    icon TEXT NOT NULL DEFAULT '◈',
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    UNIQUE(user_id,type,name)
);
CREATE INDEX idx_categories_user_type ON categories(user_id,type,is_active);

CREATE TABLE transactions (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    account_id INTEGER NOT NULL,
    category_id INTEGER,
    type TEXT NOT NULL CHECK(type IN ('income','expense')),
    description TEXT NOT NULL,
    amount INTEGER NOT NULL CHECK(amount > 0),
    transaction_date TEXT NOT NULL,
    due_date TEXT,
    status TEXT NOT NULL CHECK(status IN ('pending','paid','received','cancelled')),
    payment_method TEXT NOT NULL DEFAULT '',
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(account_id,user_id) REFERENCES accounts(id,user_id),
    FOREIGN KEY(category_id,user_id) REFERENCES categories(id,user_id)
);
CREATE INDEX idx_transactions_user_date ON transactions(user_id,transaction_date DESC,id DESC);
CREATE INDEX idx_transactions_account_date ON transactions(user_id,account_id,transaction_date DESC);
CREATE INDEX idx_transactions_category_date ON transactions(user_id,category_id,transaction_date);
CREATE INDEX idx_transactions_user_status_date ON transactions(user_id,status,transaction_date);

CREATE TABLE transfers (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    source_account_id INTEGER NOT NULL,
    destination_account_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    amount INTEGER NOT NULL CHECK(amount > 0),
    transfer_date TEXT NOT NULL,
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    CHECK(source_account_id <> destination_account_id),
    FOREIGN KEY(source_account_id,user_id) REFERENCES accounts(id,user_id),
    FOREIGN KEY(destination_account_id,user_id) REFERENCES accounts(id,user_id)
);
CREATE INDEX idx_transfers_user_date ON transfers(user_id,transfer_date DESC,id DESC);

INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Salário', 'income', '#168b68', '↗', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Freelance', 'income', '#168b68', '↗', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Outros', 'income', '#78909c', '◈', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Alimentação', 'expense', '#d7854d', '◈', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Moradia', 'expense', '#5478d4', '⌂', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Transporte', 'expense', '#8b72c3', '↗', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Saúde', 'expense', '#d46d75', '✚', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Educação', 'expense', '#548caa', '▤', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Lazer', 'expense', '#a077bb', '☆', datetime('now'), datetime('now') FROM users;
INSERT INTO categories(user_id,name,type,color,icon,created_at,updated_at)
SELECT id, 'Outros', 'expense', '#78909c', '◈', datetime('now'), datetime('now') FROM users;
