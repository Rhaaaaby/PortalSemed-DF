<?php

namespace App\Models;

use PDO;
use App\Database\Connection;

class Users
{
    private PDO $pdo;

    public function __construct()
    {
        if (defined('USE_LOCALSTORAGE') && USE_LOCALSTORAGE) {
            return;
        }
        $this->pdo = Connection::connect();
    }

    // CREATE
    public function criar(array $data): bool
    {
        $senhaHash = password_hash($data['password'], PASSWORD_ARGON2ID);

        $sql = 
        "INSERT INTO users (
        name, 
        cpf, 
        password, 
        role, 
        status, 
        created_at) 
        
        VALUES (
        :name, 
        :cpf, 
        :password, 
        :role, 
        :status, 
        CURRENT_TIMESTAMP)";

        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([

            ':name' => trim($data['name']),
            ':cpf' => trim($data['cpf']),
            ':password' => $senhaHash,
            ':role' => $data['role'] ?? 'user',
            ':status' => $data['status'] ?? true,
        ]);
    }

    // READ - Buscar por cpf (usado no login)
    public function buscarPorCpf(string $cpf)
    {
        $stmt = $this->pdo->prepare(
        "SELECT 
        id, 
        name, 
        cpf, 
        role,
        status
        FROM users 
        WHERE cpf = :cpf");

        $stmt->execute([':cpf' => $cpf]);
        return $stmt->fetch();
    }

    //READ - Buscar por nome (usado no login)
    public function buscarPorNome(string $name)
    {
        $stmt = $this->pdo->prepare(
        "SELECT 
        id, 
        name, 
        cpf, 
        role,
        status
        FROM users 
        WHERE name = :name");

        $stmt->execute([':name' => $name]);
        return $stmt->fetch();
    }

    // READ - Buscar por ID (perfil)
    public function buscarPorId(int $id)
    {
        $stmt = $this->pdo->prepare("SELECT 
        id, 
        name, 
        cpf, 
        role,
        status
        FROM users 
        WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // UPDATE
    public function atualizar(int $id, array $data): bool
    {
        $campos = [];
        $params = [
            ':id' => $id
        ];

        //atualizar apenas os campos que foram enviados no array $data (nome)
        if (isset($data['name'])) {
            $campos[] = 'name = :name';
            $params[':name'] = trim($data['name']);
        }

        //atualizar apenas os campos que foram enviados no array $data (cpf)
        if (isset($data['cpf'])) {
            $campos[] = 'cpf = :cpf';
            $params[':cpf'] = trim($data['cpf']);
        }

        //atualizar apenas os campos que foram enviados no array $data (senha)
        if (isset($data['password']) && 
            $data['password'] !== ''
        ) {
            $campos[] = 'password = :password';
            $params[':password'] = password_hash(
                $data['password'], 
                PASSWORD_ARGON2ID
            );
        }

        if(empty($campos)) {
            return false;
        }

        $sql = "UPDATE users 
        SET " . implode(', ', $campos) . " 
        WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // DELETE (soft delete - apenas marca como inativo)
    public function deletar(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
            SET status = false
            WHERE id =:id'
        );

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}