<?php

namespace App\Controllers;

use App\Models\Noticia;

class NoticiaController
{
    private Noticia $model;

    public function __construct()
    {
        $this->model = new Noticia();
    }

    // LISTAR NOTÍCIAS
    public function index()
    {
        $noticias = $this->model->all();

        require __DIR__ . '/../Views/noticias/index.php';
    }

    // CRIAR NOTÍCIA
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $imagem = null;

            if (
                isset($_FILES['imagem']) &&
                $_FILES['imagem']['error'] === UPLOAD_ERR_OK
            ) {
                $nome = time() . '_' . basename($_FILES['imagem']['name']);

                $diretorio = APP_ROOT . '/public/assets/images/uploads/';

                if (!is_dir($diretorio)) {
                    mkdir($diretorio, 0755, true);
                }

                if (move_uploaded_file(
                    $_FILES['imagem']['tmp_name'],
                    $diretorio . $nome
                )) {
                    $imagem = $nome;
                }
            }

            $author_id = !empty($_POST['author_id'])
                ? (int) $_POST['author_id']
                : null;

            try {

                $this->model->create([
                    'title' => $_POST['titulo'] ?? '',
                    'content' => $_POST['conteudo'] ?? '',
                    'author_id' => $author_id,
                    'categoria' => $_POST['categoria'] ?? null,
                    'imagem' => $imagem
                ]);

                header('Location: /noticias');
                exit;

            } catch (\Exception $e) {

                echo 'Erro ao salvar notícia: ' . $e->getMessage();
                exit;
            }
        }

        $categorias = [
            'Notícias',
            'Eventos',
            'Comunicados'
        ];

        require __DIR__ . '/../Views/noticias/create.php';
    }

    // VISUALIZAR NOTÍCIA
    public function view()
    {
        $id = isset($_GET['id'])
            ? (int) $_GET['id']
            : 0;

        $noticia = $this->model->find($id);

        if (!$noticia) {
            http_response_code(404);
            echo 'Notícia não encontrada.';
            exit;
        }

        require __DIR__ . '/../Views/noticias/view.php';
    }

    // EDITAR NOTÍCIA
    public function edit()
    {
        $id = isset($_GET['id'])
            ? (int) $_GET['id']
            : 0;

        $noticia = $this->model->find($id);

        if (!$noticia) {
            http_response_code(404);
            echo 'Notícia não encontrada.';
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $imagem = $_POST['imagem_atual'] ?? $noticia['imagem'] ?? null;

            if (
                isset($_FILES['imagem']) &&
                $_FILES['imagem']['error'] === UPLOAD_ERR_OK
            ) {
                $nome = time() . '_' . basename($_FILES['imagem']['name']);

                $diretorio = APP_ROOT . '/public/assets/images/uploads/';

                if (!is_dir($diretorio)) {
                    mkdir($diretorio, 0755, true);
                }

                if (move_uploaded_file(
                    $_FILES['imagem']['tmp_name'],
                    $diretorio . $nome
                )) {
                    $imagem = $nome;
                }
            }

            $author_id = !empty($_POST['author_id'])
                ? (int) $_POST['author_id']
                : null;

            try {

                $this->model->update($id, [
                    'title' => $_POST['titulo'] ?? '',
                    'content' => $_POST['conteudo'] ?? '',
                    'author_id' => $author_id,
                    'categoria' => $_POST['categoria'] ?? null,
                    'imagem' => $imagem
                ]);

                header('Location: /noticias');
                exit;

            } catch (\Exception $e) {

                echo 'Erro ao atualizar notícia: ' . $e->getMessage();
                exit;
            }
        }

        $categorias = [
            'Notícias',
            'Eventos',
            'Comunicados'
        ];

        require __DIR__ . '/../Views/noticias/edit.php';
    }

    // DELETAR NOTÍCIA
    public function delete()
    {
        $id = isset($_GET['id'])
            ? (int) $_GET['id']
            : 0;

        $this->model->delete($id);

        header('Location: /noticias');
        exit;
    }
}