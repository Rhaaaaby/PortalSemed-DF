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
        email,
        password, 
        role, 
        status, 
        created_at) 
        
        VALUES (
        :name, 
        :cpf, 
        :email,
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
            ':email' => trim($data['email']),
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
        email,
        role,
        status
        FROM users 
        WHERE regexp_replace(cpf, '[^0-9]', '', 'g') = :cpf
        ORDER BY id
        LIMIT 1");

        $stmt->execute([':cpf' => preg_replace('/\D/', '', $cpf)]);
        return $stmt->fetch();
    }

    public function cpfEmUso(string $cpf, ?int $exceptId = null): bool
    {
        $sql = "SELECT 1 FROM users
                WHERE regexp_replace(cpf, '[^0-9]', '', 'g') = :cpf";
        $params = [':cpf' => preg_replace('/\D/', '', $cpf)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params[':except_id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    public function emailEmUso(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email)';
        $params = [':email' => trim($email)];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params[':except_id'] = $exceptId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetchColumn();
    }

    // READ - Buscar todas as contas associadas ao CPF normalizado
    public function buscarCredenciaisPorCpf(string $cpf)
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                id,
                name,
                cpf,
                email,
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
        email,
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
        email,
        role,
        status
        FROM users 
        WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscarHashSenhaPorId(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $password = $stmt->fetchColumn();
        return is_string($password) ? $password : null;
    }

    public function createPasswordResetToken(string $email, string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email FROM users
             WHERE LOWER(email) = LOWER(:email) AND status = TRUE
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $this->pdo->beginTransaction();
        try {
            $insert = $this->pdo->prepare(
                'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
                 VALUES (:user_id, :token_hash, CURRENT_TIMESTAMP + INTERVAL \'1 hour\')
                 ON CONFLICT (user_id) DO UPDATE SET
                     token_hash = EXCLUDED.token_hash,
                     expires_at = EXCLUDED.expires_at,
                     created_at = CURRENT_TIMESTAMP'
            );
            $insert->execute([
                ':user_id' => $user['id'],
                ':token_hash' => $tokenHash,
            ]);
            $this->pdo->commit();
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }

        return $user;
    }

    public function removePasswordResetTokens(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
    }

    public function resetPassword(string $tokenHash, string $password): bool
    {
        $this->pdo->beginTransaction();
        try {
            $find = $this->pdo->prepare(
                'SELECT id, user_id FROM password_reset_tokens
                 WHERE token_hash = :token_hash AND expires_at > CURRENT_TIMESTAMP
                 FOR UPDATE'
            );
            $find->execute([':token_hash' => $tokenHash]);
            $token = $find->fetch();
            if (!$token) {
                $this->pdo->rollBack();
                return false;
            }

            $update = $this->pdo->prepare(
                'UPDATE users SET password = :password WHERE id = :user_id'
            );
            $update->execute([
                ':password' => password_hash($password, PASSWORD_ARGON2ID),
                ':user_id' => $token['user_id'],
            ]);
            if ($update->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }

            $remove = $this->pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = :user_id');
            $remove->execute([':user_id' => $token['user_id']]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
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

        if (isset($data['email'])) {
            $campos[] = 'email = :email';
            $params[':email'] = trim($data['email']);
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
                email,
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

    // cria o primeiro administrador do sistema, caso não exista nenhum
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
                'email' => $data['email'],
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

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    // Atualiza o hash da senha de um usuário específico
    public function atualizarSenha($id, $novoHash)
    {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':password' => $novoHash,
            ':id'       => $id
        ]);
    }
}