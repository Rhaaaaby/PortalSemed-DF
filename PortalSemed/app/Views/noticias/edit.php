<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Notícia - SEMED</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="/assets/css/noticias.css">
</head>
<body>
    <div id="header"></div>

    <main class="login-main-container">
    
        <div class="login-card">
            <h2>Editar Notícia</h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php
                    echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
                ?>">

                <!-- editar titulo da noticia -->
                <div class="input-group">
                    <label for="titulo">Título da Notícia:</label>

                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        placeholder="Digite o título"
                        required
                        value="<?php echo htmlspecialchars($noticia['title'], ENT_QUOTES, 'UTF-8'); ?>">

                </div>

                <!-- editar conteudo da noticia -->
                <div class="input-group">
                    <label for="conteudo">Conteúdo:</label>

                    <textarea
                        id="conteudo" 
                        name="conteudo" 
                        placeholder="Digite o conteúdo da notícia..." 
                        rows="6"><?php echo htmlspecialchars($noticia['content'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <!-- editar categoria da noticia -->
                <div class="input-group">

                    <label for="categoria">Categoria:</label>

                    <!-- Preencher o select com as categorias disponíveis -->
                    <select name="categoria" id="categoria">
                        <?php if (!empty($categorias) && is_array($categorias)): ?>

                            <?php foreach ($categorias as $c): ?>
                                <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo (($noticia['categoria'] ?? null) === $c) ? 'selected' : ''; ?>><?php
                                        echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8');
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        <!-- Caso não haja categorias disponíveis,
                        O codigo exibirá uma opção padrão informando que não há categorias. --> 
                        <?php else: ?>

                            <option value="">Sem categorias</option>

                        <?php endif; ?>
                    </select>
                </div>

                <!-- editar imagem da noticia -->
                <?php if (!empty($noticia['imagem'])): ?>

                    <div class="input-group">
                        <label>Imagem Atual:</label>

                        <img
                            src="/images/uploads/<?php echo rawurlencode(basename($noticia['imagem'])); ?>"
                            alt="Imagem atual da notícia">

                    </div>

                <?php endif; ?>

                <!-- Input para alterar a imagem da notícia -->
                <div class="input-group">
                    <label for="imagem">Alterar Imagem:</label>

                    <input
                        type="file" 
                        id="imagem" 
                        name="imagem" 
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        style="padding: 8px 0;">
                    
                    <small>JPG, PNG, WEBP ou GIF; até 5 MB.</small>

                </div>

                <!-- Campos ocultos para manter a imagem atual e o ID do autor -->
                <input type="hidden" name="imagem_atual" value="<?php echo isset($noticia['imagem']) ? $noticia['imagem'] : ''; ?>">

                <!-- Campo oculto para manter o ID do autor da notícia -->
                <input type="hidden" name="author_id" value="<?php echo isset($noticia['author_id']) ? $noticia['author_id'] : ''; ?>">


                <div class="btn-editar">
                    <!-- Botão para salvar as alterações da notícia -->
                    <button type="submit" class="btn-verde-claro">Salvar</button>

                    <!-- Botão para voltar à página de notícias -->
                    <a href="/noticias" class="btn-voltar">Voltar</a>
                </div>

            </form>
        </div>
    </main>

    <div id="footer"></div>
    <script src="/assets/js/config.js"></script>
    <script src="/assets/js/include-components.js"></script>
    <!--
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
    -->
</body>
</html>
