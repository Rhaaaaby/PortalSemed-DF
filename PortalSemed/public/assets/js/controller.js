function renderNoticias() {
    const container = document.getElementById('container-noticias');
    const noticias = NoticiaModel.getUltimasNoticias();

    container.innerHTML = noticias.map(n => `
        <a href="#" class="noticia-link"> <article class="card-noticia">
                <div class="img-placeholder"></div>
                <div class="card-noticia-content">
                    <h3>${n.titulo}</h3>
                    <p>${n.resumo}</p>
                </div>
            </article>
        </a>
    `).join('');
}
document.addEventListener('DOMContentLoaded', renderNoticias);