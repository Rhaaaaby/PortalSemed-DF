<h1><?php echo $noticia['title']; ?></h1>

<?php if ($noticia['imagem']): ?>
    <img src="uploads/<?php echo $noticia['imagem']; ?>" width="300">
<?php endif; ?>

<p><?php echo $noticia['content']; ?></p>

<br>
<a href="index.php">Voltar</a>