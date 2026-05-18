<?php
class Database {
    public static function connect() {
        $pdo = new PDO("pgsql:host=localhost;dbname=portalSemed", "postgres", "123456");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    }
}