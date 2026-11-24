CREATE TABLE IF NOT EXISTS site_feature_permissions (
    feature_key VARCHAR(40) PRIMARY KEY,
    is_enabled BOOLEAN NOT NULL DEFAULT FALSE
);

INSERT INTO site_feature_permissions (feature_key, is_enabled) VALUES
    ('news_create', TRUE),
    ('news_edit', TRUE),
    ('services_manage', TRUE),
    ('carousel_manage', FALSE),
    ('faq_manage', FALSE),
    ('about_manage', FALSE),
    ('navigation_manage', FALSE),
    ('appearance_manage', FALSE),
    ('contact_manage', FALSE),
    ('social_manage', FALSE)
ON CONFLICT (feature_key) DO NOTHING;

CREATE TABLE IF NOT EXISTS site_faq (
    id SERIAL PRIMARY KEY,
    question VARCHAR(300) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS site_about (
    id SMALLINT PRIMARY KEY CHECK (id = 1),
    title VARCHAR(150) NOT NULL DEFAULT 'Sobre a SEMED',
    body TEXT NOT NULL DEFAULT '',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO site_about (id) VALUES (1)
ON CONFLICT (id) DO NOTHING;

CREATE TABLE IF NOT EXISTS site_about_images (
    id SERIAL PRIMARY KEY,
    image_path TEXT NOT NULL,
    alt_text VARCHAR(180) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS site_navigation_links (
    id SERIAL PRIMARY KEY,
    location VARCHAR(10) NOT NULL CHECK (location IN ('header', 'footer')),
    label VARCHAR(80) NOT NULL,
    href VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0
);

INSERT INTO site_navigation_links (location, label, href, sort_order)
SELECT seed.location, seed.label, seed.href, seed.sort_order
FROM (VALUES
    ('header', 'Início', '/index.html', 1),
    ('header', 'Serviços', '/servicos.html', 2),
    ('header', 'Notícias', '/noticias.html', 3),
    ('header', 'Sobre', '/sobre.html', 4),
    ('header', 'FAQ', '/faq.html', 5),
    ('footer', 'Início', '/index.html', 1),
    ('footer', 'Serviços', '/servicos.html', 2),
    ('footer', 'Notícias', '/noticias.html', 3),
    ('footer', 'Sobre', '/sobre.html', 4),
    ('footer', 'FAQ', '/faq.html', 5),
    ('footer', 'Minha Conta', '/login.html', 6)
) AS seed(location, label, href, sort_order)
WHERE NOT EXISTS (
    SELECT 1
    FROM site_navigation_links current_link
    WHERE current_link.location = seed.location
      AND current_link.label = seed.label
      AND current_link.href = seed.href
);
