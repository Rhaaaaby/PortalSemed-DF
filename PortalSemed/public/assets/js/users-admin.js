const USERS_ADMIN_API = `${window.location.origin}/api`;
let usersAdminCsrfToken = '';
let currentAdminId = 0;

function showUsersAdminMessage(message, type = 'success') {
    const container = document.getElementById('users-admin-message');
    container.textContent = message;
    container.className = `message message-${type}`;
    container.style.display = 'block';
}

async function usersAdminRequest(endpoint, options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.method && options.method !== 'GET') {
        headers.set('X-CSRF-Token', usersAdminCsrfToken);
    }
    if (options.body && !(options.body instanceof FormData)) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(`${USERS_ADMIN_API}/${endpoint}`, {
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

function renderUser(user) {
    const row = document.createElement('article');
    row.className = 'portal-admin-service-row';

    const details = document.createElement('div');
    const title = document.createElement('h3');
    title.textContent = user.name;
    const summary = document.createElement('p');
    const role = user.role === 'admin' ? 'Administrador' : 'Funcionário';
    const active = user.status === true || ['1', 't', 'true'].includes(String(user.status).toLowerCase());
    summary.textContent = `${role} · CPF ${user.cpf} · ${active ? 'Ativo' : 'Desativado'}`;
    details.append(title, summary);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = active ? 'portal-admin-button portal-admin-button-danger' : 'portal-admin-button';
    button.textContent = active ? 'Desativar' : 'Ativar';
    button.disabled = Number(user.id) === currentAdminId;
    button.title = button.disabled ? 'Você não pode alterar sua própria conta.' : '';
    button.addEventListener('click', async () => {
        button.disabled = true;
        try {
            await usersAdminRequest(`admin/usuarios/${user.id}`, {
                method: 'PATCH',
                body: JSON.stringify({ status: !active }),
            });
            await loadUsers();
            showUsersAdminMessage('Status do usuário atualizado.');
        } catch (error) {
            button.disabled = false;
            showUsersAdminMessage(error.message, 'error');
        }
    });

    row.append(details, button);
    return row;
}

async function loadUsers() {
    const payload = await usersAdminRequest('admin/usuarios');
    document.getElementById('users-admin-list').replaceChildren(
        ...payload.usuarios.map(renderUser)
    );
}

window.addEventListener('DOMContentLoaded', async () => {
    try {
        const sessionResponse = await fetch(`${USERS_ADMIN_API}/auth/me`, {
            credentials: 'same-origin',
        });
        const session = await sessionResponse.json();
        if (!sessionResponse.ok || session.user.role !== 'admin') {
            window.location.href = '/login.html';
            return;
        }

        usersAdminCsrfToken = session.csrf_token;
        currentAdminId = Number(session.user.id);
        await loadUsers();
    } catch (error) {
        showUsersAdminMessage(error.message, 'error');
        return;
    }

    document.getElementById('create-user-form').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form));
        data.cpf = data.cpf.replace(/\D/g, '');

        try {
            await usersAdminRequest('admin/usuarios', {
                method: 'POST',
                body: JSON.stringify(data),
            });
            form.reset();
            await loadUsers();
            showUsersAdminMessage('Usuário criado com sucesso.');
        } catch (error) {
            showUsersAdminMessage(error.message, 'error');
        }
    });
});
