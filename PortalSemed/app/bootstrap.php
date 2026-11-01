<?php
require_once __DIR__ . '/../vendor/autoload.php';

// 1. Carrega as variáveis de ambiente do arquivo .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \"'");
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// 2. constantes globais úteis

define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// 3. Inclui a conexão com o banco

use App\Database\Connection;

// 4. Outras inicializações importantes 

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// require_once __DIR__ . '/config/config.php';  // ← se criar um config.php depois
