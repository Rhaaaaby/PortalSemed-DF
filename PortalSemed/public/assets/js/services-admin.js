const SERVICES_ADMIN_API = `${window.location.origin}/api`;
let servicesAdminCsrfToken = '';

function showServicesAdminMessage(message, type = 'success') {
    const container = document.getElementById('services-admin-message');
    container.textContent = message;
    container.className = `message message-${type}`;
    container.style.display = 'block';
}

async function servicesAdminRequest(endpoint, options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.method && options.method !== 'GET') {
        headers.set('X-CSRF-Token', servicesAdminCsrfToken);
    }
    if (options.body && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(`${SERVICES_ADMIN_API}/${endpoint}`, {
        ...options,
        headers,
        credentials: 'same-origin',
    });
    const responseText = await response.text();
    let payload;
    try {
        payload = JSON.parse(responseText);
    } catch {
        throw new Error(`O servidor retornou uma resposta inválida (HTTP ${response.status}).`);
    }

    if (response.status === 401) {
        window.location.href = '/login.html';
    }
    if (!response.ok) {
        throw new Error(payload.erro || 'Não foi possível concluir a operação.');
    }
    return payload;
}

function createServiceEditor(service) {
    const row = document.createElement('article');
    row.className = 'portal-admin-service-row';

    const details = document.createElement('div');
    const title = document.createElement('h3');
    title.textContent = `${service.icon} ${service.title}`;
    const description = document.createElement('p');
    description.textContent = service.is_public ? 'Publicado' : 'Oculto';
    details.append(title, description);

    const controls = document.createElement('div');
    controls.className = 'portal-admin-service-editor';

    const fields = [
        ['title', 'Nome', service.title, 100],
        ['description', 'Descrição', service.description, 300],
        ['icon', 'Ícone', service.icon, 16],
        ['href', 'Link', service.href, 255],
    ];
    const inputs = {};
    for (const [name, label, value, maxLength] of fields) {
        const input = document.createElement('input');
        input.type = 'text';
        input.value = value;
        input.maxLength = maxLength;
        input.setAttribute('aria-label', `${label} de ${service.title}`);
        if (name === 'title' || name === 'description') input.required = true;
        inputs[name] = input;
        controls.appendChild(input);
    }

    const saveButton = document.createElement('button');
    saveButton.type = 'button';
    saveButton.className = 'portal-admin-button';
    saveButton.textContent = 'Salvar alterações';
    saveButton.addEventListener('click', async () => {
        saveButton.disabled = true;
        try {
            await servicesAdminRequest(`portal/services/${encodeURIComponent(service.slug)}`, {
                method: 'PATCH',
                body: JSON.stringify({
                    title: inputs.title.value,
                    description: inputs.description.value,
                    icon: inputs.icon.value,
                    href: inputs.href.value,
                    is_public: service.is_public,
                }),
            });
            await loadManageableServices();
            showServicesAdminMessage('Serviço atualizado.');
        } catch (error) {
            saveButton.disabled = false;
            showServicesAdminMessage(error.message, 'error');
        }
    });

    const visibilityButton = document.createElement('button');
    visibilityButton.type = 'button';
    visibilityButton.className = service.is_public
        ? 'portal-admin-button portal-admin-button-danger'
        : 'portal-admin-button';
    visibilityButton.textContent = service.is_public ? 'Ocultar' : 'Exibir';
    visibilityButton.setAttribute('aria-pressed', String(service.is_public));
    visibilityButton.addEventListener('click', async () => {
        visibilityButton.disabled = true;
        try {
            await servicesAdminRequest(`portal/services/${encodeURIComponent(service.slug)}`, {
                method: 'PATCH',
                body: JSON.stringify({ is_public: !service.is_public }),
            });
            await loadManageableServices();
            showServicesAdminMessage('Visibilidade do serviço atualizada.');
        } catch (error) {
            visibilityButton.disabled = false;
            showServicesAdminMessage(error.message, 'error');
        }
    });

    controls.append(saveButton, visibilityButton);
    row.append(details, controls);
    return row;
}

async function loadManageableServices() {
    const payload = await servicesAdminRequest('portal/services');
    document.getElementById('services-admin-list').replaceChildren(
        ...payload.services.map(createServiceEditor)
    );
}

window.addEventListener('DOMContentLoaded', async () => {
    try {
        const sessionResponse = await fetch(`${SERVICES_ADMIN_API}/auth/me`, {
            credentials: 'same-origin',
        });
        const session = await sessionResponse.json();
        if (!sessionResponse.ok || !session.user ||
            !['admin', 'funcionario'].includes(session.user.role)) {
            window.location.href = '/login.html';
            return;
        }

        servicesAdminCsrfToken = session.csrf_token;
        document.getElementById('services-admin-back').href =
            session.user.role === 'admin' ? '/perfil-admin.html' : '/perfil-funcionario.html';
        await loadManageableServices();
    } catch (error) {
        showServicesAdminMessage(error.message, 'error');
    }

    document.getElementById('create-service-form').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form));
        try {
            await servicesAdminRequest('portal/services', {
                method: 'POST',
                body: JSON.stringify(data),
            });
            form.reset();
            form.elements.namedItem('icon').value = '🔗';
            await loadManageableServices();
            showServicesAdminMessage('Serviço criado e publicado nas páginas Início e Serviços.');
        } catch (error) {
            showServicesAdminMessage(error.message, 'error');
        }
    });
});
