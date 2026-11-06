<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Notícia - SEMED</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="stylesheet" href="/assets/css/noticias.css">
</head>
<body>
    <div id="header"></div>

    <main class="login-main-container">
        <div class="login-card">
            <h2>Publicar Nova Notícia</h2>
            
            <form 
                id="noticia-form" 
                method="POST" 
                enctype="multipart/form-data" >

                <input 
                    type="hidden" 
                    name="csrf_token" 
                    value="
                        <?php 
                            echo htmlspecialchars($_SESSION['csrf_token'] ?? '', 
                            ENT_QUOTES, 'UTF-8'); 
                        ?>
                    "
                >

                <div class="input-group">

                    <label for="titulo">Título da Notícia:</label>

                    <input 
                        type="text" 
                        id="titulo" 
                        name="titulo" 
                        placeholder="Digite o título" 
                        required>
                </div>

                <div class="input-group">

                    <label for="conteudo">Conteúdo:</label>

                    <textarea 
                        id="conteudo" 
                        class="conteudo"
                        name="conteudo" 
                        placeholder="Digite o conteúdo da notícia..." 
                        rows="6" 
                        required>
                    </textarea>
                </div>

                <div class="input-group">

                    <label for="categoria">Categoria:</label>

                    <select 
                        name="categoria" 
                        id="categoria" 
                        class="categoria">

                        <?php if (!empty($categorias) && is_array($categorias)): ?>

                            <?php foreach ($categorias as $c): ?>

                                <option value="<?php echo $c; ?>"><?php echo $c; ?></option>

                            <?php endforeach; ?>

                        <?php else: ?>
                            <!-- Caso não haja categorias disponíveis, o código exibirá uma opção padrão informando que não há categorias. -->
                            <option value="">Sem categorias</option>

                        <?php endif; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="imagem">Imagem de Destaque:</label>
                    <input 
                        type="file" 
                        id="imagem" 
                        name="imagem" 
                        accept="image/jpeg,image/png,image/webp,image/gif" 
                        style="padding: 8px 0;"
                    >
                    
                    

                    <small>JPG, PNG, WEBP ou GIF; até 5 MB.</small>
                </div>

                <!--<input type="hidden" name="author_id" value="
                //(<)?php echo $author_id; ?>
                ">-->

                <div class="btn-criar">
                    <button type="submit" class="btn-verde-claro">Publicar</button>
                    <a href="/noticias" class="btn-voltar">Voltar</a>
                </div>
            </form>
        </div>
    </main>

    <div id="footer"></div>
    <script src="/assets/js/config.js"></script>
    <script src="/assets/js/include-components.js"></script>
    <script src="/assets/js/noticia-create.js"></script>
</body>
</html>