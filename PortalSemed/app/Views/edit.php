<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Notícia - SEMED</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div id="header"></div>

    <main class="login-main-container" style="padding: 40px 20px;">
        <div class="login-card" style="max-width: 500px !important;">
            <h2>Editar Notícia</h2>
            
            <form method="POST" enctype="multipart/form-data" style="align-items: stretch !important; gap: 15px;">
                <div class="input-group">
                    <label for="titulo">Título da Notícia:</label>
                    <input type="text" id="titulo" name="titulo" placeholder="Digite o título" required value="<?php echo isset($noticia['title']) ? htmlspecialchars($noticia['title']) : ''; ?>">
                </div>

                <div class="input-group">
                    <label for="conteudo">Conteúdo:</label>
                    <textarea id="conteudo" name="conteudo" placeholder="Digite o conteúdo da notícia..." rows="6" style="width: 100%; padding: 12px; border: 1px solid #C4C4C4; border-radius: 8px; box-sizing: border-box; background-color: white; font-family: inherit; font-size: 0.95rem; resize: vertical;"><?php echo isset($noticia['content']) ? htmlspecialchars($noticia['content']) : ''; ?></textarea>
                </div>

                <div class="input-group">
                    <label for="categoria">Categoria:</label>
                    <select name="categoria" id="categoria" style="width: 100%; padding: 12px; border: 1px solid #C4C4C4; border-radius: 8px; box-sizing: border-box; background-color: white; font-size: 0.95rem;">
                        <?php if (!empty($categorias) && is_array($categorias)): ?>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?php echo $c; ?>" <?php echo (isset($noticia['categoria']) && $noticia['categoria'] === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">Sem categorias</option>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if (!empty($noticia['imagem'])): ?>
                    <div class="input-group" style="text-align: center;">
                        <label>Imagem Atual:</label>
                        <img src="/uploads/<?php echo $noticia['imagem']; ?>" style="max-width: 100%; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 5px;" width="200">
                    </div>
                <?php endif; ?>

                <div class="input-group">
                    <label for="imagem">Alterar Imagem:</label>
                    <input type="file" id="imagem" name="imagem" accept="image/*" style="padding: 8px 0;">
                </div>

                <input type="hidden" name="imagem_atual" value="<?php echo isset($noticia['imagem']) ? $noticia['imagem'] : ''; ?>">
                <input type="hidden" name="author_id" value="<?php echo isset($noticia['author_id']) ? $noticia['author_id'] : ''; ?>">

                <div style="display: flex; gap: 15px; justify-content: center; width: 100%; margin-top: 15px;">
                    <button type="submit" class="btn-verde-claro" style="margin: 0 !important; width: 48% !important;">Salvar</button>
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

        // 1. Obter ID do query param
        const urlParams = new URLSearchParams(window.location.search);
        const id = parseInt(urlParams.get('id'));

        const noticias = JSON.parse(localStorage.getItem('noticias')) || [];
        const noticia = noticias.find(n => n.id === id);

        if (!noticia) {
            alert('Notícia não encontrada!');
            window.location.href = '/noticias';
            return;
        }

        // 2. Preencher form
        document.getElementById('titulo').value = noticia.title || noticia.titulo || '';
        document.getElementById('conteudo').value = noticia.content || noticia.conteudo || '';
        document.getElementById('categoria').value = noticia.categoria || 'Notícias';
        
        // Mostrar preview da imagem atual
        const imagemAtualInput = document.querySelector('input[name="imagem_atual"]');
        if (imagemAtualInput) imagemAtualInput.value = noticia.imagem || '';
        
        if (noticia.imagem) {
            const imgGroup = document.createElement('div');
            imgGroup.className = 'input-group';
            imgGroup.style.textAlign = 'center';
            imgGroup.innerHTML = `
                <label>Imagem Atual:</label>
                <img src="${noticia.imagem}" style="max-width: 100%; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 5px;" width="200">
            `;
            const form = document.querySelector('form');
            const fileInputGroup = document.getElementById('imagem').closest('.input-group');
            form.insertBefore(imgGroup, fileInputGroup);
        }

        // 3. Interceptar submit
        const form = document.querySelector('form');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            
            const titulo = document.getElementById('titulo').value;
            const conteudo = document.getElementById('conteudo').value;
            const categoria = document.getElementById('categoria').value;
            const fileInput = document.getElementById('imagem');
            
            function salvarEdicao(imagemBase64) {
                const idx = noticias.findIndex(n => n.id === id);
                if (idx !== -1) {
                    noticias[idx].title = titulo;
                    noticias[idx].content = conteudo;
                    noticias[idx].categoria = categoria;
                    if (imagemBase64 !== undefined) {
                        noticias[idx].imagem = imagemBase64;
                    }
                    localStorage.setItem('noticias', JSON.stringify(noticias));
                }
                window.location.href = '/noticias';
            }

            if (fileInput.files.length > 0) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    salvarEdicao(e.target.result);
                };
                reader.readAsDataURL(fileInput.files[0]);
            } else {
                // Mantém a imagem atual
                salvarEdicao(noticia.imagem);
            }
        });
    });
    </script>
</body>
</html>
