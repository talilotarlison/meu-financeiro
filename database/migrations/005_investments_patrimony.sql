ALTER TABLE transactions ADD COLUMN external_id TEXT NOT NULL DEFAULT '';
CREATE UNIQUE INDEX idx_transactions_external_id ON transactions(user_id,account_id,external_id) WHERE external_id<>'';

CREATE TABLE investments (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('tesouro','cdb','lci_lca','savings','stock','reit','etf','fund','crypto','pension','other')),
    institution TEXT NOT NULL DEFAULT '',
    quantity_micros INTEGER NOT NULL DEFAULT 0 CHECK(quantity_micros>=0),
    average_price INTEGER NOT NULL DEFAULT 0 CHECK(average_price>=0),
    invested_amount INTEGER NOT NULL CHECK(invested_amount>=0),
    market_value INTEGER NOT NULL CHECK(market_value>=0),
    application_date TEXT NOT NULL,
    notes TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id)
);
CREATE INDEX idx_investments_user_active ON investments(user_id,is_active,type);

CREATE TABLE investment_transactions (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    investment_id INTEGER NOT NULL,
    kind TEXT NOT NULL CHECK(kind IN ('application','redemption','income','valuation')),
    amount INTEGER NOT NULL CHECK(amount>0),
    quantity_micros INTEGER NOT NULL DEFAULT 0 CHECK(quantity_micros>=0),
    movement_date TEXT NOT NULL,
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    FOREIGN KEY(investment_id,user_id) REFERENCES investments(id,user_id)
);
CREATE INDEX idx_investment_transactions_history ON investment_transactions(user_id,investment_id,movement_date DESC,id DESC);

CREATE TABLE assets (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('cash','property','vehicle','other')),
    current_value INTEGER NOT NULL CHECK(current_value>=0),
    valuation_date TEXT NOT NULL,
    notes TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id)
);
CREATE INDEX idx_assets_user_active ON assets(user_id,is_active,type);

CREATE TABLE liabilities (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK(type IN ('loan','mortgage','debt','installment','other')),
    original_amount INTEGER NOT NULL CHECK(original_amount>=0),
    remaining_amount INTEGER NOT NULL CHECK(remaining_amount>=0 AND remaining_amount<=original_amount),
    due_date TEXT,
    notes TEXT NOT NULL DEFAULT '',
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id)
);
CREATE INDEX idx_liabilities_user_active ON liabilities(user_id,is_active,due_date);

CREATE TABLE net_worth_snapshots (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reference_month TEXT NOT NULL CHECK(length(reference_month)=7),
    assets_total INTEGER NOT NULL,
    liabilities_total INTEGER NOT NULL,
    net_worth INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    UNIQUE(user_id,reference_month)
);
