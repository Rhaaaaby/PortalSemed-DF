const PORTAL_ADMIN_API = `${window.location.origin}/api`;
let portalAdminCsrfToken = '';

function showPortalAdminMessage(message, type = 'success') {
    window.portalNotify(message, type);
}

async function portalAdminRequest(endpoint, options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.method && options.method !== 'GET') {
        headers.set('X-CSRF-Token', portalAdminCsrfToken);
    }

    if (options.body && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(`${PORTAL_ADMIN_API}/${endpoint}`, {
        ...options,
        headers,
        credentials: 'same-origin',
    });

    const responseText = await response.text();
    let payload;
    try {
        payload = JSON.parse(responseText);
    } catch {
        throw new Error(`O servidor retornou uma resposta inválida (HTTP ${response.status}). Consulte os logs.`);
    }

    if (response.status === 401) {
        window.location.href = '/login.html';
    }
    if (!response.ok) {
        throw new Error(payload.erro || 'Não foi possível concluir a operação.');
    }

    return payload;
}

function applyPortalAdminSettings(settings) {
    const form = document.getElementById('theme-form');
    const socialForm = document.getElementById('social-form');
    const contactForm = document.getElementById('contact-form');
    for (const [field, value] of Object.entries(settings)) {
        const input = form.elements.namedItem(field);
        if (input) input.value = value;
        const socialInput = socialForm.elements.namedItem(field);
        if (socialInput) socialInput.value = value;
        const contactInput = contactForm.elements.namedItem(field);
        if (contactInput) contactInput.value = value;
    }

    const root = document.documentElement;
    root.style.setProperty('--primary-color', settings.primary_color);
    root.style.setProperty('--accent-color', settings.accent_color);
    root.style.setProperty('--background-color', settings.background_color);
    root.style.setProperty('--text-dark', settings.text_color);
}

function makeAdminCarouselCard(item) {
    const card = document.createElement('article');
    card.className = 'portal-admin-carousel-card';

    const image = document.createElement('img');
    image.src = item.image_path;
    image.alt = item.alt_text;
    card.appendChild(image);

    if (item.caption) {
        const caption = document.createElement('p');
        caption.textContent = item.caption;
        card.appendChild(caption);
    }

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'portal-admin-button portal-admin-button-danger';
    remove.textContent = 'Remover imagem';
    remove.addEventListener('click', async () => {
        if (!window.confirm('Remover esta imagem do carrossel público?')) return;

        try {
            await portalAdminRequest(`admin/portal/carousel/${item.id}`, { method: 'DELETE' });
            await loadPortalAdminContent();
            showPortalAdminMessage('Imagem removida do carrossel.');
        } catch (error) {
            showPortalAdminMessage(error.message, 'error');
        }
    });
    card.appendChild(remove);
    return card;
}

function makeAdminServiceRow(service) {
    const row = document.createElement('article');
    row.className = 'portal-admin-service-row';

    const details = document.createElement('div');
    const title = document.createElement('h3');
    title.textContent = `${service.icon} ${service.title}`;
    const description = document.createElement('p');
    description.textContent = service.description;
    details.append(title, description);

    const controls = document.createElement('div');
    controls.className = 'portal-admin-service-controls';

    const hrefInput = document.createElement('input');
    hrefInput.type = 'text';
    hrefInput.maxLength = 255;
    hrefInput.value = service.href;
    hrefInput.setAttribute('aria-label', `Link de destino de ${service.title}`);

    const saveHref = document.createElement('button');
    saveHref.type = 'button';
    saveHref.className = 'portal-admin-button';
    saveHref.textContent = 'Salvar link';
    saveHref.addEventListener('click', async () => {
        saveHref.disabled = true;
        try {
            await portalAdminRequest(`admin/portal/services/${encodeURIComponent(service.slug)}`, {
                method: 'PATCH',
                body: JSON.stringify({ is_public: service.is_public, href: hrefInput.value }),
            });
            await loadPortalAdminContent();
            showPortalAdminMessage('Link atualizado com sucesso.');
        } catch (error) {
            saveHref.disabled = false;
            showPortalAdminMessage(error.message, 'error');
        }
    });

    const visibilityButton = document.createElement('button');
    visibilityButton.type = 'button';
    visibilityButton.className = service.is_public ? 'portal-admin-button portal-admin-button-danger' : 'portal-admin-button';
    visibilityButton.textContent = service.is_public ? 'Ocultar botão' : 'Exibir botão';
    visibilityButton.setAttribute('aria-pressed', String(service.is_public));
    visibilityButton.addEventListener('click', async () => {
        visibilityButton.disabled = true;
        try {
            await portalAdminRequest(`admin/portal/services/${encodeURIComponent(service.slug)}`, {
                method: 'PATCH',
                body: JSON.stringify({ is_public: !service.is_public }),
            });
            await loadPortalAdminContent();
            showPortalAdminMessage('Visibilidade do botão atualizada.');
        } catch (error) {
            visibilityButton.disabled = false;
            showPortalAdminMessage(error.message, 'error');
        }
    });

    controls.append(hrefInput, saveHref, visibilityButton);
    row.append(details, controls);
    return row;
}

