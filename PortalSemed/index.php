<link rel="stylesheet" href="css/style.css">

<h1>Portal da Educação</h1>

<a class="btn" href="?action=create">+ Nova Notícia</a>

<div class="grid">
<?php foreach ($noticias as $n): ?>
    <div class="card">
        <?php if ($n['imagem']): ?>
            <img src="uploads/<?php echo $n['imagem']; ?>">
        <?php endif; ?>

        <h2><?php echo $n['title']; ?></h2>

        <p><?php echo substr($n['content'], 0, 100); ?>...</p>

        <a href="?action=view&id=<?php echo $n['id']; ?>">Ver</a>
        <a href="?action=edit&id=<?php echo $n['id']; ?>">Editar</a>
        <a href="?action=delete&id=<?php echo $n['id']; ?>">Excluir</a>
    </div>
<?php endforeach; ?>
</div>