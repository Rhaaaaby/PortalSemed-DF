function renderFaq(items) {
    const container = document.getElementById('faq-list');
    if (!container) return;
    if (items.length === 0) {
        container.textContent = 'Ainda não há perguntas frequentes cadastradas.';
        return;
    }

    container.replaceChildren(...items.map(item => {
        const article = document.createElement('article');
        article.className = 'portal-admin-section';
        const question = document.createElement('h2');
        question.textContent = item.question;
        const answer = document.createElement('p');
        answer.className = 'institutional-text';
        answer.textContent = item.answer;
        article.append(question, answer);
        return article;
    }));
}

function renderAbout(about) {
    const title = document.getElementById('about-title');
    const body = document.getElementById('about-body');
    const images = document.getElementById('about-images');
    if (title) title.textContent = about.title;
    if (body) body.textContent = about.body;
    if (images) {
        images.replaceChildren(...about.images.map(item => {
            const figure = document.createElement('figure');
            figure.className = 'portal-admin-carousel-card';
            const image = document.createElement('img');
            image.src = item.image_path;
            image.alt = item.alt_text;
            figure.appendChild(image);
            return figure;
        }));
    }
}

window.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`${window.location.origin}/api/portal`);
        if (!response.ok) throw new Error('Não foi possível carregar o conteúdo institucional.');
        const content = await response.json();
        renderFaq(content.faq || []);
        renderAbout(content.about || { title: 'Sobre a SEMED', body: '', images: [] });
    } catch (error) {
        const container = document.getElementById('faq-list') || document.getElementById('about-body');
        if (container) container.textContent = error.message;
    }
});
