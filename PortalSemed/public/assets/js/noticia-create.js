document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('noticia-form');

    if (!form) {
        return;
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const token = localStorage.getItem('authToken');

        if (!token) {
            alert('Você precisa estar logado para publicar uma notícia.');
            window.location.href = '/assets/pages/login.html';
            return;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch('/api/noticias', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token}`
                },
                body: formData
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

            alert(data.mensagem);

        } catch (error) {
            console.error('Erro ao publicar notícia:', error);
            alert(error.message);
        }
    });
});