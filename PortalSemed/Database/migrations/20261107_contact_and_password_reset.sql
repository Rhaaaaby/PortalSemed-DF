ALTER TABLE site_settings
    ADD COLUMN IF NOT EXISTS contact_title VARCHAR(150) NOT NULL DEFAULT 'Secretaria Municipal de Educação',
    ADD COLUMN IF NOT EXISTS contact_address VARCHAR(255) NOT NULL DEFAULT 'Rua Principal, nº 123 – São Miguel do Tocantins',
    ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(40) NOT NULL DEFAULT '(63) 99999-9999',
    ADD COLUMN IF NOT EXISTS contact_email VARCHAR(100) NOT NULL DEFAULT 'educacao@saomiguel.to.gov.br',
    ADD COLUMN IF NOT EXISTS contact_hours VARCHAR(120) NOT NULL DEFAULT 'Seg - Sex | 08h às 17h';

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGSERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS password_reset_tokens_user_id_idx
    ON password_reset_tokens (user_id);
