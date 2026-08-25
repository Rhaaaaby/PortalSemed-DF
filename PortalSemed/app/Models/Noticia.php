<?php

namespace App\Models;

use PDO;
use App\Database\Connection;

class Noticia
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Connection::connect();
    }

    // LISTAR TODAS AS NOTÍCIAS
    public function all(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                id,
                title,
                content,
                author_id,
                created_at,
                updated_at,
                categoria,
                imagem
            FROM posts
            ORDER BY created_at DESC"
        );

        return $stmt->fetchAll();
    }

    // BUSCAR UMA NOTÍCIA PELO ID
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                id,
                title,
                content,
                author_id,
                created_at,
                updated_at,
                categoria,
                imagem
            FROM posts
            WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        $noticia = $stmt->fetch();

        return $noticia ?: null;
    }

    // CRIAR UMA NOTÍCIA
    public function create(array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO posts (
                title,
                content,
                author_id,
                created_at,
                categoria,
                imagem
            )
            VALUES (
                :title,
                :content,
                :author_id,
                CURRENT_TIMESTAMP,
                :categoria,
                :imagem
            )"
        );

        return $stmt->execute([
            ':title' => trim($data['title']),
            ':content' => trim($data['content']),
            ':author_id' => $data['author_id'] ?? null,
            ':categoria' => $data['categoria'] ?? null,
            ':imagem' => $data['imagem'] ?? null
        ]);
    }

    // ATUALIZAR UMA NOTÍCIA
    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE posts
            SET
                title = :title,
                content = :content,
                author_id = :author_id,
                categoria = :categoria,
                imagem = :imagem,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id"
        );

        return $stmt->execute([
            ':title' => trim($data['title']),
            ':content' => trim($data['content']),
            ':author_id' => $data['author_id'] ?? null,
            ':categoria' => $data['categoria'] ?? null,
            ':imagem' => $data['imagem'] ?? null,
            ':id' => $id
        ]);
    }

    // DELETAR UMA NOTÍCIA
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM posts
            WHERE id = :id"
        );

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}