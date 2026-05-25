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
                $host = $_ENV['DB_HOST'] ?? 'localhost';
                $port = $_ENV['DB_PORT'] ?? '5432';
                $name = $_ENV['DB_NAME'] ?? 'portalSemed';
                $user = $_ENV['DB_USER'] ?? 'postgres';
                $pass = $_ENV['DB_PASS'] ?? '';

                $dsn = "pgsql:host=$host;port=$port;dbname=$name";
                
                self::$connection = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['erro' => 'Erro ao conectar ao banco de dados: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE));
            }
        }

        return self::$connection;
    }

    public static function getInstance(): PDO
    {
        return self::connect();
    }
}
