<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$checks = [
    'php_version' => [
        'status' => PHP_VERSION_ID >= 80000,
        'message' => 'PHP 8.0+ required',
        'current' => PHP_VERSION,
    ],
    'pdo_pgsql' => [
        'status' => extension_loaded('pdo_pgsql'),
        'message' => 'PDO PostgreSQL extension required',
    ],
    'json' => [
        'status' => extension_loaded('json'),
        'message' => 'JSON extension required',
    ],
    'env_file' => [
        'status' => file_exists(__DIR__ . '/../.env'),
        'message' => '.env file exists',
    ],
    'vendor_autoload' => [
        'status' => file_exists(__DIR__ . '/../vendor/autoload.php'),
        'message' => 'Composer dependencies installed',
    ],
    'database_connection' => [
        'status' => test_database_connection(),
        'message' => 'Database connection successful',
    ],
];

$all_ok = !in_array(false, array_column($checks, 'status'));

http_response_code($all_ok ? 200 : 503);

echo json_encode([
    'status' => $all_ok ? 'ok' : 'error',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => $checks,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function test_database_connection(): bool
{
    try {
        if (!file_exists(__DIR__ . '/../.env')) {
            return false;
        }

        $env = [];
        $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value, " \"'");
        }

        $host = $env['DB_HOST'] ?? 'localhost';
        $port = $env['DB_PORT'] ?? '5432';
        $name = $env['DB_NAME'] ?? '';
        $user = $env['DB_USER'] ?? '';
        $pass = $env['DB_PASS'] ?? '';

        if (!$name || !$user) {
            return false;
        }

        $dsn = "pgsql:host=$host;port=$port;dbname=$name";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 5]);

        return true;
    } catch (Exception $e) {
        return false;
    }
}
