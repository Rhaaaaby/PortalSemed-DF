<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($noticia['title']); ?> - SEMED</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .news-article-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .news-title {
            color: #2D4F43;
            font-size: 2.2rem;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .news-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .news-image {
            width: 100%;
            max-height: 450px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .news-content {
            font-size: 1.1rem;
            line-height: 1.7;
            color: #333;
            white-space: pre-wrap;
        }
        .btn-back-container {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div id="header"></div>

    <main style="background-color: #FDF2F2; min-height: 80vh; padding: 20px 10px;">
        <article class="news-article-container">
            <h1 class="news-title"><?php echo htmlspecialchars($noticia['title']); ?></h1>
            <div class="news-meta">
                <span>Categoria: <strong><?php echo htmlspecialchars($noticia['categoria'] ?? 'Geral'); ?></strong></span>
                <?php if (isset($noticia['created_at'])): ?>
                    <span style="margin-left: 20px;">Publicado em: <strong><?php echo date('d/m/Y H:i', strtotime($noticia['created_at'])); ?></strong></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($noticia['imagem'])): ?>
                <img src="/uploads/<?php echo $noticia['imagem']; ?>" alt="<?php echo htmlspecialchars($noticia['title']); ?>" class="news-image">
            <?php endif; ?>

            <div class="news-content">
                <?php echo htmlspecialchars($noticia['content']); ?>
            </div>

            <div class="btn-back-container">
                <a href="/" class="btn-verde-claro" style="text-decoration: none; display: inline-block; padding: 10px 25px; border-radius: 6px;">Voltar para o Início</a>
            </div>
        </article>
    </main>

    <div id="footer"></div>
    <script src="/js/config.js"></script>
    <script src="/js/include-components.js"></script>
    <script>
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
</body>
</html>