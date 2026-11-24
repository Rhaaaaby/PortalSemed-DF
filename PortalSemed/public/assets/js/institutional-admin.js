const INSTITUTIONAL_API = `${window.location.origin}/api`;
let institutionalCsrf = '';
let faqItems = [];
let navigationLinks = [];
let institutionalPermissions = {};
let institutionalIsAdmin = false;

async function institutionalRequest(path, options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.method && options.method !== 'GET') headers.set('X-CSRF-Token', institutionalCsrf);
    if (options.body && !(options.body instanceof FormData)) headers.set('Content-Type', 'application/json');
    const response = await fetch(`${INSTITUTIONAL_API}/${path}`, {
        ...options,
        headers,
        credentials: 'same-origin',
    });
    const payload = await response.json();
    if (response.status === 401) window.location.href = '/login.html';
    if (!response.ok) throw new Error(payload.erro || 'Não foi possível concluir a operação.');
    return payload;
}

function showInstitutionalMessage(message, type = 'success') {
    window.portalNotify(message, type);
}

function renderFaqEditor() {
    const list = document.getElementById('faq-editor-list');
    list.replaceChildren(...faqItems.map((item, index) => {
        const row = document.createElement('article');
        row.className = 'portal-admin-service-row';
        const fields = document.createElement('div');
        fields.className = 'institutional-editor-fields';
        const question = document.createElement('input');
        question.value = item.question;
        question.maxLength = 300;
        question.setAttribute('aria-label', 'Pergunta');
        question.addEventListener('input', () => { item.question = question.value; });
        const answer = document.createElement('textarea');
        answer.value = item.answer;
        answer.maxLength = 10000;
        answer.rows = 4;
        answer.setAttribute('aria-label', 'Resposta');
        answer.addEventListener('input', () => { item.answer = answer.value; });
        fields.append(question, answer);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'portal-admin-button portal-admin-button-danger';
        remove.textContent = 'Remover';
        remove.addEventListener('click', () => {
            faqItems.splice(index, 1);
            renderFaqEditor();
        });
        row.append(fields, remove);
        return row;
    }));
}

function renderAboutImages(images) {
    const list = document.getElementById('about-image-list');
    list.replaceChildren(...images.map(item => {
        const card = document.createElement('article');
        card.className = 'portal-admin-carousel-card';
        const image = document.createElement('img');
        image.src = item.image_path;
        image.alt = item.alt_text;
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'portal-admin-button portal-admin-button-danger';
        remove.textContent = 'Remover foto';
        remove.addEventListener('click', async () => {
            try {
                await institutionalRequest(`admin/portal/about/images/${item.id}`, { method: 'DELETE' });
                await loadInstitutionalContent();
                showInstitutionalMessage('Foto removida.');
            } catch (error) {
                showInstitutionalMessage(error.message, 'error');
            }
        });
        card.append(image, remove);
        return card;
    }));
}

function renderNavigationLinks() {
    const list = document.getElementById('navigation-links-list');
    list.replaceChildren(...navigationLinks.map((item, index) => {
        const row = document.createElement('article');
        row.className = 'portal-admin-service-row';
        const fields = document.createElement('div');
        fields.className = 'institutional-editor-fields';
        const location = document.createElement('select');
        location.innerHTML = '<option value="header">Cabeçalho</option><option value="footer">Rodapé</option>';
        location.value = item.location;
        location.setAttribute('aria-label', 'Local do link');
        location.addEventListener('change', () => { item.location = location.value; });
        const label = document.createElement('input');
        label.value = item.label;
        label.maxLength = 80;
        label.setAttribute('aria-label', 'Nome do link');
        label.addEventListener('input', () => { item.label = label.value; });
        const href = document.createElement('input');
        href.value = item.href;
        href.maxLength = 255;
        href.setAttribute('aria-label', 'Endereço do link');
        href.addEventListener('input', () => { item.href = href.value; });
        fields.append(location, label, href);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'portal-admin-button portal-admin-button-danger';
        remove.textContent = 'Remover';
        remove.addEventListener('click', () => {
            navigationLinks.splice(index, 1);
            renderNavigationLinks();
        });
        row.append(fields, remove);
        return row;
    }));
}

