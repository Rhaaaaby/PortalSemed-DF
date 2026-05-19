const NoticiaModel = {
    getUltimasNoticias: async function() {
        if (typeof STORAGE_MODE !== 'undefined' && STORAGE_MODE === 'localstorage') {
            // Inicializar notícias em LocalStorage se estiver vazio
            if (!localStorage.getItem('noticias')) {
                const mockNoticias = [
                    { id: 1, title: "Volta às aulas 2026", content: "A Secretaria Municipal de Educação divulga o calendário para o início do ano letivo de 2026 em toda a rede municipal de São Miguel do Tocantins.", categoria: "Notícias", created_at: new Date().toISOString() },
                    { id: 2, title: "Programa de Merenda Escolar", content: "Novos investimentos foram aprovados para a melhoria e ampliação da alimentação dos alunos da rede municipal.", categoria: "Comunicados", created_at: new Date().toISOString() },
                    { id: 3, title: "Capacitação Docente", content: "Curso de capacitação continuada oferecido para todos os profissionais da educação de São Miguel.", categoria: "Eventos", created_at: new Date().toISOString() }
                ];
                localStorage.setItem('noticias', JSON.stringify(mockNoticias));
            }
            const noticias = JSON.parse(localStorage.getItem('noticias')) || [];
            return noticias.map(n => ({
                id: n.id,
                titulo: n.title || n.titulo,
                resumo: n.content ? (n.content.substring(0, 100) + (n.content.length > 100 ? '...' : '')) : '',
                imagem: n.imagem || null
            }));
        }

        try {
            const response = await fetch(`${window.location.origin}/api/noticias`);
            if (!response.ok) {
                throw new Error('Erro ao carregar notícias');
            }
            const data = await response.json();
            if (!Array.isArray(data) || data.length === 0) {
                throw new Error('Nenhuma notícia no banco');
            }
            return data.map(n => ({
                id: n.id,
                titulo: n.title,
                resumo: n.content ? (n.content.substring(0, 100) + (n.content.length > 100 ? '...' : '')) : '',
                imagem: n.imagem ? `${window.location.origin}/uploads/${n.imagem}` : null
            }));
        } catch (error) {
            console.warn("Usando notícias mockadas (motivo: " + error.message + ")");
            return [
                { id: 1, titulo: "Volta às aulas 2026", resumo: "A Secretaria Municipal de Educação divulga o calendário para o início do ano letivo." },
                { id: 2, titulo: "Programa de Merenda Escolar", resumo: "Novos investimentos para a melhoria da alimentação dos alunos da rede municipal." },
                { id: 3, titulo: "Capacitação Docente", resumo: "Curso de capacitação oferecido para os profissionais da educação em São Miguel." }
            ];
        }
    }
};