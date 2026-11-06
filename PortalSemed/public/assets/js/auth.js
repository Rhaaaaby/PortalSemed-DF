const API_BASE_URL = `${window.location.origin}/api`;

function showMessage(container, message, type = 'success') {
    if (!container) return;
    container.textContent = message;
    container.className = `message message-${type}`;
    container.style.display = 'block';
}

function clearMessage(container) {
    if (!container) return;
    container.textContent = '';
    container.className = '';
    container.style.display = 'none';
}

function getFormData(form) {
    return Array.from(form.elements)
        .filter(field => field.name && !field.disabled)
        .reduce((data, field) => {
            if (field.type !== 'radio' || field.checked) {
                data[field.name] = field.value;
            }
            return data;
        }, {});
}

function normalizeCpf(value) {
    return String(value).replace(/\D/g, '');
}

async function login(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const message = document.getElementById('login-message');
    clearMessage(message);

    const credentials = getFormData(form);
    credentials.cpf = normalizeCpf(credentials.cpf || '');
    if (!credentials.cpf || !credentials.password) {
        showMessage(message, 'CPF e senha são obrigatórios.', 'error');
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(credentials),
        });
        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.erro || 'Não foi possível entrar.');
        }

        showMessage(message, 'Login realizado com sucesso. Redirecionando...', 'success');
        const destination = payload.user.role === 'admin'
            ? 'perfil-admin.html'
            : 'perfil-funcionario.html';
        window.setTimeout(() => {
            window.location.href = destination;
        }, 400);
    } catch (error) {
        showMessage(message, error.message || 'Não foi possível entrar.', 'error');
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', login);
    }
});
