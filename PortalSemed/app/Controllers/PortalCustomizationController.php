<?php

//Controller para customização do portal, incluindo cores, logo, carrossel e serviços.

namespace App\Controllers;

use App\Models\PortalCustomization;

class PortalCustomizationController
{
    private PortalCustomization $model;

    public function __construct()
    {
        $this->model = new PortalCustomization();
    }

    // Retorna o conteúdo público do portal, incluindo configurações, 
    // serviços públicos e imagens do carrossel.
    public function publicContent(): void
    {
        json_response([
            'settings' => $this->model->settings(),
            'services' => $this->model->publicServices(),
            'carousel' => $this->model->publicCarousel(),
        ]);
    }

    // Retorna o conteúdo administrativo do portal, incluindo configurações,
    public function adminContent(): void
    {
        json_response([
            'settings' => $this->model->settings(),
            'services' => $this->model->allServices(),
            'carousel' => $this->model->allCarousel(),
        ]);
    }

    // Retorna todos os serviços cadastrados no portal, incluindo os privados.
    public function manageServices(): void
    {
        json_response(['services' => $this->model->allServices()]);
    }

    public function createService(array $data): void
    {
        // Valida os campos obrigatórios do serviço e cria um novo serviço no portal.
        $service = $this->validateServiceFields($data, true);

        // Gera um slug único para o serviço com base no título.
        //slug é uma versão amigável do título, usada em URLs.
        $slug = $this->makeServiceSlug($service['title']);

        $baseSlug = rtrim(substr($slug, 0, 54), '-') ?: 'servico';

        $slug = $baseSlug;

        $suffix = 2;

        while ($this->model->serviceSlugExists($slug)) {
            $slug = substr($baseSlug, 0, 59 - strlen((string) $suffix)) . '-' . $suffix;
            $suffix++;
        }

        $service['slug'] = $slug;
        if (!$this->model->createService($service)) {
            json_response(['erro' => 'Não foi possível criar o serviço.'], 500);
        }

        json_response(['mensagem' => 'Serviço criado com sucesso.', 'slug' => $slug], 201);
    }


    // Atualiza as cores do portal com base nos dados fornecidos.
    public function updateSettings(array $data): void
    {
        $fields = ['primary_color', 'accent_color', 'background_color', 'text_color'];
        $settings = [];

        foreach ($fields as $field) {

            $value = $data[$field] ?? null;
            if (!is_string($value) || !preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                json_response(['erro' => 'Informe cores válidas no formato hexadecimal.'], 400);
            }

            $settings[$field] = strtoupper($value);
        }

        if (!$this->model->updateSettings($settings)) {

            json_response(['erro' => 'Não foi possível salvar as cores do portal.'], 500);
        }

        json_response(['mensagem' => 'Tema atualizado com sucesso.']);
    }

    // Atualiza a logo do portal com base na imagem enviada.
    public function uploadLogo(): void
    {
        $path = $this->saveImageUpload('logo');

        if (!$this->model->updateLogo($path)) {

            json_response(['erro' => 'Não foi possível atualizar a logo.'], 500);
        }

        json_response(['mensagem' => 'Logo atualizada com sucesso.', 'logo_path' => $path]);
    }

    // Adiciona uma nova imagem ao carrossel do portal com texto alternativo e legenda.
    public function addCarouselImage(): void
    {
        $altInput = $_POST['alt_text'] ?? '';
        $captionInput = $_POST['caption'] ?? '';
        if (!is_string($altInput) || !is_string($captionInput)) {
            json_response(['erro' => 'Os textos da imagem são inválidos.'], 400);
        }

        $altText = trim($altInput);
        $caption = trim($captionInput);
        $altLength = preg_match_all('/./us', $altText);
        $captionLength = preg_match_all('/./us', $caption);
        if ($altText === '' || $altLength === false || $altLength > 180 || $captionLength === false || $captionLength > 180) {
            json_response(['erro' => 'Informe um texto alternativo de até 180 caracteres; a legenda também deve ter até 180 caracteres.'], 400);
        }

        $path = $this->saveImageUpload('image');
        if (!$this->model->addCarouselImage($path, $altText, $caption)) {
            json_response(['erro' => 'Não foi possível adicionar a imagem ao carrossel.'], 500);
        }

        json_response(['mensagem' => 'Imagem adicionada ao carrossel.'], 201);
    }

    // Remove uma imagem do carrossel do portal com base no ID.
    public function deleteCarouselImage(int $id): void
    {
        if (!$this->model->deleteCarouselImage($id)) {
            json_response(['erro' => 'Imagem do carrossel não encontrada.'], 404);
        }

        json_response(['mensagem' => 'Imagem removida do carrossel.']);
    }

