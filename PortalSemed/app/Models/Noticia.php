<?php
require_once __DIR__ . '/../config/database.php';

class Noticia {

    public static function all() {
        $db = Database::connect();
        return $db->query("SELECT * FROM public.posts ORDER BY created_at DESC")
                  ->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM public.posts WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::connect();
        $stmt = $db->prepare("INSERT INTO public.posts (title, content, author_id, created_at, categoria, imagem) VALUES (?, ?, ?, NOW(), ?, ?)");
        return $stmt->execute([$data['title'], $data['content'], $data['author_id'], $data['categoria'], $data['imagem']]);
    }

    public static function update($id, $data) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE public.posts SET title=?, content=?, author_id=?, categoria=?, imagem=? WHERE id=?");
        return $stmt->execute([$data['title'], $data['content'], $data['author_id'], $data['categoria'], $data['imagem'], $id]);
    }

    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM public.posts WHERE id=?");
        return $stmt->execute([$id]);
    }
}