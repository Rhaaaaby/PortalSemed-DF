<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Notícias - SEMED</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/painel-adm.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div id="header"></div>

    <main class="admin-main-container">
        <div class="admin-table-container">
            <div class="admin-header-row">

                <h2>Gerenciar Notícias Publicadas</h2>
                <a href="/noticias?action=create" class="btn-verde-claro">+ Nova Notícia</a>
            </div>

            <div class="table-scroll" tabindex="0" role="region" aria-label="Tabela de notícias; deslize para visualizar todas as colunas">
            <table class="noticias-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Categoria</th>
                        <th>Data de Criação</th>
                        <th style="width: 180px;">Ações</th>
                    </tr>
                </thead>
                <tbody id="noticias-tbody">
                    <?php if ($noticias): ?>
                        <?php foreach ($noticias as $n): ?>
                            <tr>
                                <td 
                                    style="
                                        font-weight: 500; 
                                        color: #111827;
                                    "
                                >

                                <?php 
                                    echo htmlspecialchars($n['title']); 
                                ?>
                                </td>

                                <td>
                                    <span class="badge-cat">
                                        <?php echo htmlspecialchars($n['categoria'] ?? 'Geral'); ?>
                                    </span>
                                </td>

                                <td style="color: #6B7280;">
                                    <?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?>
                                </td>

                                <td>
                                    <div class="action-btns">

                                        <a href="/noticias?action=edit&id=<?php echo $n['id']; ?>" class="btn-action btn-edit">Editar</a>

                                        <form method="POST"
                                              action="/noticias?action=delete&amp;id=<?php echo (int) $n['id']; ?>"
                                              onsubmit="return confirm('Tem certeza que deseja excluir esta notícia?');">
                                            <input type="hidden" name="csrf_token" value="<?php
                                                echo htmlspecialchars(
                                                    $_SESSION['csrf_token'] ?? '',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                            ?>">

                                            <button type="submit" class="btn-action btn-delete">Excluir</button>

                                        </form>
                                    </div>
                                </td>
                            </tr>

                        <?php endforeach; ?>
                    <?php else: ?>

                        <tr>
                            <td colspan="4" class="text-center">Nenhuma notícia publicada até o momento.</td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <div style="margin-top: 30px;">
                <a href="/perfil-admin.html" class="btn-verde-claro">Voltar ao Painel</a>
            </div>
        </div>
    </main>

    <div id="footer"></div>
    <script src="/assets/js/config.js"></script>
    <script src="/assets/js/include-components.js"></script>
</body>
</html>
