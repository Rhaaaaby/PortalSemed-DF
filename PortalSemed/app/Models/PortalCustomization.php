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

    public function settings(): array
    {
        $stmt = $this->pdo->query(
            'SELECT primary_color, accent_color, background_color, text_color, logo_path
             FROM site_settings
             WHERE id = 1'
        );
        $settings = $stmt->fetch();

        return $settings ?: [
            'primary_color' => '#2D4F43',
            'accent_color' => '#8EB34E',
            'background_color' => '#FDF2F2',
            'text_color' => '#333333',
            'logo_path' => '/images/logo.png',
        ];
    }

    public function publicServices(): array
    {
        $stmt = $this->pdo->query(
            'SELECT slug, title, description, href, icon, is_public, sort_order
             FROM site_services
             WHERE is_public = TRUE
             ORDER BY sort_order, title'
        );

        return $stmt->fetchAll();
    }

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

    public function deleteCarouselImage(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM site_carousel WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function updateServiceVisibility(string $slug, bool $isPublic): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE site_services SET is_public = :is_public WHERE slug = :slug'
        );

        $stmt->execute([
            ':slug' => $slug,
            ':is_public' => $isPublic,
        ]);

        return $stmt->rowCount() > 0;
    }
}
