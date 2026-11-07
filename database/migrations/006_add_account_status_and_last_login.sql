ALTER TABLE users ADD COLUMN status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','inactive'));
ALTER TABLE users ADD COLUMN last_login_at TEXT;
