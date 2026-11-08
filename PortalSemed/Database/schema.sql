-- ============================================
-- BANCO DE DADOS - PortalSemed
-- ============================================

-- ============================================
-- USUÁRIOS
-- ============================================

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    status BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    categoria VARCHAR(50),
    imagem VARCHAR(255),

    CONSTRAINT chk_user_role
        CHECK (role IN ('admin', 'funcionario'))
);


-- ============================================
-- POSTS
-- ============================================

CREATE TABLE posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    author_id INT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP,

    CONSTRAINT fk_author
        FOREIGN KEY (author_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);


-- ============================================
-- ARQUIVOS DOS POSTS
-- ============================================

CREATE TABLE files (
    id SERIAL PRIMARY KEY,
    post_id INT,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_post
        FOREIGN KEY (post_id)
        REFERENCES posts(id)
        ON DELETE CASCADE
);


-- ============================================
-- DOCUMENTOS
-- ============================================

CREATE TABLE documents (
    id SERIAL PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- PERSONALIZAÇÃO DO PORTAL
-- ============================================

CREATE TABLE site_settings (
    id SMALLINT PRIMARY KEY CHECK (id = 1),
    primary_color CHAR(7) NOT NULL DEFAULT '#2D4F43',
    accent_color CHAR(7) NOT NULL DEFAULT '#8EB34E',
    background_color CHAR(7) NOT NULL DEFAULT '#FDF2F2',
    text_color CHAR(7) NOT NULL DEFAULT '#333333',
    logo_path VARCHAR(255) NOT NULL DEFAULT '/images/logo.png',
    whatsapp_url VARCHAR(255) NOT NULL DEFAULT '',
    instagram_url VARCHAR(255) NOT NULL DEFAULT '',
    facebook_url VARCHAR(255) NOT NULL DEFAULT '',
    contact_title VARCHAR(150) NOT NULL DEFAULT 'Secretaria Municipal de Educação',
    contact_address VARCHAR(255) NOT NULL DEFAULT 'Rua Principal, nº 123 – São Miguel do Tocantins',
    contact_phone VARCHAR(40) NOT NULL DEFAULT '(63) 99999-9999',
    contact_email VARCHAR(100) NOT NULL DEFAULT 'educacao@saomiguel.to.gov.br',
    contact_hours VARCHAR(120) NOT NULL DEFAULT 'Seg - Sex | 08h às 17h',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO site_settings (id) VALUES (1)
ON CONFLICT (id) DO NOTHING;

CREATE TABLE site_services (
    slug VARCHAR(60) PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(300) NOT NULL,
    href VARCHAR(255) NOT NULL,
    icon VARCHAR(16) NOT NULL,
    is_public BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT NOT NULL DEFAULT 0
);

INSERT INTO site_services (slug, title, description, href, icon, sort_order) VALUES
    ('calendario', 'Calendário Escolar', 'Consulte datas de aulas, reuniões e eventos educacionais.', 'calendario.html', '📅', 1),
    ('documentos', 'Documentos', 'Baixe portarias, regulamentos e comunicados oficiais.', 'documentos.html', '📄', 2),
    ('transparencia', 'Transparência', 'Acesse os documentos públicos e informações de transparência.', '', '🔎', 3),
    ('fale-conosco', 'Fale Conosco', 'Entre em contato com a Secretaria para dúvidas e solicitações.', 'faleconosco.html', '💬', 4)
ON CONFLICT (slug) DO NOTHING;

CREATE TABLE site_carousel (
    id SERIAL PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(180) NOT NULL,
    caption VARCHAR(180) NOT NULL DEFAULT '',
    is_public BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_reset_tokens (
    id BIGSERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX password_reset_tokens_user_id_idx ON password_reset_tokens (user_id);