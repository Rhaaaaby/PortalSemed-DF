document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('noticia-form');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);

        try {
            const sessionResponse = await fetch('/api/auth/me', {
                credentials: 'same-origin',
            });
            const session = await sessionResponse.json();
            if (!sessionResponse.ok) {
                throw new Error(session.erro || 'Faça login para publicar uma notícia.');
            }

            const response = await fetch('/api/noticias', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': session.csrf_token
                },
                body: formData,
                credentials: 'same-origin',
            });

            const text = await response.text();

            console.log('Status:', response.status);
            console.log('Resposta do servidor:', text);

            let data;

            try {
                data = JSON.parse(text);
            } catch (error) {
                console.error('Resposta não é JSON:', text);
                throw new Error('O servidor retornou uma resposta inválida.');
            }

            if (!response.ok) {
                throw new Error(data.erro || 'Erro ao publicar notícia.');
            }

            alert(data.mensagem || 'Notícia criada com sucesso!');
            window.location.href = '/noticias';

        } catch (error) {
            console.error('Erro ao publicar notícia:', error);
            alert(error.message);
        }
    });
});