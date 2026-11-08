function renderContactSocialLinks(settings, container) {
    if (!container) return;

    const links = [
        { name: 'WhatsApp', url: settings.whatsapp_url },
        { name: 'Instagram', url: settings.instagram_url },
        { name: 'Facebook', url: settings.facebook_url },
    ].filter(link => link.url);

    container.replaceChildren(...links.map(link => {
        const anchor = document.createElement('a');
        anchor.href = link.url;
        anchor.target = '_blank';
        anchor.rel = 'noopener noreferrer';
        anchor.textContent = link.name;
        return anchor;
    }));
}

async function loadContactContent() {
    try {
        const response = await fetch(`${window.location.origin}/api/portal`);
        if (!response.ok) {
            throw new Error(`Falha ao carregar as informações de contato (${response.status}).`);
        }

        const { settings } = await response.json();
        const fields = [
            ['contact-title', settings.contact_title, ''],
            ['contact-address', settings.contact_address, 'Endereço: '],
            ['contact-phone', settings.contact_phone, 'Telefone: '],
            ['contact-email', settings.contact_email, 'E-mail: '],
            ['contact-hours', settings.contact_hours, 'Atendimento: '],
            ['home-contact-title', settings.contact_title, ''],
            ['home-contact-address', settings.contact_address, 'Endereço: '],
            ['home-contact-phone', settings.contact_phone, 'Telefone: '],
            ['home-contact-email', settings.contact_email, 'E-mail: '],
            ['home-contact-hours', settings.contact_hours, 'Atendimento: '],
        ];

        for (const [id, value, prefix] of fields) {
            const element = document.getElementById(id);
            if (!element) continue;
            element.textContent = value ? `${prefix}${value}` : '';
            if (id !== 'contact-title' && id !== 'home-contact-title') {
                const row = element.closest('.info-item') || element;
                row.hidden = !value;
            }
        }

        renderContactSocialLinks(settings, document.getElementById('contact-social-links'));
        renderContactSocialLinks(settings, document.getElementById('home-contact-social-links'));
    } catch (error) {
        console.error('Não foi possível carregar as informações de contato:', error);
    }
}

window.addEventListener('DOMContentLoaded', loadContactContent);
