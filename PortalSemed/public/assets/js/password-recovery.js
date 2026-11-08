const PASSWORD_RECOVERY_API = `${window.location.origin}/api`;

async function submitPasswordRecovery(endpoint, data) {
    const response = await fetch(`${PASSWORD_RECOVERY_API}/${endpoint}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });

    const payload = await response.json();
    if (!response.ok) {
        throw new Error(payload.erro || 'Não foi possível concluir a solicitação.');
    }

    return payload;
}

window.addEventListener('DOMContentLoaded', () => {
    const token = new URLSearchParams(window.location.search).get('token');
    const requestSection = document.getElementById('recovery-request');
    const resetSection = document.getElementById('recovery-reset');
    requestSection.hidden = Boolean(token);
    resetSection.hidden = !token;

    document.getElementById('recovery-request-form').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const result = await submitPasswordRecovery('password/recovery', {
                email: form.elements.namedItem('email').value,
            });
            window.portalNotify(result.mensagem, 'success');
            form.reset();
        } catch (error) {
            window.portalNotify(error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });

    document.getElementById('recovery-reset-form').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const password = form.elements.namedItem('password').value;
        if (password !== form.elements.namedItem('confirm_password').value) {
            window.portalNotify('A confirmação da nova senha não confere.', 'error');
            return;
        }

        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const result = await submitPasswordRecovery('password/reset', { token, password });
            window.portalQueueNotification(result.mensagem, 'success');
            window.location.href = '/login.html';
        } catch (error) {
            window.portalNotify(error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
});
