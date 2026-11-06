ALTER TABLE users
    ADD COLUMN IF NOT EXISTS email VARCHAR(100);

CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique_idx
    ON users (LOWER(email))
    WHERE email IS NOT NULL AND BTRIM(email) <> '';
