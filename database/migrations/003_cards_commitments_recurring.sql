CREATE TABLE credit_cards (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    institution TEXT NOT NULL DEFAULT '',
    brand TEXT NOT NULL DEFAULT '',
    credit_limit INTEGER NOT NULL CHECK(credit_limit>=0),
    closing_day INTEGER NOT NULL CHECK(closing_day BETWEEN 1 AND 28),
    due_day INTEGER NOT NULL CHECK(due_day BETWEEN 1 AND 28),
    payment_account_id INTEGER NOT NULL,
    color TEXT NOT NULL DEFAULT '#5478d4',
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    FOREIGN KEY(payment_account_id,user_id) REFERENCES accounts(id,user_id)
);
CREATE INDEX idx_credit_cards_user ON credit_cards(user_id,is_active);

CREATE TABLE invoices (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    card_id INTEGER NOT NULL,
    reference_month TEXT NOT NULL,
    closing_date TEXT NOT NULL,
    due_date TEXT NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('open','closed','paid')),
    paid_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    UNIQUE(user_id,card_id,reference_month),
    FOREIGN KEY(card_id,user_id) REFERENCES credit_cards(id,user_id)
);
CREATE INDEX idx_invoices_user_due ON invoices(user_id,due_date,status);

CREATE TABLE card_purchases (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    card_id INTEGER NOT NULL,
    category_id INTEGER,
    description TEXT NOT NULL,
    total_amount INTEGER NOT NULL CHECK(total_amount>0),
    purchase_date TEXT NOT NULL,
    installment_count INTEGER NOT NULL DEFAULT 1 CHECK(installment_count BETWEEN 1 AND 60),
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    FOREIGN KEY(card_id,user_id) REFERENCES credit_cards(id,user_id),
    FOREIGN KEY(category_id,user_id) REFERENCES categories(id,user_id)
);
CREATE INDEX idx_card_purchases_user_date ON card_purchases(user_id,purchase_date DESC);

CREATE TABLE invoice_items (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    invoice_id INTEGER NOT NULL,
    purchase_id INTEGER NOT NULL,
    installment_number INTEGER NOT NULL,
    total_installments INTEGER NOT NULL,
    amount INTEGER NOT NULL CHECK(amount>0),
    UNIQUE(purchase_id,installment_number),
    FOREIGN KEY(invoice_id,user_id) REFERENCES invoices(id,user_id),
    FOREIGN KEY(purchase_id,user_id) REFERENCES card_purchases(id,user_id)
);
CREATE INDEX idx_invoice_items_invoice ON invoice_items(user_id,invoice_id);

CREATE TABLE invoice_payments (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    invoice_id INTEGER NOT NULL,
    account_id INTEGER NOT NULL,
    amount INTEGER NOT NULL CHECK(amount>0),
    paid_at TEXT NOT NULL,
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    UNIQUE(user_id,invoice_id),
    FOREIGN KEY(invoice_id,user_id) REFERENCES invoices(id,user_id),
    FOREIGN KEY(account_id,user_id) REFERENCES accounts(id,user_id)
);
CREATE INDEX idx_invoice_payments_account ON invoice_payments(user_id,account_id,paid_at);

CREATE TABLE payables (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    account_id INTEGER NOT NULL,
    category_id INTEGER,
    description TEXT NOT NULL,
    amount INTEGER NOT NULL CHECK(amount>0),
    due_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','paid','cancelled')),
    paid_at TEXT,
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    FOREIGN KEY(account_id,user_id) REFERENCES accounts(id,user_id),
    FOREIGN KEY(category_id,user_id) REFERENCES categories(id,user_id)
);
CREATE INDEX idx_payables_user_due ON payables(user_id,due_date,status);

CREATE TABLE receivables (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    account_id INTEGER NOT NULL,
    category_id INTEGER,
    description TEXT NOT NULL,
    amount INTEGER NOT NULL CHECK(amount>0),
    due_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','received','cancelled')),
    received_at TEXT,
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    FOREIGN KEY(account_id,user_id) REFERENCES accounts(id,user_id),
    FOREIGN KEY(category_id,user_id) REFERENCES categories(id,user_id)
);
CREATE INDEX idx_receivables_user_due ON receivables(user_id,due_date,status);

CREATE TABLE recurring_transactions (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    account_id INTEGER NOT NULL,
    category_id INTEGER,
    type TEXT NOT NULL CHECK(type IN ('income','expense')),
    description TEXT NOT NULL,
    amount INTEGER NOT NULL CHECK(amount>0),
    frequency TEXT NOT NULL CHECK(frequency IN ('daily','weekly','monthly','yearly','custom')),
    interval_value INTEGER NOT NULL DEFAULT 1 CHECK(interval_value BETWEEN 1 AND 365),
    start_date TEXT NOT NULL,
    next_due_date TEXT NOT NULL,
    end_date TEXT,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','cancelled')),
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    FOREIGN KEY(account_id,user_id) REFERENCES accounts(id,user_id),
    FOREIGN KEY(category_id,user_id) REFERENCES categories(id,user_id)
);
CREATE INDEX idx_recurring_user_next ON recurring_transactions(user_id,status,next_due_date);

CREATE TABLE recurrence_occurrences (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    recurring_id INTEGER NOT NULL,
    transaction_id INTEGER NOT NULL UNIQUE REFERENCES transactions(id),
    due_date TEXT NOT NULL,
    created_at TEXT NOT NULL,
    UNIQUE(recurring_id,due_date),
    FOREIGN KEY(recurring_id,user_id) REFERENCES recurring_transactions(id,user_id)
);
CREATE INDEX idx_recurrence_occurrences_rule ON recurrence_occurrences(user_id,recurring_id,due_date);
