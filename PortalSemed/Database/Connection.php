<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;

    /**
     * Retorna a conexão PDO (singleton - cria apenas uma vez)
     * Usa valores do arquivo .env
     *
     * @return PDO
     * @throws PDOException 
     */
    
    public static function connect(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host     = $_ENV['DB_HOST']     ?? 'localhost';
        $port     = $_ENV['DB_PORT']     ?? '5432';
        $dbname   = $_ENV['DB_NAME']     ?? 'portalSemed';
        $user     = $_ENV['DB_USER']     ?? 'postgres';
        $password = $_ENV['DB_PASS']     ?? '123456';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};";

        $dsn .= "options='--client_encoding=UTF8'";

        try {
            self::$instance = new PDO(
                $dsn,
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,          
                    PDO::ATTR_STRINGIFY_FETCHES  => false,
                ]
            );

            return self::$instance;

        } catch (PDOException $e) {
            if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
                throw $e;
            }

            error_log("Erro de conexão com banco: " . $e->getMessage());
            throw new PDOException("Não foi possível conectar ao banco de dados. Tente novamente mais tarde.");
        }
    }

    public static function reconnect(): PDO
    {
        self::$instance = null;
        return self::connect();
    }
}