async function loadPortalAdminContent() {
    const content = await portalAdminRequest('admin/portal');
    applyPortalAdminSettings(content.settings);

    document.getElementById('portal-admin-carousel').replaceChildren(
        ...content.carousel.map(makeAdminCarouselCard)
    );
    document.getElementById('portal-admin-services').replaceChildren(
        ...content.services.map(makeAdminServiceRow)
    );
}

window.addEventListener('DOMContentLoaded', async () => {
    try {
        const sessionResponse = await fetch(`${PORTAL_ADMIN_API}/auth/me`, {
            credentials: 'same-origin',
        });
        const session = await sessionResponse.json();
        if (!sessionResponse.ok || !session.user || session.user.role !== 'admin') {
            window.location.href = '/login.html';
            return;
        }
        portalAdminCsrfToken = session.csrf_token;
        await loadPortalAdminContent();
    } catch (error) {
        showPortalAdminMessage(error.message, 'error');
    }

    document.getElementById('theme-form').addEventListener('submit', async event => {
        event.preventDefault();
        const settings = Object.fromEntries(new FormData(event.currentTarget));
        try {
            await portalAdminRequest('admin/portal/settings', {
                method: 'PUT',
                body: JSON.stringify(settings),
            });
            await loadPortalAdminContent();
            showPortalAdminMessage('Cores atualizadas com sucesso.');
        } catch (error) {
            showPortalAdminMessage(error.message, 'error');
        }
    });

    document.getElementById('logo-form').addEventListener('submit', async event => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        try {
            const result = await portalAdminRequest('admin/portal/logo', { method: 'POST', body: formData });
            const logo = document.getElementById('portal-logo');
            if (logo) logo.src = `${result.logo_path}?v=${Date.now()}`;
            event.currentTarget.reset();
            showPortalAdminMessage('Logo atualizada com sucesso.');
        } catch (error) {
            showPortalAdminMessage(error.message, 'error');
        }
    });

    document.getElementById('carousel-form').addEventListener('submit', async event => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        try {
            await portalAdminRequest('admin/portal/carousel', { method: 'POST', body: formData });
            event.currentTarget.reset();
            await loadPortalAdminContent();
            showPortalAdminMessage('Imagem adicionada ao carrossel.');
        } catch (error) {
            showPortalAdminMessage(error.message, 'error');
        }
    });

    document.getElementById('social-form').addEventListener('submit', async event => {
        event.preventDefault();
        const links = Object.fromEntries(new FormData(event.currentTarget));
        try {
            await portalAdminRequest('admin/portal/social-links', {
                method: 'PUT',
                body: JSON.stringify(links),
            });
            await loadPortalAdminContent();
            showPortalAdminMessage('Links das redes sociais atualizados.');
        } catch (error) {
            showPortalAdminMessage(error.message, 'error');
        }
    });

    document.getElementById('contact-form').addEventListener('submit', async event => {
        event.preventDefault();
        const contact = Object.fromEntries(new FormData(event.currentTarget));
        try {
            await portalAdminRequest('admin/portal/contact', {
                method: 'PUT',
                body: JSON.stringify(contact),
            });
            await loadPortalAdminContent();
            showPortalAdminMessage('Informações de contato atualizadas.');
        } catch (error) {
            showPortalAdminMessage(error.message, 'error');
        }
    });
});
