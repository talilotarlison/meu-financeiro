CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL COLLATE NOCASE UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE user_settings (
    id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    currency TEXT NOT NULL DEFAULT 'BRL',
    date_format TEXT NOT NULL DEFAULT 'd/m/Y',
    theme TEXT NOT NULL DEFAULT 'light' CHECK(theme IN ('light','dark')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE INDEX idx_users_email ON users(email);
