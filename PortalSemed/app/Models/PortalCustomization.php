<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class PortalCustomization
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::connect();
    }

    // RETORNA AS CONFIGURAÇÕES DO PORTAL
    public function settings(): array
    {
        $stmt = $this->pdo->query(
            'SELECT primary_color, accent_color, background_color, text_color, logo_path,
                    whatsapp_url, instagram_url, facebook_url
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
        ];
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
