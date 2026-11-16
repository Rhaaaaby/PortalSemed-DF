<?php

namespace App\Controllers;

use App\Models\Noticia;
use App\Services\ImageStorage;

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
    public function create(? int $author_id = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $created = $this->model->create([
                    'title' => $_POST['titulo'] ?? '',
                    'content' => $_POST['conteudo'] ?? '',
                    'author_id' => $author_id,
                    'categoria' => $_POST['categoria'] ?? null,
                    'imagem' => $this->saveUploadedImage()
                ]);

                if (!$created) {
                    json_response(['erro' => 'Não foi possível salvar a notícia.'], 500);
                }

                json_response([
                    'mensagem' => 'Notícia criada com sucesso!'
                ], 201);

            } catch (\InvalidArgumentException $e) {
                json_response(['erro' => $e->getMessage()], 400);

            } catch (\Exception $e) {
                error_log('Erro ao salvar notícia: ' . $e);
                json_response(['erro' => 'Não foi possível salvar a notícia.'], 500);
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
            $author_id = !empty($_POST['author_id'])
                ? (int) $_POST['author_id']
                : null;

            try {
                $updated = $this->model->update($id, [
                    'title' => $_POST['titulo'] ?? '',
                    'content' => $_POST['conteudo'] ?? '',
                    'author_id' => $author_id,
                    'categoria' => $_POST['categoria'] ?? null,
                    'imagem' => $this->saveUploadedImage() ?? $noticia['imagem']
                ]);

                if (!$updated) {
                    throw new \RuntimeException('A atualização da notícia não foi concluída.');
                }

                header('Location: /noticias');
                exit;

            } catch (\InvalidArgumentException $e) {
                http_response_code(400);
                echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
                exit;

            } catch (\Exception $e) {
                error_log('Erro ao atualizar notícia: ' . $e);
                http_response_code(500);
                echo 'Não foi possível atualizar a notícia.';
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

    // SALVAR IMAGEM ENVIADA
    private function saveUploadedImage(): ?string
    {
        if (!isset($_FILES['imagem']) ||
            ($_FILES['imagem']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return ImageStorage::storeUploadedImage($_FILES['imagem'], 'noticia');
    }

    // DELETAR NOTÍCIA
    public function delete()
    {
        $id = isset($_GET['id'])
            ? (int) $_GET['id']
            : 0;

        if ($id < 1 || !$this->model->find($id)) {
            http_response_code(404);
            echo 'Notícia não encontrada.';
            exit;
        }

        if (!$this->model->delete($id)) {
            http_response_code(500);
            echo 'Não foi possível excluir a notícia.';
            exit;
        }

        header('Location: /noticias');
        exit;
    }
}