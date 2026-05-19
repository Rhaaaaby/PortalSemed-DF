<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Notícias - SEMED</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .admin-table-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .admin-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 15px;
        }
        .admin-header-row h2 {
            color: #2D4F43;
            margin: 0;
        }
        .noticias-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            text-align: left;
        }
        .noticias-table th, .noticias-table td {
            padding: 14px;
            border-bottom: 1px solid #E5E7EB;
        }
        .noticias-table th {
            background-color: #F3F4F6;
            color: #374151;
            font-weight: 600;
        }
        .noticias-table tr:hover {
            background-color: #F9FAFB;
        }
        .badge-cat {
            background-color: #E0F2FE;
            color: #0369A1;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .action-btns {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }
        .btn-edit {
            background-color: #3B82F6;
            color: white;
        }
        .btn-edit:hover {
            background-color: #2563EB;
        }
        .btn-delete {
            background-color: #EF4444;
            color: white;
        }
        .btn-delete:hover {
            background-color: #DC2626;
        }
    </style>
</head>
<body>
    <div id="header"></div>

    <main style="background-color: #FDF2F2; min-height: 80vh; padding: 20px 10px;">
        <div class="admin-table-container">
            <div class="admin-header-row">
                <h2>Gerenciar Notícias Publicadas</h2>
                <a href="/noticias?action=create" class="btn-verde-claro" style="text-decoration: none; padding: 10px 20px; border-radius: 6px; margin: 0 !important; max-width: 180px;">+ Nova Notícia</a>
            </div>

            <table class="noticias-table">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Categoria</th>
                        <th>Data de Criação</th>
                        <th style="width: 180px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($noticias) && is_array($noticias)): ?>
                        <?php foreach ($noticias as $n): ?>
                            <tr>
                                <td style="font-weight: 500; color: #111827;"><?php echo htmlspecialchars($n['title']); ?></td>
                                <td><span class="badge-cat"><?php echo htmlspecialchars($n['categoria'] ?? 'Geral'); ?></span></td>
                                <td style="color: #6B7280;"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="/noticias?action=edit&id=<?php echo $n['id']; ?>" class="btn-action btn-edit">Editar</a>
                                        <a href="/noticias?action=delete&id=<?php echo $n['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Tem certeza que deseja excluir esta notícia?');">Excluir</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #6B7280; padding: 30px;">Nenhuma notícia publicada até o momento.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top: 30px;">
                <a href="/perfil-admin.html" class="btn-verde-claro" style="background-color: #6B7280 !important; text-decoration: none; color: white !important; padding: 10px 25px; border-radius: 6px; display: inline-block;">Voltar ao Painel</a>
            </div>
        </div>
    </main>

    <div id="footer"></div>
    <script src="/js/include-components.js"></script>
</body>
</html>
