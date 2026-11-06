ALTER TABLE site_settings
    ADD COLUMN IF NOT EXISTS whatsapp_url VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS instagram_url VARCHAR(255) NOT NULL DEFAULT '',
    ADD COLUMN IF NOT EXISTS facebook_url VARCHAR(255) NOT NULL DEFAULT '';

INSERT INTO site_services (slug, title, description, href, icon, is_public, sort_order) VALUES
    ('transparencia', 'Transparência', 'Acesse os documentos públicos e informações de transparência.', '', '🔎', TRUE, 3)
ON CONFLICT (slug) DO NOTHING;

UPDATE site_services
SET href = ''
WHERE slug = 'transparencia' AND href = 'transparencia.html';
