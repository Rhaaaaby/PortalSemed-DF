const PROFILE_API_URL = `${window.location.origin}/api`;
let profileCsrfToken = '';

function showProfileMessage(message, type = 'success') {
    window.portalNotify(message, type);
}

async function profileRequest(endpoint, options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.method && options.method !== 'GET') {
        headers.set('X-CSRF-Token', profileCsrfToken);
    }
    if (options.body && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(`${PROFILE_API_URL}/${endpoint}`, {
        ...options,
        headers,
        credentials: 'same-origin',
    });
    const payload = await response.json();
    if (response.status === 401) {
        window.location.href = '/login.html';
    }
    if (!response.ok) {
        throw new Error(payload.erro || 'Não foi possível concluir a operação.');
    }
    return payload;
}

window.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('profile-form');
    if (!form) return;

    try {
        const session = await profileRequest('auth/me');
        profileCsrfToken = session.csrf_token;
        const dashboardLink = document.querySelector('[data-dashboard-link]');
        if (dashboardLink) {
            dashboardLink.href = session.user.role === 'admin'
                ? '/perfil-admin.html'
                : '/perfil-funcionario.html';
        }
        const profile = await profileRequest('perfil');
        for (const field of ['name', 'cpf', 'email']) {
            form.elements.namedItem(field).value = profile.user[field] || '';
        }
    } catch (error) {
        showProfileMessage(error.message, 'error');
        return;
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const formData = new FormData(form);
        const password = String(formData.get('password') || '');
        const confirmation = String(formData.get('confirm_password') || '');
        if (password !== confirmation) {
            showProfileMessage('A confirmação da nova senha não confere.', 'error');
            return;
        }

        const data = Object.fromEntries(formData);
        delete data.confirm_password;
        if (!password) {
            delete data.password;
            delete data.current_password;
        }
        data.cpf = String(data.cpf).replace(/\D/g, '');

        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            await profileRequest('atualizar', {
                method: 'PUT',
                body: JSON.stringify(data),
            });
            form.elements.namedItem('password').value = '';
            form.elements.namedItem('current_password').value = '';
            form.elements.namedItem('confirm_password').value = '';
            const name = document.querySelector('[data-user-name]');
            if (name) name.textContent = data.name;
            showProfileMessage('Dados da conta atualizados com sucesso.');
        } catch (error) {
            showProfileMessage(error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
});
