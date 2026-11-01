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

// Função para lidar com a troca de senha
document.addEventListener('DOMContentLoaded', function() {
    const formTrocaSenha = document.getElementById('formTrocaSenha');

    if (!formTrocaSenha) return;

    formTrocaSenha.addEventListener('submit', async function(event) {
        event.preventDefault();

        const inputAtual = document.getElementById('senhaAtual');
        const inputNova = document.getElementById('novaSenha');
        const inputConfirmar = document.getElementById('confirmarSenha');

        if (!inputAtual || !inputNova || !inputConfirmar) {
            alert('Erro: Campos de senha não foram encontrados na página.');
            return;
        }

        const senhaAtual = inputAtual.value;
        const novaSenha = inputNova.value;
        const confirmarSenha = inputConfirmar.value;

        if (novaSenha !== confirmarSenha) {
            alert('A nova senha e a confirmação não coincidem!');
            return;
        }

        // Alterarei de getItem('token') para getItem('authToken'):
        const token = localStorage.getItem('authToken');
        if (!token) {
            alert('Sessão expirada. Faça login novamente.');
            window.location.href = '/login.html';
            return;
        }

        try {
            const response = await fetch('/api/router.php?action=alterar-senha', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({ senhaAtual, novaSenha })
            });

            const resultado = await response.json();

            if (response.ok) {
                alert(resultado.mensagem || 'Senha alterada com sucesso!');
                formTrocaSenha.reset();
                document.getElementById('containerTrocaSenha').style.display = 'none';
            } else {
                alert(resultado.mensagem || 'Erro ao alterar a senha.');
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            alert('Erro de conexão com o servidor.');
        }
    });
});