<form method="POST" enctype="multipart/form-data">
    <h2>Nova Notícia</h2>

    <input type="text" name="titulo" placeholder="Título" required><br><br>

    <textarea name="conteudo" placeholder="Conteúdo"></textarea><br><br>

    <input type="file" name="imagem"><br><br>


    <!-- <input type="hidden" name="author_id" value="<?php echo isset($id) ? $id : ''; ?>"> -->

    <input type="hidden" name="author_id" value="1"> <!-- Substitua '1' pelo ID do autor real -->

    <label for="categoria">Categoria:</label><br>
    <select name="categoria" id="categoria">
        <?php if (!empty($categorias) && is_array($categorias)): ?>
            <?php foreach ($categorias as $c): ?>
                <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
            <?php endforeach; ?>
        <?php else: ?>
            <option value="">Sem categorias</option>
        <?php endif; ?>
    </select>
    <br><br>

    <button type="submit">Salvar</button>
</form>

<a href="index.php">Voltar</a>