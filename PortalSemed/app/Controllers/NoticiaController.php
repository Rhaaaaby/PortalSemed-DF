<?php
require_once __DIR__ . '/../Models/Noticia.php';

class NoticiaController {

    public function index() {
        $noticias = Noticia::all();
        require __DIR__ . '/../Views/index.php';
    }

    public function create() {
        if ($_POST) {

            $imagem = null;

            if (!empty($_FILES['imagem']['name'])) {
                $nome = time() . "_" . $_FILES['imagem']['name'];
                move_uploaded_file($_FILES['imagem']['tmp_name'], "uploads/" . $nome);
                $imagem = $nome;
            }

            $author_id = !empty($_POST['author_id']) ? $_POST['author_id'] : 1;

            try {
                Noticia::create([
                    'title' => $_POST['titulo'],
                    'content' => $_POST['conteudo'],
                    'author_id' => $author_id,
                    'categoria' => $_POST['categoria'],
                    'imagem' => $imagem
                ]);

                header("Location: /noticias");
                exit;
            } catch (Exception $e) {
                echo "Erro ao salvar notícia: " . $e->getMessage();
                exit;
            }
        }

        // Lista de categorias disponível para a view
        $categorias = [
            'Notícias',
            'Eventos',
            'Comunicados'
        ];

        require __DIR__ . '/../Views/create.php';
    }

    public function view() {
        $noticia = Noticia::find($_GET['id']);
        require __DIR__ . '/../Views/view.php';
    }

    public function edit() {
        $id = $_GET['id'];

        if ($_POST) {
            $imagem = $_POST['imagem_atual'];

            if (!empty($_FILES['imagem']['name'])) {
                $nome = time() . "_" . $_FILES['imagem']['name'];
                move_uploaded_file($_FILES['imagem']['tmp_name'], "uploads/" . $nome);
                $imagem = $nome;
            }

            $author_id = !empty($_POST['author_id']) ? $_POST['author_id'] : 1;

            try {
                Noticia::update($id, [
                    'title' => $_POST['titulo'],
                    'content' => $_POST['conteudo'],
                    'author_id' => $author_id,
                    'categoria' => $_POST['categoria'],
                    'imagem' => $imagem
                ]);

                header("Location: /noticias");
                exit;
            } catch (Exception $e) {
                echo "Erro ao atualizar notícia: " . $e->getMessage();
                exit;
            }
        }

        $noticia = Noticia::find($id);

        // Lista de categorias disponível para a view de edição
        $categorias = [
            'Notícias',
            'Eventos',
            'Comunicados'
        ];

        require __DIR__ . '/../Views/edit.php';
    }

    public function delete() {
        Noticia::delete($_GET['id']);
        header("Location: /noticias");
    }
}