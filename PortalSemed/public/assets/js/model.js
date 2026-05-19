const NoticiaModel = {
    getUltimasNoticias: async function() {
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