async function loadInstitutionalContent() {
    const content = await institutionalRequest('admin/portal');
    institutionalPermissions = content.permissions;
    faqItems = content.faq.map(item => ({ question: item.question, answer: item.answer }));
    navigationLinks = content.navigation.map(item => ({
        location: item.location,
        label: item.label,
        href: item.href,
    }));
    document.querySelectorAll('[data-permission]').forEach(section => {
        const permitted = institutionalIsAdmin || institutionalPermissions[section.dataset.permission];
        section.hidden = !permitted;
    });

    const aboutForm = document.getElementById('about-form');
    aboutForm.elements.namedItem('title').value = content.about.title;
    aboutForm.elements.namedItem('body').value = content.about.body;
    renderFaqEditor();
    renderAboutImages(content.about.images);
    renderNavigationLinks();
}

window.addEventListener('DOMContentLoaded', async () => {
    try {
        const sessionResponse = await fetch(`${INSTITUTIONAL_API}/auth/me`, { credentials: 'same-origin' });
        const session = await sessionResponse.json();
        if (!sessionResponse.ok || !session.user) {
            window.location.href = '/login.html';
            return;
        }
        institutionalCsrf = session.csrf_token;
        institutionalIsAdmin = session.user.role === 'admin';
        if (!institutionalIsAdmin) {
            document.getElementById('institutional-back').href = '/perfil-funcionario.html';
        }
        await loadInstitutionalContent();
    } catch (error) {
        showInstitutionalMessage(error.message, 'error');
        return;
    }

    document.getElementById('faq-add-form').addEventListener('submit', event => {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form));
        faqItems.push(data);
        form.reset();
        renderFaqEditor();
    });
    document.getElementById('faq-save').addEventListener('click', async event => {
        event.currentTarget.disabled = true;
        try {
            await institutionalRequest('admin/portal/faq', {
                method: 'PUT',
                body: JSON.stringify({ items: faqItems }),
            });
            await loadInstitutionalContent();
            showInstitutionalMessage('FAQ salvo.');
        } catch (error) {
            showInstitutionalMessage(error.message, 'error');
        } finally {
            event.currentTarget.disabled = false;
        }
    });

    document.getElementById('about-form').addEventListener('submit', async event => {
        event.preventDefault();
        const data = Object.fromEntries(new FormData(event.currentTarget));
        try {
            await institutionalRequest('admin/portal/about', { method: 'PUT', body: JSON.stringify(data) });
            showInstitutionalMessage('Página Sobre salva.');
        } catch (error) {
            showInstitutionalMessage(error.message, 'error');
        }
    });
    document.getElementById('about-image-form').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        try {
            await institutionalRequest('admin/portal/about/images', {
                method: 'POST',
                body: new FormData(form),
            });
            form.reset();
            await loadInstitutionalContent();
            showInstitutionalMessage('Foto adicionada.');
        } catch (error) {
            showInstitutionalMessage(error.message, 'error');
        }
    });

    document.getElementById('navigation-add-form').addEventListener('submit', event => {
        event.preventDefault();
        const form = event.currentTarget;
        navigationLinks.push(Object.fromEntries(new FormData(form)));
        form.reset();
        renderNavigationLinks();
    });
    document.getElementById('navigation-save').addEventListener('click', async event => {
        event.currentTarget.disabled = true;
        try {
            await institutionalRequest('admin/portal/navigation', {
                method: 'PUT',
                body: JSON.stringify({ links: navigationLinks }),
            });
            await loadInstitutionalContent();
            showInstitutionalMessage('Links salvos.');
        } catch (error) {
            showInstitutionalMessage(error.message, 'error');
        } finally {
            event.currentTarget.disabled = false;
        }
    });
});
