<?php
//require_once __DIR__ . '/../vendor/autoload.php';

// 2. Carrega as variáveis de ambiente do .env

//$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
//$dotenv->load();

//$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'JWT_SECRET']);

// 3. constantes globais úteis

define('APP_ROOT', dirname(__DIR__));
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// 4. Inclui a conexão com o banco

use App\Database\Connection;

// 5. Outras inicializações importantes 

// session_start();                  // ← quando for usar sessões
// require_once __DIR__ . '/config/config.php';  // ← se criar um config.php depois
