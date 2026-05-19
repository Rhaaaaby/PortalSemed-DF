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

window.addEventListener('DOMContentLoaded', () => {
    includeComponent('header', '../components/header.html');
    includeComponent('footer', '../components/footer.html');
});
