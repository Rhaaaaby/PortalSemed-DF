async function includeComponent(containerId, componentUrl) {
    const container = document.getElementById(containerId);
    if (!container) return;

    try {
        const response = await fetch(componentUrl);
        if (!response.ok) {
            throw new Error(`Falha ao carregar componente: ${componentUrl}`);
        }
        container.innerHTML = await response.text();
    } catch (error) {
        console.error(error);
    }
}

window.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([
        includeComponent('header', '/components/header.html'),
        includeComponent('footer', '/components/footer.html'),
    ]);

    await Promise.all([loadPortalAppearance(), updateAccountNavigation()]);
});

async function loadPortalAppearance() {
    try {
        const response = await fetch(`${window.location.origin}/api/portal`);
        if (!response.ok) {
            throw new Error(`Falha ao carregar a aparência do portal (${response.status}).`);
        }

        const { settings } = await response.json();
        const root = document.documentElement;
        root.style.setProperty('--primary-color', settings.primary_color);
        root.style.setProperty('--accent-color', settings.accent_color);
        root.style.setProperty('--background-color', settings.background_color);
        root.style.setProperty('--text-dark', settings.text_color);

        const logo = document.getElementById('portal-logo');
        if (logo && settings.logo_path) {
            logo.src = settings.logo_path;
        }
    } catch (error) {
        console.error('Não foi possível aplicar a aparência salva do portal:', error);
    }
}

async function updateAccountNavigation() {
    try {
        const response = await fetch(`${window.location.origin}/api/auth/me`, {
            credentials: 'same-origin',
        });

        if (response.status === 401) return;
        if (!response.ok) {
            throw new Error(`Falha ao validar a sessão (${response.status}).`);
        }

        const { user, csrf_token: csrfToken } = await response.json();
        window.portalCsrfToken = csrfToken;

        const accountLink = document.getElementById('account-link');
        if (accountLink) {
            accountLink.href = user.role === 'admin' ? '/perfil-admin.html' : '/perfil-funcionario.html';
            accountLink.textContent = user.role === 'admin' ? 'Painel administrativo' : 'Painel do funcionário';
        }

        const navigation = document.querySelector('header nav ul');
        if (navigation && !navigation.querySelector('[data-logout]')) {
            const item = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.logout = 'true';
            button.className = 'btn-conta';
            button.textContent = 'Sair';
            item.appendChild(button);
            navigation.appendChild(item);
        }

        const userName = document.querySelector('[data-user-name]');
        if (userName) userName.textContent = user.name;
    } catch (error) {
        console.error('Não foi possível validar a sessão do usuário:', error);
    }
}

document.addEventListener('click', async event => {
    if (!(event.target instanceof Element)) return;

    const navigationToggle = event.target.closest('[data-nav-toggle]');
    if (navigationToggle) {
        const header = navigationToggle.closest('header');
        if (!header) return;

        const isExpanded = navigationToggle.getAttribute('aria-expanded') === 'true';
        navigationToggle.setAttribute('aria-expanded', String(!isExpanded));
        navigationToggle.setAttribute('aria-label', isExpanded ? 'Abrir menu de navegação' : 'Fechar menu de navegação');
        header.classList.toggle('nav-open', !isExpanded);
        return;
    }

    const navigationLink = event.target.closest('header nav a');
    if (navigationLink) {
        closeNavigationMenu(navigationLink.closest('header'));
    }

    const button = event.target.closest('[data-logout]');
    if (!button) return;

    button.disabled = true;
    try {
        const response = await fetch(`${window.location.origin}/api/auth/logout`, {
            method: 'POST',
            headers: { 'X-CSRF-Token': window.portalCsrfToken || '' },
            credentials: 'same-origin',
        });
        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.erro || 'Não foi possível encerrar a sessão.');
        }

        window.location.href = '/login.html';
    } catch (error) {
        button.disabled = false;
        console.error(error);
        window.alert(error.message || 'Não foi possível encerrar a sessão.');
    }
});

document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;

    const header = document.querySelector('header.nav-open');
    if (!header) return;

    const navigationToggle = header.querySelector('[data-nav-toggle]');
    closeNavigationMenu(header);
    navigationToggle?.focus();
});

function closeNavigationMenu(header) {
    const navigationToggle = header?.querySelector('[data-nav-toggle]');
    if (!header || !navigationToggle) return;

    header.classList.remove('nav-open');
    navigationToggle.setAttribute('aria-expanded', 'false');
    navigationToggle.setAttribute('aria-label', 'Abrir menu de navegação');
}
