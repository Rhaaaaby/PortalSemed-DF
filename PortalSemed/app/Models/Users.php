<?php

namespace App\Models;

use PDO;
use App\Database\Connection;

class Users
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::connect();
    }

    // CREATE
    public function criar(array $data): bool
    {
        if (!in_array($data['role'] ?? null, ['admin', 'funcionario'], true)) {
            throw new \InvalidArgumentException('Perfil de usuário inválido.');
        }

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
            ':role' => $data['role'] ?? 'funcionario',
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
        WHERE regexp_replace(cpf, '[^0-9]', '', 'g') = :cpf
        ORDER BY id
        LIMIT 1");

        $stmt->execute([':cpf' => preg_replace('/\D/', '', $cpf)]);
        return $stmt->fetch();
    }

    // READ - Buscar todas as contas associadas ao CPF normalizado
    public function buscarCredenciaisPorCpf(string $cpf)
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                id,
                name,
                cpf,
                password,
                role,
                status
            FROM users
            WHERE regexp_replace(cpf, '[^0-9]', '', 'g') = :cpf
            ORDER BY id"
        );

        $stmt->execute([':cpf' => preg_replace('/\D/', '', $cpf)]);

        return $stmt->fetchAll();
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

    // Listar todos os funcionários
    public function listarFuncionarios(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                id,
                name,
                cpf,
                role,
                status

            FROM users
            WHERE role IN ('admin', 'funcionario')
            ORDER BY role, name"
        );
        return $stmt->fetchAll();
    }

    public function contarAdministradoresAtivos(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = TRUE"
        );

        return (int) $stmt->fetchColumn();
    }

    public function contarAdministradores(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");

        return (int) $stmt->fetchColumn();
    }

    public function criarPrimeiroAdministrador(array $data): bool
    {
        $this->pdo->beginTransaction();
        try {
            $this->pdo->exec('LOCK TABLE users IN EXCLUSIVE MODE');
            if ($this->contarAdministradores() > 0 || $this->buscarPorCpf($data['cpf'])) {
                $this->pdo->rollBack();
                return false;
            }

            $created = $this->criar([
                'name' => $data['name'],
                'cpf' => $data['cpf'],
                'password' => $data['password'],
                'role' => 'admin',
                'status' => true,
            ]);

            if (!$created) {
                $this->pdo->rollBack();
                return false;
            }

            $this->pdo->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function atualizarStatus(int $id, bool $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET status = :status WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':status' => $status,
        ]);

        return $stmt->rowCount() > 0;
    }

    // Atualiza o hash da senha de um usuário específico
    public function atualizarSenha($id, $novoHash)
    {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':password' => $novoHash,
            ':id'       => $id
        ]);
    }
}