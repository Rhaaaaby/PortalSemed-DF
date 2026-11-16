<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection === null) {
            try {
                $databaseUrl = self::environmentValue('DATABASE_URL');
                if ($databaseUrl !== '') {
                    [$dsn, $user, $pass] = self::parseDatabaseUrl($databaseUrl);
                } else {
                    $host = self::environmentValue('DB_HOST', 'localhost');
                    $port = self::environmentValue('DB_PORT', '5432');
                    $name = self::environmentValue('DB_NAME', 'portalSemed');
                    $user = self::environmentValue('DB_USER', 'postgres');
                    $pass = self::environmentValue('DB_PASS');
                    $dsn = "pgsql:host=$host;port=$port;dbname=$name";
                }
                
                self::$connection = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                error_log('Database connection failed: ' . $e->getMessage());
                http_response_code(500);
                die(json_encode(['erro' => 'Erro interno ao conectar ao banco de dados.'], JSON_UNESCAPED_UNICODE));
            }
        }

        return self::$connection;
    }

    private static function environmentValue(string $name, string $default = ''): string
    {
        $value = $_ENV[$name] ?? getenv($name);

        return is_string($value) ? $value : $default;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private static function parseDatabaseUrl(string $url): array
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            throw new PDOException('DATABASE_URL must be a PostgreSQL connection URL.');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (!in_array($scheme, ['postgres', 'postgresql'], true) ||
            empty($parts['host']) ||
            !isset($parts['user'], $parts['pass']) ||
            $path === '/' || $path === '') {
            throw new PDOException('DATABASE_URL must be a PostgreSQL connection URL.');
        }

        $host = $parts['host'];
        $port = (int) ($parts['port'] ?? 5432);
        $database = rawurldecode(ltrim($path, '/'));
        if ($port < 1 || $port > 65535 ||
            strpbrk($host . $database, ";\r\n\0") !== false) {
            throw new PDOException('DATABASE_URL contains invalid PostgreSQL connection settings.');
        }

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        $sslMode = $query['sslmode'] ?? 'require';
        if (!is_string($sslMode) ||
            !in_array($sslMode, ['require', 'verify-ca', 'verify-full'], true)) {
            throw new PDOException('DATABASE_URL must require encrypted PostgreSQL connections.');
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=%s',
            $host,
            $port,
            $database,
            $sslMode
        );

        return [
            $dsn,
            rawurldecode($parts['user']),
            rawurldecode($parts['pass']),
        ];
    }

    public static function getInstance(): PDO
    {
        return self::connect();
    }
}