    // Atualiza os detalhes de um serviço existente com base no slug e nos dados fornecidos.
    public function updateService(string $slug, array $data): void
    {
        if (!isset($data['is_public']) || !is_bool($data['is_public'])) {
            json_response(['erro' => 'Informe a visibilidade do serviço.'], 400);
        }

        $service = ['is_public' => $data['is_public']];
        if (array_intersect(['title', 'description', 'href', 'icon'], array_keys($data)) !== []) {
            $service += $this->validateServiceFields($data, false);
        }

        if (!$this->model->updateService($slug, $service)) {
            json_response(['erro' => 'Serviço não encontrado.'], 404);
        }

        json_response(['mensagem' => 'Serviço atualizado com sucesso.']);
    }

    // Remove um serviço existente com base no slug.
    private function validateServiceFields(array $data, bool $requireHref): array
    {
        $service = [];
        foreach ([
            'title' => [100, 'Informe um nome de serviço com até 100 caracteres.'],
            'description' => [300, 'Informe uma descrição com até 300 caracteres.'],
        ] as $field => [$maxLength, $error]) {
            if ($requireHref || array_key_exists($field, $data)) {
                $value = $data[$field] ?? null;
                if (!is_string($value) || trim($value) === '' ||
                    ($length = preg_match_all('/./us', trim($value))) === false || $length > $maxLength) {
                    json_response(['erro' => $error], 400);
                }
                $service[$field] = trim($value);
            }
        }

        if ($requireHref || array_key_exists('href', $data)) {
            $href = $data['href'] ?? null;
            if (!is_string($href)) {
                json_response(['erro' => 'Informe um endereço HTTPS válido ou um caminho interno.'], 400);
            }
            $href = trim($href);
            if (($requireHref && $href === '') ||
                ($href !== '' && !$this->isValidServiceHref($href))) {
                json_response(['erro' => 'Informe um endereço HTTPS válido ou um caminho interno.'], 400);
            }
            $service['href'] = $href;
        }

        if ($requireHref || array_key_exists('icon', $data)) {
            $icon = $data['icon'] ?? '🔗';
            if (!is_string($icon) ||
                ($length = preg_match_all('/./us', trim($icon))) === false || $length > 16) {
                json_response(['erro' => 'O ícone deve ter até 16 caracteres.'], 400);
            }
            $service['icon'] = trim($icon) !== '' ? trim($icon) : '🔗';
        }

        return $service;
    }

    // Gera um slug único para o serviço com base no título.
    //slug é uma versão amigável do título, usada em URLs.
    private function makeServiceSlug(string $title): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
        $slug = strtolower((string) ($transliterated === false ? $title : $transliterated));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'servico';
    }

    // Atualiza os links das redes sociais do portal com base nos dados fornecidos.
    public function updateSocialLinks(array $data): void
    {
        $links = [];
        foreach (['whatsapp_url', 'instagram_url', 'facebook_url'] as $field) {
            $value = $data[$field] ?? null;
            if (!is_string($value)) {
                json_response(['erro' => 'Informe endereços válidos para as redes sociais.'], 400);
            }

            $value = trim($value);
            if ($value !== '' && !$this->isValidHttpsUrl($value)) {
                json_response(['erro' => 'Os links das redes sociais devem usar endereços HTTPS válidos.'], 400);
            }
            $links[$field] = $value;
        }

        if (!$this->model->updateSocialLinks($links)) {
            json_response(['erro' => 'Não foi possível salvar os links das redes sociais.'], 500);
        }

        json_response(['mensagem' => 'Links das redes sociais atualizados.']);
    }

    // Valida se o href do serviço é válido, seja um link HTTPS ou um caminho interno.
    private function isValidServiceHref(string $href): bool
    {
        if ($href === '' || strlen($href) > 255 || preg_match('/[\x00-\x20\x7f\\\\]/', $href)) {
            return false;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $href)) {
            return $this->isValidHttpsUrl($href);
        }

        return !str_starts_with($href, '//');
    }

    // Valida se a URL fornecida é um link HTTPS válido.
    private function isValidHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);

        return filter_var($url, FILTER_VALIDATE_URL) !== false &&
            is_array($parts) &&
            strtolower($parts['scheme'] ?? '') === 'https' &&
            !empty($parts['host']);
    }

    // Salva a imagem enviada no servidor e retorna o caminho relativo para acesso público.
    private function saveImageUpload(string $field): string
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            json_response(['erro' => 'Selecione uma imagem válida para enviar.'], 400);
        }

        $file = $_FILES[$field];
        if ($file['size'] > 5 * 1024 * 1024) {
            json_response(['erro' => 'A imagem deve ter no máximo 5 MB.'], 400);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
            json_response(['erro' => 'Formato inválido. Use uma imagem JPG, PNG, WEBP ou GIF.'], 400);
        }

        $filename = 'portal-' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        $directory = APP_ROOT . '/public/assets/images/uploads/';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            json_response(['erro' => 'Não foi possível preparar o armazenamento de imagens.'], 500);
        }

        if (!move_uploaded_file($file['tmp_name'], $directory . $filename)) {
            json_response(['erro' => 'Não foi possível salvar a imagem enviada.'], 500);
        }

        return '/images/uploads/' . $filename;
    }
}
