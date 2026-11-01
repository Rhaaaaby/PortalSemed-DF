function createPortalService(service, isServicePage = false) {
    const card = document.createElement('article');
    card.className = isServicePage ? 'servico-card' : 'portal-service-card';

    const title = document.createElement('h3');
    title.textContent = `${service.icon} ${service.title}`;
    card.appendChild(title);

    const description = document.createElement('p');
    description.textContent = service.description;
    card.appendChild(description);

    if (isServicePage) {
        const link = document.createElement('a');
        link.className = 'btn-servico verde';
        link.href = service.href;
        link.textContent = 'Acessar';
        card.appendChild(link);
        return card;
    }

    const wrapper = document.createElement('a');
    wrapper.className = 'portal-service-card-link';
    wrapper.href = service.href;
    wrapper.setAttribute('aria-label', `Acessar ${service.title}`);
    wrapper.appendChild(card);
    return wrapper;
}

function renderPortalCarousel(items) {
    const container = document.getElementById('portal-carousel');
    if (!container || items.length === 0) return;

    let currentIndex = 0;
    const image = document.createElement('img');
    image.className = 'portal-carousel-image';
    image.alt = '';
    container.appendChild(image);

    const caption = document.createElement('p');
    caption.className = 'portal-carousel-caption';
    container.appendChild(caption);

    const showItem = () => {
        const item = items[currentIndex];
        image.src = item.image_path;
        image.alt = item.alt_text;
        caption.textContent = item.caption;
        caption.hidden = !item.caption;
        container.dataset.currentItem = String(currentIndex + 1);
    };

    if (items.length > 1) {
        const previous = document.createElement('button');
        previous.type = 'button';
        previous.className = 'portal-carousel-control portal-carousel-previous';
        previous.setAttribute('aria-label', 'Imagem anterior');
        previous.textContent = '‹';
        previous.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + items.length) % items.length;
            showItem();
        });

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'portal-carousel-control portal-carousel-next';
        next.setAttribute('aria-label', 'Próxima imagem');
        next.textContent = '›';
        next.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % items.length;
            showItem();
        });

        container.append(previous, next);
    }

    showItem();
}

async function loadPortalContent() {
    try {
        const response = await fetch(`${window.location.origin}/api/portal`);
        if (!response.ok) {
            throw new Error(`Falha ao carregar os serviços do portal (${response.status}).`);
        }

        const content = await response.json();
        const homeServices = document.getElementById('home-services');
        if (homeServices) {
            homeServices.replaceChildren(...content.services.map(service => createPortalService(service)));
        }

        const servicesPage = document.getElementById('services-page-grid');
        if (servicesPage) {
            servicesPage.replaceChildren(...content.services.map(service => createPortalService(service, true)));
        }

        renderPortalCarousel(content.carousel);
    } catch (error) {
        console.error('Não foi possível carregar o conteúdo público do portal:', error);
    }
}

window.addEventListener('DOMContentLoaded', loadPortalContent);
