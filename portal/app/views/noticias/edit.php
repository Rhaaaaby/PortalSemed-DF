<form method="POST" enctype="multipart/form-data">
    <h2>Editar Notícia</h2>

    <input type="text" name="titulo" placeholder="Título" required value="<?php echo isset($noticia['title']) ? htmlspecialchars($noticia['title']) : ''; ?>"><br><br>

    <textarea name="conteudo" placeholder="Conteúdo"><?php echo isset($noticia['content']) ? htmlspecialchars($noticia['content']) : ''; ?></textarea><br><br>

    <?php if (!empty($noticia['imagem'])): ?>
        <img src="uploads/<?php echo $noticia['imagem']; ?>" width="200"><br>
    <?php endif; ?>

    <input type="file" name="imagem"><br><br>

    <input type="hidden" name="imagem_atual" value="<?php echo isset($noticia['imagem']) ? $noticia['imagem'] : ''; ?>">
    <input type="hidden" name="author_id" value="<?php echo isset($noticia['author_id']) ? $noticia['author_id'] : ''; ?>">

    <label for="categoria">Categoria:</label><br>
    <select name="categoria" id="categoria">
        <?php if (!empty($categorias) && is_array($categorias)): ?>
            <?php foreach ($categorias as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo (isset($noticia['categoria']) && $noticia['categoria'] === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="">Sem categorias</option>
        <?php endif; ?>
    </select>
    <br><br>

    <button type="submit">Salvar</button>
</form>

<a href="index.php">Voltar</a>
