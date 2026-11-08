<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($noticia['title']); ?> - SEMED</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/noticias.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div id="header"></div>

    <main class="noticia-main-content">
        <article class="news-article-container">

            <h1 class="news-title">
                <?php 
                    echo htmlspecialchars($noticia['title']); 
                ?>
            </h1>

            <div class="news-meta">
                <span>Categoria:
                    <strong>
                        <?php 
                            echo htmlspecialchars($noticia['categoria'] ?? 'Geral'); 
                        ?>
                    </strong>
                </span>

                <?php if (isset($noticia['created_at'])): ?>

                    <span style="margin-left: 20px;">
                        Publicado em: 
                        <strong>
                            <?php 
                                echo date('d/m/Y H:i', 
                                strtotime($noticia['created_at'])); 
                            ?>
                        </strong>
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!empty($noticia['imagem'])): ?>

                <img src="<?php echo '/images/uploads/' . rawurlencode(basename($noticia['imagem'])); ?>"
                     alt="<?php echo htmlspecialchars($noticia['title'], ENT_QUOTES, 'UTF-8'); ?>"
                     class="news-image">

            <?php endif; ?>

            <div class="news-content">

                <?php 
                    echo htmlspecialchars($noticia['content']); 
                ?>
            </div>

            <div class="btn-back-container">
                <a href="/" class="btn-verde-claro">
                    Voltar para o Início
                </a>
            </div>
        </article>
    </main>

    <div id="footer"></div>
    
    <script src="/assets/js/config.js"></script>
    <script src="/assets/js/include-components.js"></script>
    <!-- <script>
    document.addEventListener('DOMContentLoaded', () => {
        const useLocalStorage = <?php echo USE_LOCALSTORAGE ? 'true' : 'false'; ?>;
        if (!useLocalStorage) return;

        const urlParams = new URLSearchParams(window.location.search);
        const id = parseInt(urlParams.get('id'));

        const noticias = JSON.parse(localStorage.getItem('noticias')) || [];
        const noticia = noticias.find(n => n.id === id);

        if (!noticia) {
            alert('Notícia não encontrada!');
            window.location.href = '/';
            return;
        }

        // Preencher DOM
        document.title = `${noticia.title} - SEMED`;
        document.querySelector('.news-title').textContent = noticia.title;
        
        const dataFormatada = new Date(noticia.created_at).toLocaleString('pt-BR');
        document.querySelector('.news-meta').innerHTML = `
            <span>Categoria: <strong>${noticia.categoria || 'Geral'}</strong></span>
            <span style="margin-left: 20px;">Publicado em: <strong>${dataFormatada}</strong></span>
        `;

        const container = document.querySelector('.news-article-container');
        const oldImg = container.querySelector('.news-image');
        if (oldImg) oldImg.remove();

        if (noticia.imagem) {
            const img = document.createElement('img');
            img.src = noticia.imagem;
            img.className = 'news-image';
            img.alt = noticia.title;
            const newsContent = container.querySelector('.news-content');
            container.insertBefore(img, newsContent);
        }

        document.querySelector('.news-content').textContent = noticia.content;
    });
    </script>
    -->
</body>
</html>