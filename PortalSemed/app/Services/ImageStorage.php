<?php

namespace App\Services;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use InvalidArgumentException;
use RuntimeException;

class ImageStorage
{
    private const MAX_SIZE = 5 * 1024 * 1024;
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private static ?S3Client $r2Client = null;

    public static function storeUploadedImage(array $file, string $prefix): string
    {
        $upload = self::validateUpload($file);
        $filename = $prefix . '-' . bin2hex(random_bytes(16)) . '.' . self::EXTENSIONS[$upload['mime']];
        $driver = self::storageDriver();

        if ($driver === 'r2') {
            return self::storeInR2($upload, $filename);
        }

        if ($driver !== 'local') {
            throw new RuntimeException('IMAGE_STORAGE_DRIVER must be either local or r2.');
        }

        $directory = APP_ROOT . '/public/assets/images/uploads/';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not prepare image storage.');
        }

        if (!move_uploaded_file($upload['tmp_name'], $directory . $filename)) {
            throw new RuntimeException('Could not save uploaded image.');
        }

        return '/images/uploads/' . $filename;
    }

    public static function publicUrl(?string $reference): ?string
    {
        if ($reference === null || trim($reference) === '') {
            return null;
        }

        $reference = trim($reference);
        $parts = parse_url($reference);
        if (is_array($parts) && isset($parts['scheme'], $parts['host'])) {
            if (strtolower($parts['scheme']) !== 'https' ||
                filter_var($reference, FILTER_VALIDATE_URL) === false ||
                isset($parts['user']) ||
                isset($parts['pass'])) {
                throw new RuntimeException('Stored image URLs must use HTTPS.');
            }

            return $reference;
        }

        if (preg_match('#^/images/uploads/[A-Za-z0-9._-]+$#', $reference)) {
            return $reference;
        }

        if (preg_match('/^[A-Za-z0-9._-]+$/', $reference)) {
            return '/images/uploads/' . rawurlencode($reference);
        }

        throw new RuntimeException('Stored image reference is invalid.');
    }

    private static function validateUpload(array $file): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $message = match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
                UPLOAD_ERR_NO_FILE => 'Selecione uma imagem para enviar.',
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A imagem deve ter no máximo 5 MB.',
                UPLOAD_ERR_PARTIAL => 'O envio da imagem foi interrompido. Tente novamente.',
                default => 'Não foi possível receber a imagem enviada.',
            };
            throw new InvalidArgumentException($message);
        }

        $tmpName = $file['tmp_name'] ?? null;
        $size = $file['size'] ?? null;
        if (!is_string($tmpName) || $tmpName === '' || !is_int($size) || $size < 1) {
            throw new InvalidArgumentException('A imagem enviada é inválida.');
        }
        if ($size > self::MAX_SIZE) {
            throw new InvalidArgumentException('A imagem deve ter no máximo 5 MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName);
        if (!is_string($mime) ||
            !isset(self::EXTENSIONS[$mime]) ||
            @getimagesize($tmpName) === false) {
            throw new InvalidArgumentException('Envie uma imagem JPG, PNG, WEBP ou GIF válida.');
        }

        return [
            'tmp_name' => $tmpName,
            'size' => $size,
            'mime' => $mime,
        ];
    }

    private static function storageDriver(): string
    {
        $driver = $_ENV['IMAGE_STORAGE_DRIVER'] ?? getenv('IMAGE_STORAGE_DRIVER');

        return is_string($driver) && trim($driver) !== ''
            ? strtolower(trim($driver))
            : 'local';
    }

    private static function storeInR2(array $upload, string $filename): string
    {
        $bucket = self::environmentValue('R2_BUCKET');
        $publicBaseUrl = self::environmentValue('R2_PUBLIC_BASE_URL');
        if ($bucket === '' || $publicBaseUrl === '') {
            throw new RuntimeException('R2_BUCKET and R2_PUBLIC_BASE_URL must be configured.');
        }

        $publicUrl = parse_url($publicBaseUrl);
        if (!is_array($publicUrl) ||
            strtolower((string) ($publicUrl['scheme'] ?? '')) !== 'https' ||
            empty($publicUrl['host']) ||
            filter_var($publicBaseUrl, FILTER_VALIDATE_URL) === false ||
            isset($publicUrl['user']) ||
            isset($publicUrl['pass']) ||
            isset($publicUrl['query']) ||
            isset($publicUrl['fragment'])) {
            throw new RuntimeException('R2_PUBLIC_BASE_URL must be a valid HTTPS base URL.');
        }

        $key = 'portal/' . $filename;
        $stream = fopen($upload['tmp_name'], 'rb');
        if ($stream === false) {
            throw new RuntimeException('Could not read uploaded image.');
        }

        try {
            self::r2Client()->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'Body' => $stream,
                'ContentLength' => $upload['size'],
                'ContentType' => $upload['mime'],
                'CacheControl' => 'public, max-age=31536000, immutable',
            ]);
        } catch (AwsException $e) {
            error_log('R2 image upload failed: ' . $e->getAwsErrorCode());
            throw new RuntimeException('Could not save uploaded image to remote storage.', 0, $e);
        } finally {
            fclose($stream);
        }

        $baseUrl = rtrim($publicBaseUrl, '/');

        return $baseUrl . '/portal/' . rawurlencode($filename);
    }

    private static function r2Client(): S3Client
    {
        if (self::$r2Client !== null) {
            return self::$r2Client;
        }

        $accountId = self::environmentValue('R2_ACCOUNT_ID');
        $accessKeyId = self::environmentValue('R2_ACCESS_KEY_ID');
        $secretAccessKey = self::environmentValue('R2_SECRET_ACCESS_KEY');
        if ($accountId === '' || $accessKeyId === '' || $secretAccessKey === '') {
            throw new RuntimeException('R2 account and API credentials must be configured.');
        }

        self::$r2Client = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => 'https://' . $accountId . '.r2.cloudflarestorage.com',
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $accessKeyId,
                'secret' => $secretAccessKey,
            ],
        ]);

        return self::$r2Client;
    }

    private static function environmentValue(string $name): string
    {
        $value = $_ENV[$name] ?? getenv($name);

        return is_string($value) ? trim($value) : '';
    }
}
