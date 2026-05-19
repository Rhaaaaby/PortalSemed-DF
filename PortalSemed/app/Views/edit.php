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
    <script src="/js/include-components.js"></script>
</body>
</html>
