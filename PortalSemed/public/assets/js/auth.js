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

async function postJson(endpoint, data) {
    const response = await fetch(`${API_BASE_URL}/${endpoint}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    });

    const text = await response.text();

    console.log('Status:', response.status);
    console.log('Resposta do servidor:', text);

    let payload;

    try {
        payload = JSON.parse(text);
    } catch (error) {
        throw {
            erro: `O servidor retornou uma resposta inválida. Status: ${response.status}`,
            resposta: text
        };
    }

    if (!response.ok) {
        throw payload;
    }

    return payload;
}

function getFormData(form) {
    return Array.from(form.elements)
        .filter(el => el.name && !el.disabled)
        .reduce((data, field) => {
            if (field.type === 'radio') {
                if (field.checked) {
                    data[field.name] = field.value;
                }
            } else {
                data[field.name] = field.value;
            }
            return data;
        }, {});
}

function normalizeCpf(value) {
    return String(value).replace(/\D/g, '');
}

function handleRegister(event) {
    event.preventDefault();
    const form = event.target;
    const messageContainer = document.getElementById('register-message');
    clearMessage(messageContainer);

    const formData = getFormData(form);
    formData.cpf = normalizeCpf(formData.cpf);
    formData.role = formData.role || 'user';

    if (!formData.name || !formData.cpf || !formData.password) {
        showMessage(messageContainer, 'Preencha todos os campos obrigatórios.', 'error');
        return;
    }

    if (typeof STORAGE_MODE !== 'undefined' && STORAGE_MODE === 'localstorage') {
        const users = JSON.parse(localStorage.getItem('users')) || [];
        const userExists = users.some(u => u.cpf === formData.cpf);
        if (userExists) {
            showMessage(messageContainer, 'Este CPF já está cadastrado', 'error');
            return;
        }

        const newUser = {
            id: Date.now(),
            name: formData.name,
            cpf: formData.cpf,
            password: formData.password,
            role: formData.role
        };
        users.push(newUser);
        localStorage.setItem('users', JSON.stringify(users));

        showMessage(messageContainer, 'Cadastro realizado com sucesso! Redirecionando...', 'success');
        setTimeout(() => window.location.href = 'login.html', 1200);
        return;
    }

    postJson('cadastrar', formData)
        .then(() => {
            showMessage(messageContainer, 'Cadastro realizado com sucesso! Redirecionando...', 'success');
            setTimeout(() => window.location.href = 'login.html', 1200);
        })
        .catch(error => {
            const errorText = error.erro || error.message || 'Erro ao registrar usuário.';
            showMessage(messageContainer, errorText, 'error');
        });
}

function handleLogin(event) {
    event.preventDefault();
    const form = event.target;
    const messageContainer = document.getElementById('login-message');
    clearMessage(messageContainer);

    const formData = getFormData(form);
    formData.cpf = normalizeCpf(formData.cpf);

    if (!formData.cpf || !formData.password) {
        showMessage(messageContainer, 'CPF e senha são obrigatórios.', 'error');
        return;
    }

    if (typeof STORAGE_MODE !== 'undefined' && STORAGE_MODE === 'localstorage') {
        const users = JSON.parse(localStorage.getItem('users')) || [];
        const user = users.find(u => u.cpf === formData.cpf && u.password === formData.password);

        if (!user) {
            showMessage(messageContainer, 'Credenciais inválidas.', 'error');
            return;
        }

        const mockToken = 'mock-jwt-token-' + Date.now();
        localStorage.setItem('authToken', mockToken);
        localStorage.setItem('authUser', JSON.stringify(user));
        showMessage(messageContainer, 'Login realizado com sucesso! Redirecionando...', 'success');
        
        setTimeout(() => {
            if (user.role === 'servidor' || user.role === 'admin') {
                window.location.href = 'perfil-admin.html';
            } else if (user.role === 'funcionario') {
                window.location.href = 'perfil-funcionario.html';
            } else {
                window.location.href = 'index.html';
            }
        }, 1000);
        return;
    }

    postJson('login', formData)
        .then(result => {
            localStorage.setItem('authToken', result.token);
            localStorage.setItem('authUser', JSON.stringify(result.user));
            showMessage(messageContainer, 'Login realizado com sucesso! Redirecionando...', 'success');
            
            setTimeout(() => {
                const user = result.user;
                if (user.role === 'servidor' || user.role === 'admin') {
                    window.location.href = 'perfil-admin.html';
                } else if (user.role === 'funcionario') {
                    window.location.href = 'perfil-funcionario.html';
                } else {
                    window.location.href = 'index.html';
                }
            }, 1000);
        })
        .catch(error => {
            const errorText = error.erro || error.message || 'Credenciais inválidas.';
            showMessage(messageContainer, errorText, 'error');
        });
}

window.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
});

