CREATE TABLE budgets (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    limit_amount INTEGER NOT NULL CHECK(limit_amount>0),
    reference_month TEXT NOT NULL CHECK(length(reference_month)=7),
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0,1)),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    UNIQUE(user_id,category_id,reference_month),
    FOREIGN KEY(category_id,user_id) REFERENCES categories(id,user_id)
);
CREATE INDEX idx_budgets_user_month ON budgets(user_id,reference_month,is_active);

CREATE TABLE budget_alert_levels (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    budget_id INTEGER NOT NULL,
    percentage INTEGER NOT NULL CHECK(percentage BETWEEN 1 AND 1000),
    created_at TEXT NOT NULL,
    UNIQUE(user_id,budget_id,percentage),
    FOREIGN KEY(budget_id,user_id) REFERENCES budgets(id,user_id)
);
CREATE INDEX idx_budget_alert_levels_budget ON budget_alert_levels(user_id,budget_id,percentage);

CREATE TABLE goals (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    target_amount INTEGER NOT NULL CHECK(target_amount>0),
    start_date TEXT NOT NULL,
    due_date TEXT,
    account_id INTEGER,
    category_id INTEGER,
    color TEXT NOT NULL DEFAULT '#168b68',
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','completed','cancelled')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(id,user_id),
    FOREIGN KEY(account_id,user_id) REFERENCES accounts(id,user_id),
    FOREIGN KEY(category_id,user_id) REFERENCES categories(id,user_id)
);
CREATE INDEX idx_goals_user_status ON goals(user_id,status,due_date);

CREATE TABLE goal_contributions (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    goal_id INTEGER NOT NULL,
    amount INTEGER NOT NULL CHECK(amount>0),
    direction TEXT NOT NULL CHECK(direction IN ('contribution','withdrawal')),
    movement_date TEXT NOT NULL,
    notes TEXT NOT NULL DEFAULT '',
    transfer_id INTEGER REFERENCES transfers(id),
    created_at TEXT NOT NULL,
    FOREIGN KEY(goal_id,user_id) REFERENCES goals(id,user_id)
);
CREATE INDEX idx_goal_contributions_history ON goal_contributions(user_id,goal_id,movement_date DESC,id DESC);
