<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class PortalCustomization
{
    private PDO $pdo;

    public const FEATURE_LABELS = [
        'news_create' => 'Criar notícias',
        'news_edit' => 'Editar e remover notícias',
        'services_manage' => 'Gerenciar serviços',
        'carousel_manage' => 'Adicionar e remover fotos do carrossel',
        'faq_manage' => 'Gerenciar FAQ',
        'about_manage' => 'Editar a página Sobre e suas fotos',
        'navigation_manage' => 'Gerenciar links do cabeçalho e rodapé',
        'appearance_manage' => 'Editar cores e logo do portal',
        'contact_manage' => 'Editar informações de contato',
        'social_manage' => 'Editar links de redes sociais',
    ];

    public function __construct()
    {
        $this->pdo = Connection::connect();
    }

    // RETORNA AS CONFIGURAÇÕES DO PORTAL
    public function settings(): array
    {
        $stmt = $this->pdo->query(
            'SELECT primary_color, accent_color, background_color, text_color, logo_path,
                    whatsapp_url, instagram_url, facebook_url,
                    contact_title, contact_address, contact_phone, contact_email, contact_hours
             FROM site_settings
             WHERE id = 1'
        );
        $settings = $stmt->fetch();

        // Retorna as configurações do portal ou valores padrão se não houver configurações salvas.
        return $settings ?: [
            'primary_color' => '#2D4F43',
            'accent_color' => '#8EB34E',
            'background_color' => '#FDF2F2',
            'text_color' => '#333333',
            'logo_path' => '/images/logo.png',
            'whatsapp_url' => '',
            'instagram_url' => '',
            'facebook_url' => '',
            'contact_title' => 'Secretaria Municipal de Educação',
            'contact_address' => 'Rua Principal, nº 123 – São Miguel do Tocantins',
            'contact_phone' => '(63) 99999-9999',
            'contact_email' => 'educacao@saomiguel.to.gov.br',
            'contact_hours' => 'Seg - Sex | 08h às 17h',
        ];
    }

    public function featurePermissions(): array
    {
        $stmt = $this->pdo->query('SELECT feature_key, is_enabled FROM site_feature_permissions');
        $permissions = array_fill_keys(array_keys(self::FEATURE_LABELS), false);
        foreach ($stmt->fetchAll() as $row) {
            if (array_key_exists($row['feature_key'], $permissions)) {
                $permissions[$row['feature_key']] = in_array($row['is_enabled'], [true, 1, '1', 't', 'true'], true);
            }
        }

        return $permissions;
    }

    public function featureEnabled(string $feature): bool
    {
        if (!array_key_exists($feature, self::FEATURE_LABELS)) {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT is_enabled FROM site_feature_permissions WHERE feature_key = :feature');
        $stmt->execute([':feature' => $feature]);
        return in_array($stmt->fetchColumn(), [true, 1, '1', 't', 'true'], true);
    }

    public function updateFeaturePermissions(array $permissions): bool
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO site_feature_permissions (feature_key, is_enabled)
                 VALUES (:feature_key, :is_enabled)
                 ON CONFLICT (feature_key) DO UPDATE SET is_enabled = EXCLUDED.is_enabled'
            );
            foreach ($permissions as $feature => $enabled) {
                $stmt->bindValue(':feature_key', $feature, PDO::PARAM_STR);
                $stmt->bindValue(':is_enabled', $enabled, PDO::PARAM_BOOL);
                $stmt->execute();
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }

    public function publicFaq(): array
    {
        return $this->pdo->query(
            'SELECT id, question, answer FROM site_faq ORDER BY sort_order, id'
        )->fetchAll();
    }

    public function replaceFaq(array $items): bool
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('DELETE FROM site_faq');
            $stmt = $this->pdo->prepare(
                'INSERT INTO site_faq (question, answer, sort_order)
                 VALUES (:question, :answer, :sort_order)'
            );
            foreach ($items as $index => $item) {
                $stmt->execute([
                    ':question' => $item['question'],
                    ':answer' => $item['answer'],
                    ':sort_order' => $index,
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }

    public function aboutContent(): array
    {
        $about = $this->pdo->query('SELECT title, body FROM site_about WHERE id = 1')->fetch();
        return [
            'title' => $about['title'] ?? 'Sobre a SEMED',
            'body' => $about['body'] ?? '',
            'images' => $this->pdo->query(
                'SELECT id, image_path, alt_text FROM site_about_images ORDER BY sort_order, id'
            )->fetchAll(),
        ];
    }

    public function updateAbout(string $title, string $body): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO site_about (id, title, body, updated_at)
             VALUES (1, :title, :body, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO UPDATE SET
                 title = EXCLUDED.title, body = EXCLUDED.body, updated_at = CURRENT_TIMESTAMP'
        );
        return $stmt->execute([':title' => $title, ':body' => $body]);
    }

    public function addAboutImage(string $path, string $altText): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO site_about_images (image_path, alt_text, sort_order)
             VALUES (:image_path, :alt_text, COALESCE((SELECT MAX(sort_order) + 1 FROM site_about_images), 0))'
        );
        return $stmt->execute([':image_path' => $path, ':alt_text' => $altText]);
    }

    public function deleteAboutImage(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM site_about_images WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function navigationLinks(): array
    {
        return $this->pdo->query(
            'SELECT id, location, label, href FROM site_navigation_links ORDER BY location, sort_order, id'
        )->fetchAll();
    }

    public function replaceNavigationLinks(array $links): bool
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('DELETE FROM site_navigation_links');
            $stmt = $this->pdo->prepare(
                'INSERT INTO site_navigation_links (location, label, href, sort_order)
                 VALUES (:location, :label, :href, :sort_order)'
            );
            $orders = ['header' => 0, 'footer' => 0];
            foreach ($links as $link) {
                $location = $link['location'];
                $stmt->execute([
                    ':location' => $location,
                    ':label' => $link['label'],
                    ':href' => $link['href'],
                    ':sort_order' => $orders[$location]++,
                ]);
            }
            $this->pdo->commit();
            return true;
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }

    // RETORNA OS SERVIÇOS PÚBLICOS DO PORTAL
    public function publicServices(): array
    {
        $stmt = $this->pdo->query(
            'SELECT slug, title, description, href, icon, is_public, sort_order
             FROM site_services
             WHERE is_public = TRUE AND href <> \'\'
             ORDER BY sort_order, title'
        );

        return $stmt->fetchAll();
    }

    // RETORNA TODOS OS SERVIÇOS DO PORTAL, INCLUINDO OS NÃO PÚBLICOS
    public function allServices(): array
    {
        $stmt = $this->pdo->query(
            'SELECT slug, title, description, href, icon, is_public, sort_order
             FROM site_services
             ORDER BY sort_order, title'
        );

        $services = $stmt->fetchAll();
        foreach ($services as &$service) {
            $service['is_public'] = in_array($service['is_public'], [true, 1, '1', 't', 'true'], true);
        }
        unset($service);

        return $services;
    }

    // RETORNA UM SERVIÇO ESPECÍFICO COM BASE NO SLUG
    public function createService(array $service): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO site_services (slug, title, description, href, icon, is_public, sort_order)
             VALUES (
                 :slug,
                 :title,
                 :description,
                 :href,
                 :icon,
                 TRUE,
                 COALESCE((SELECT MAX(sort_order) + 1 FROM site_services), 0)
             )'
        );

        return $stmt->execute([
            ':slug' => $service['slug'],
            ':title' => $service['title'],
            ':description' => $service['description'],
            ':href' => $service['href'],
            ':icon' => $service['icon'],
        ]);
    }

    // RETORNA UM SERVIÇO ESPECÍFICO COM BASE NO SLUG
    public function serviceSlugExists(string $slug): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM site_services WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);

        return $stmt->fetchColumn() !== false;
    }

    // RETORNA UM SERVIÇO ESPECÍFICO COM BASE NO SLUG
    public function publicCarousel(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, image_path, alt_text, caption, sort_order
             FROM site_carousel
             WHERE is_public = TRUE
             ORDER BY sort_order, id'
        );

        return $stmt->fetchAll();
    }

    // RETORNA TODOS OS ITENS DO CARROSSEL, INCLUINDO OS NÃO PÚBLICOS
    public function allCarousel(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, image_path, alt_text, caption, is_public, sort_order
             FROM site_carousel
             ORDER BY sort_order, id'
        );

        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['is_public'] = in_array($item['is_public'], [true, 1, '1', 't', 'true'], true);
        }
        unset($item);

        return $items;
    }

    // atualiza os dados das configurações do portal
    public function updateSettings(array $settings): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO site_settings (id, primary_color, accent_color, background_color, text_color, updated_at)
             VALUES (1, :primary_color, :accent_color, :background_color, :text_color, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO UPDATE SET
                 primary_color = EXCLUDED.primary_color,
                 accent_color = EXCLUDED.accent_color,
                 background_color = EXCLUDED.background_color,
                 text_color = EXCLUDED.text_color,
                 updated_at = CURRENT_TIMESTAMP'
        );

        return $stmt->execute([
            ':primary_color' => $settings['primary_color'],
            ':accent_color' => $settings['accent_color'],
            ':background_color' => $settings['background_color'],
            ':text_color' => $settings['text_color'],
        ]);
    }

    // atualiza os links das redes sociais do portal
    public function updateSocialLinks(array $links): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE site_settings
             SET whatsapp_url = :whatsapp_url,
                 instagram_url = :instagram_url,
                 facebook_url = :facebook_url,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = 1'
        );

        return $stmt->execute([
            ':whatsapp_url' => $links['whatsapp_url'],
            ':instagram_url' => $links['instagram_url'],
            ':facebook_url' => $links['facebook_url'],
        ]);
    }

    public function updateContactInfo(array $contact): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE site_settings
             SET contact_title = :contact_title,
                 contact_address = :contact_address,
                 contact_phone = :contact_phone,
                 contact_email = :contact_email,
                 contact_hours = :contact_hours,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = 1'
        );

        return $stmt->execute([
            ':contact_title' => $contact['contact_title'],
            ':contact_address' => $contact['contact_address'],
            ':contact_phone' => $contact['contact_phone'],
            ':contact_email' => $contact['contact_email'],
            ':contact_hours' => $contact['contact_hours'],
        ]);
    }

    // edita a logo
    public function updateLogo(string $path): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO site_settings (id, logo_path, updated_at)
             VALUES (1, :logo_path, CURRENT_TIMESTAMP)
             ON CONFLICT (id) DO UPDATE SET
                 logo_path = EXCLUDED.logo_path,
                 updated_at = CURRENT_TIMESTAMP'
        );

        return $stmt->execute([':logo_path' => $path]);
    }

    // adiciona uma nova imagem ao carrossel
    public function addCarouselImage(string $path, string $altText, string $caption): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO site_carousel (image_path, alt_text, caption, is_public, sort_order)
             VALUES (:image_path, :alt_text, :caption, TRUE, COALESCE((SELECT MAX(sort_order) + 1 FROM site_carousel), 0))'
        );

        return $stmt->execute([
            ':image_path' => $path,
            ':alt_text' => $altText,
            ':caption' => $caption,
        ]);
    }

    // deleta uma imagem do carrossel
    public function deleteCarouselImage(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM site_carousel WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    // edita um serviço existente com base no slug
    public function updateService(string $slug, array $service): bool
    {
        $columns = [
            'title' => 'title',
            'description' => 'description',
            'href' => 'href',
            'icon' => 'icon',
            'is_public' => 'is_public',
        ];
        $updates = [];
        $params = [':slug' => $slug];

        foreach ($columns as $field => $column) {
            if (!array_key_exists($field, $service)) {
                continue;
            }

            $parameter = ':' . $field;
            $updates[] = $column . ' = ' . $parameter;
            $params[$parameter] = $service[$field];
        }

        if ($updates === []) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE site_services SET ' . implode(', ', $updates) . ' WHERE slug = :slug'
        );
        foreach ($params as $parameter => $value) {
            $type = $parameter === ':is_public' ? PDO::PARAM_BOOL : PDO::PARAM_STR;
            $stmt->bindValue($parameter, $value, $type);
        }
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return true;
        }

        $exists = $this->pdo->prepare('SELECT 1 FROM site_services WHERE slug = :slug');
        $exists->execute([':slug' => $slug]);

        return $exists->fetchColumn() !== false;
    }
}
