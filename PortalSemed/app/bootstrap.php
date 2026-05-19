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
define('USE_LOCALSTORAGE', ($_ENV['STORAGE_MODE'] ?? 'localstorage') === 'localstorage'); //utilizado para testes e apresentação

// 3. Inclui a conexão com o banco

use App\Database\Connection;

// 4. Outras inicializações importantes 

session_start();                  // ← quando for usar sessões
// require_once __DIR__ . '/config/config.php';  // ← se criar um config.php depois
