<?php
use App\Database\Connection;

class Database {
    public static function connect() {
        return Connection::connect();
    }
}