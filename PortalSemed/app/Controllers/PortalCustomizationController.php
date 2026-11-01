<?php

namespace App\Controllers;

use App\Models\PortalCustomization;

class PortalCustomizationController
{
    private PortalCustomization $model;

    public function __construct()
    {
        $this->model = new PortalCustomization();
    }

    public function publicContent(): void
    {
        json_response([
            'settings' => $this->model->settings(),
            'services' => $this->model->publicServices(),
            'carousel' => $this->model->publicCarousel(),
        ]);
    }

    public function adminContent(): void
    {
        json_response([
            'settings' => $this->model->settings(),
            'services' => $this->model->allServices(),
            'carousel' => $this->model->allCarousel(),
        ]);
    }

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

    public function uploadLogo(): void
    {
        $path = $this->saveImageUpload('logo');

        if (!$this->model->updateLogo($path)) {
            json_response(['erro' => 'Não foi possível atualizar a logo.'], 500);
        }

        json_response(['mensagem' => 'Logo atualizada com sucesso.', 'logo_path' => $path]);
    }

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

    public function deleteCarouselImage(int $id): void
    {
        if (!$this->model->deleteCarouselImage($id)) {
            json_response(['erro' => 'Imagem do carrossel não encontrada.'], 404);
        }

        json_response(['mensagem' => 'Imagem removida do carrossel.']);
    }

    public function updateService(string $slug, array $data): void
    {
        if (!isset($data['is_public']) || !is_bool($data['is_public'])) {
            json_response(['erro' => 'Informe a visibilidade do serviço.'], 400);
        }

        if (!$this->model->updateServiceVisibility($slug, $data['is_public'])) {
            json_response(['erro' => 'Serviço não encontrado.'], 404);
        }

        json_response(['mensagem' => 'Visibilidade do serviço atualizada.']);
    }

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
