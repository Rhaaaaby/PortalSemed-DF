async function renderNoticias() {
    const container = document.getElementById('container-noticias');
    if (!container) return;

    try {
        const noticias = await NoticiaModel.getUltimasNoticias();
        const cards = noticias.map(noticia => {
            const link = document.createElement('a');
            link.href = noticia.id ? `/noticias?action=view&id=${encodeURIComponent(noticia.id)}` : '#';
            link.className = 'noticia-link';

            const card = document.createElement('article');
            card.className = 'card-noticia';
            const imageContainer = document.createElement('div');
            imageContainer.className = 'img-placeholder';
            if (noticia.imagem) {
                const image = document.createElement('img');
                image.src = noticia.imagem;
                image.alt = noticia.titulo;
                imageContainer.appendChild(image);
            } else {
                const placeholder = document.createElement('span');
                placeholder.textContent = 'SEMED Informa';
                imageContainer.appendChild(placeholder);
            }

            const content = document.createElement('div');
            content.className = 'card-noticia-content';
            const title = document.createElement('h3');
            title.textContent = noticia.titulo;
            const summary = document.createElement('p');
            summary.textContent = noticia.resumo;
            content.append(title, summary);
            card.append(imageContainer, content);
            link.appendChild(card);
            return link;
        });
        container.replaceChildren(...cards);
        setupNewsCarousel(container);
    } catch (e) {
        console.error(e);
        const error = document.createElement('p');
        error.textContent = 'Erro ao exibir as notícias.';
        container.replaceChildren(error);
    }
}

function setupNewsCarousel(track) {
    const carousel = document.getElementById('news-carousel');
    const cards = Array.from(track.children);
    const previous = carousel?.querySelector('.news-carousel-previous');
    const next = carousel?.querySelector('.news-carousel-next');
    if (!carousel || cards.length < 2 || track.scrollWidth <= track.clientWidth + 1) {
        previous?.setAttribute('hidden', '');
        next?.setAttribute('hidden', '');
        return;
    }

    const scrollByCard = direction => {
        const distance = cards[0].getBoundingClientRect().width + 20;
        track.scrollBy({ left: direction * distance, behavior: 'smooth' });
    };
    previous.addEventListener('click', () => scrollByCard(-1));
    next.addEventListener('click', () => scrollByCard(1));

    if (window.matchMedia('(min-width: 761px) and (prefers-reduced-motion: no-preference)').matches) {
        const advance = () => {
            const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
            track.scrollTo({
                left: atEnd ? 0 : track.scrollLeft + cards[0].getBoundingClientRect().width + 20,
                behavior: 'smooth',
            });
        };
        let timer = window.setInterval(advance, 5000);
        const pause = () => {
            window.clearInterval(timer);
            timer = 0;
        };
        const resume = () => {
            if (!timer) timer = window.setInterval(advance, 5000);
        };
        carousel.addEventListener('mouseenter', pause);
        carousel.addEventListener('mouseleave', resume);
        carousel.addEventListener('focusin', pause);
        carousel.addEventListener('focusout', resume);
    }
}

document.addEventListener('DOMContentLoaded', renderNoticias);