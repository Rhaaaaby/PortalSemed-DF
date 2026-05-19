async function renderNoticias() {
    const container = document.getElementById('container-noticias');
    if (!container) return;

    try {
        const noticias = await NoticiaModel.getUltimasNoticias();

        container.innerHTML = noticias.map(n => {
            const imageStyle = n.imagem ? `background-image: url('${n.imagem}'); background-size: cover; background-position: center;` : '';
            return `
                <a href="${n.id ? '/noticias?action=view&id=' + n.id : '#'}" class="noticia-link">
                    <article class="card-noticia">
                        <div class="img-placeholder" style="${imageStyle}">
                            ${!n.imagem ? '<span style="color:#777; font-size:0.8rem; display:flex; justify-content:center; align-items:center; height:100%;">SEMED Informa</span>' : ''}
                        </div>
                        <div class="card-noticia-content">
                            <h3>${n.titulo}</h3>
                            <p>${n.resumo}</p>
                        </div>
                    </article>
                </a>
            `;
        }).join('');
    } catch (e) {
        console.error(e);
        container.innerHTML = '<p>Erro ao exibir as notícias.</p>';
    }
}
document.addEventListener('DOMContentLoaded', renderNoticias);