<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Notícia - SEMED</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div id="header"></div>

    <main class="login-main-container" style="padding: 40px 20px;">
        <div class="login-card" style="max-width: 500px !important;">
            <h2>Publicar Nova Notícia</h2>
            
            <form method="POST" enctype="multipart/form-data" style="align-items: stretch !important; gap: 15px;">
                <div class="input-group">
                    <label for="titulo">Título da Notícia:</label>
                    <input type="text" id="titulo" name="titulo" placeholder="Digite o título" required>
                </div>

                <div class="input-group">
                    <label for="conteudo">Conteúdo:</label>
                    <textarea id="conteudo" name="conteudo" placeholder="Digite o conteúdo da notícia..." rows="6" style="width: 100%; padding: 12px; border: 1px solid #C4C4C4; border-radius: 8px; box-sizing: border-box; background-color: white; font-family: inherit; font-size: 0.95rem; resize: vertical;"></textarea>
                </div>

                <div class="input-group">
                    <label for="categoria">Categoria:</label>
                    <select name="categoria" id="categoria" style="width: 100%; padding: 12px; border: 1px solid #C4C4C4; border-radius: 8px; box-sizing: border-box; background-color: white; font-size: 0.95rem;">
                        <?php if (!empty($categorias) && is_array($categorias)): ?>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">Sem categorias</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="imagem">Imagem de Destaque:</label>
                    <input type="file" id="imagem" name="imagem" accept="image/*" style="padding: 8px 0;">
                </div>

                <input type="hidden" name="author_id" value="1">

                <div style="display: flex; gap: 15px; justify-content: center; width: 100%; margin-top: 15px;">
                    <button type="submit" class="btn-verde-claro" style="margin: 0 !important; width: 48% !important;">Publicar</button>
                    <a href="/noticias" class="btn-verde-claro" style="background-color: #6B7280 !important; text-decoration: none; color: white !important; margin: 0 !important; width: 48% !important; display: flex; align-items: center; justify-content: center;">Voltar</a>
                </div>
            </form>
        </div>
    </main>

    <div id="footer"></div>
    <script src="/js/config.js"></script>
    <script src="/js/include-components.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const useLocalStorage = <?php echo USE_LOCALSTORAGE ? 'true' : 'false'; ?>;
        if (!useLocalStorage) return;

        const form = document.querySelector('form');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            
            const titulo = document.getElementById('titulo').value;
            const conteudo = document.getElementById('conteudo').value;
            const categoria = document.getElementById('categoria').value;
            const fileInput = document.getElementById('imagem');
            
            function salvar(imagemBase64) {
                const noticias = JSON.parse(localStorage.getItem('noticias')) || [];
                const novaNoticia = {
                    id: Date.now(),
                    title: titulo,
                    content: conteudo,
                    categoria: categoria,
                    imagem: imagemBase64,
                    created_at: new Date().toISOString()
                };
                noticias.unshift(novaNoticia); // Adiciona no início
                localStorage.setItem('noticias', JSON.stringify(noticias));
                window.location.href = '/noticias';
            }

            if (fileInput.files.length > 0) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    salvar(e.target.result);
                };
                reader.readAsDataURL(fileInput.files[0]);
            } else {
                salvar(null);
            }
        });
    });
    </script>
</body>
</html>