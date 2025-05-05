// admin.js - JavaScript para o painel administrativo

document.addEventListener('DOMContentLoaded', function() {
    // Variáveis globais
    let dadosContato = null;
    let filtroAtual = 'todos';
    let busca = '';

    // Inicialização
    inicializarNavegacao();
    carregarDadosContato();
    inicializarModais();

    // Funções de navegação entre seções
    function inicializarNavegacao() {
        const navLinks = document.querySelectorAll('.sidebar-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetSection = this.getAttribute('data-section');
                
                // Atualizar navegação
                document.querySelectorAll('.sidebar-nav li').forEach(item => {
                    item.classList.remove('active');
                });
                this.parentElement.classList.add('active');
                
                // Mostrar seção correspondente
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(targetSection).classList.add('active');
                
                // Recarregar dados se necessário
                if (targetSection === 'dashboard') {
                    atualizarDashboard();
                } else if (targetSection === 'contatos') {
                    atualizarTabelaContatos();
                } else if (targetSection === 'atendimentos') {
                    atualizarCardsAtendimentos();
                } else if (targetSection === 'relatorios') {
                    inicializarGraficos();
                }
            });
        });
    }

    // Carregamento e atualização de dados
    function carregarDadosContato() {
        fetch('dados/contatos.json')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Arquivo de contatos não encontrado');
                }
                return response.json();
            })
            .then(data => {
                dadosContato = data;
                atualizarDashboard();
                atualizarTabelaContatos();
                atualizarCardsAtendimentos();
                inicializarGraficos();
            })
            .catch(error => {
                console.error('Erro ao carregar dados:', error);
                // Criar dados de exemplo para demonstração
                dadosContato = criarDadosExemplo();
                atualizarDashboard();
                atualizarTabelaContatos();
                atualizarCardsAtendimentos();
                inicializarGraficos();
            });
    }

    // Criar dados de exemplo se o arquivo não existir
    function criarDadosExemplo() {
        return {
            contatos: [
                {
                    id: "CON001",
                    data: "2025-05-04T14:30:00",
                    nome: "João Silva",
                    email: "joao.silva@email.com",
                    telefone: "5534999887766",
                    clinica: "Odonto Excellence",
                    mensagem: "Gostaria de informações sobre o serviço de marketing para clínicas odontológicas.",
                    status: "pendente",
                    origem: "formulario_site",
                    whatsapp_link: "https://api.whatsapp.com/send?phone=5534999887766&text=Ol%C3%A1%20Jo%C3%A3o%2C%20recebemos%20sua%20solicita%C3%A7%C3%A3o%20CON001%20na%20Klube%20Digital.%20Como%20podemos%20ajudar%20com%20seu%20atendimento%3F"
                },
                {
                    id: "CON002",
                    data: "2025-05-04T16:15:00",
                    nome: "Maria Oliveira",
                    email: "maria.oliveira@email.com",
                    telefone: "5534988776655",
                    clinica: "Sorria Odontologia",
                    mensagem: "Preciso de um orçamento para gestão de redes sociais para minha clínica.",
                    status: "em_atendimento",
                    origem: "formulario_site",
                    whatsapp_link: "https://api.whatsapp.com/send?phone=5534988776655&text=Ol%C3%A1%20Maria%2C%20estamos%20dando%20continuidade%20ao%20seu%20atendimento%20CON002%20na%20Klube%20Digital.%20Podemos%20conversar%20sobre%20o%20or%C3%A7amento%20para%20gest%C3%A3o%20de%20redes%20sociais%3F"
                },
                {
                    id: "CON003",
                    data: "2025-05-03T09:45:00",
                    nome: "Carlos Santos",
                    email: "carlos.santos@email.com",
                    telefone: "5534977665544",
                    clinica: "Odonto Saúde",
                    mensagem: "Quero saber mais sobre as estratégias de posicionamento digital para aumentar o fluxo de pacientes.",
                    status: "concluido",
                    origem: "indicacao",
                    whatsapp_link: "https://api.whatsapp.com/send?phone=5534977665544&text=Ol%C3%A1%20Carlos%2C%20referente%20ao%20seu%20atendimento%20CON003%20na%20Klube%20Digital.%20Gostar%C3%ADamos%20de%20apresentar%20nossas%20estrat%C3%A9gias%20de%20posicionamento%20digital%20para%20sua%20cl%C3%ADnica."
                }
            ],
            meta: {
                total_registros: 3,
                ultima_atualizacao: "2025-05-05T18:00:00",
                status_disponiveis: ["pendente", "em_atendimento", "concluido", "cancelado"],
                origens_disponiveis: ["formulario_site", "telefone", "email", "indicacao", "redes_sociais"]
            },
            config: {
                mensagem_padrao_whatsapp: "Olá {nome}, referente ao seu atendimento {id} na Klube Digital. {mensagem_personalizada}",
                tempo_resposta_maximo: "24h",
                notificacoes_email: true,
                notificacoes_sistema: true
            }
        };
    }

    // Atualização do Dashboard
    function atualizarDashboard() {
        if (!dadosContato) return;

        // Contadores de status
        const pendentes = dadosContato.contatos.filter(c => c.status === 'pendente').length;
        const emAtendimento = dadosContato.contatos.filter(c => c.status === 'em_atendimento').length;
        const concluidos = dadosContato.contatos.filter(c => c.status === 'concluido').length;
        const total = dadosContato.contatos.length;

        // Atualizar cards
        document.getElementById('count-pendentes').textContent = pendentes;
        document.getElementById('count-em-atendimento').textContent = emAtendimento;
        document.getElementById('count-concluidos').textContent = concluidos;
        document.getElementById('count-total').textContent = total;

        // Contatos recentes
        const recentesContainer = document.getElementById('recent-contacts');
        
        // Ordenar por data (mais recentes primeiro)
        const contatosRecentes = [...dadosContato.contatos]
            .sort((a, b) => new Date(b.data) - new Date(a.data))
            .slice(0, 5); // Pegar os 5 mais recentes
        
        if (contatosRecentes.length === 0) {
            recentesContainer.innerHTML = '<p>Nenhum contato recente.</p>';
            return;
        }
        
        let html = `
            <table class="contatos-tabela">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Nome</th>
                        <th>Clínica</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        contatosRecentes.forEach(contato => {
            html += `
                <tr>
                    <td>${contato.id}</td>
                    <td>${formatarData(contato.data)}</td>
                    <td>${contato.nome}</td>
                    <td>${contato.clinica}</td>
                    <td><span class="status-${contato.status}">${traduzirStatus(contato.status)}</span></td>
                    <td>
                        <a href="${contato.whatsapp_link}" target="_blank" class="btn-whatsapp">WhatsApp</a>
                        <button class="btn-ver-mais" data-id="${contato.id}">Ver mais</button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        recentesContainer.innerHTML = html;
        
        // Adicionar event listeners aos botões "Ver mais"
        document.querySelectorAll('.btn-ver-mais').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                abrirModalContato(id);
            });
        });
    }

    // Atualização da tabela de contatos
    function atualizarTabelaContatos() {
        if (!dadosContato) return;
        
        const container = document.getElementById('contatos-table-container');
        
        // Aplicar filtros
        let contatosFiltrados = [...dadosContato.contatos];
        
        // Filtrar por status
        if (filtroAtual !== 'todos') {
            contatosFiltrados = contatosFiltrados.filter(c => c.status === filtroAtual);
        }
        
        // Filtrar por busca
        if (busca.trim() !== '') {
            const termoBusca = busca.toLowerCase();
            contatosFiltrados = contatosFiltrados.filter(c => 
                c.nome.toLowerCase().includes(termoBusca) || 
                c.email.toLowerCase().includes(termoBusca) || 
                c.clinica.toLowerCase().includes(termoBusca) ||
                c.id.toLowerCase().includes(termoBusca)
            );
        }
        
        // Ordenar por data (mais recentes primeiro)
        contatosFiltrados.sort((a, b) => new Date(b.data) - new Date(a.data));
        
        if (contatosFiltrados.length === 0) {
            container.innerHTML = '<p>Nenhum contato encontrado com os filtros atuais.</p>';
            return;
        }
        
        let html = `
            <table class="contatos-tabela">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Clínica</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        contatosFiltrados.forEach(contato => {
            html += `
                <tr>
                    <td>${contato.id}</td>
                    <td>${formatarData(contato.data)}</td>
                    <td>${contato.nome}</td>
                    <td>${contato.email}</td>
                    <td>${contato.clinica}</td>
                    <td><span class="status-${contato.status}">${traduzirStatus(contato.status)}</span></td>
                    <td>
                        <a href="${contato.whatsapp_link}" target="_blank" class="btn-whatsapp">WhatsApp</a>
                        <button class="btn-ver-mais" data-id="${contato.id}">Ver mais</button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        container.innerHTML = html;
        
        // Event listeners para os botões
        document.querySelectorAll('.btn-ver-mais').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                abrirModalContato(id);
            });
        });
    }

    // Atualização dos cards de atendimento
    function atualizarCardsAtendimentos() {
        if (!dadosContato) return;
        
        const container = document.getElementById('atendimentos-cards');
        
        // Obter status do filtro
        const statusFiltro = document.getElementById('filter-atendimentos-status').value;
        
        // Filtrar contatos pelo status
        let contatosFiltrados = [...dadosContato.contatos];
        if (statusFiltro !== 'todos') {
            contatosFiltrados = contatosFiltrados.filter(c => c.status === statusFiltro);
        }
        
        // Ordenar por data (mais recentes primeiro)
        contatosFiltrados.sort((a, b) => new Date(b.data) - new Date(a.data));
        
        if (contatosFiltrados.length === 0) {
            container.innerHTML = '<p>Nenhum atendimento encontrado com este status.</p>';
            return;
        }
        
        let html = '';
        
        contatosFiltrados.forEach(contato => {
            html += `
                <div class="atendimento-card">
                    <div class="atendimento-header">
                        <div class="atendimento-id">${contato.id}</div>
                        <div class="atendimento-data">${formatarData(contato.data)}</div>
                    </div>
                    <div class="atendimento-content">
                        <p><strong>Nome:</strong> ${contato.nome}</p>
                        <p><strong>Clínica:</strong> ${contato.clinica}</p>
                        <p><strong>Status:</strong> <span class="status-${contato.status}">${traduzirStatus(contato.status)}</span></p>
                        <p><strong>Mensagem:</strong></p>
                        <div class="atendimento-message">${contato.mensagem}</div>
                    </div>
                    <div class="atendimento-actions">
                        <a href="${contato.whatsapp_link}" target="_blank" class="btn-whatsapp">WhatsApp</a>
                        <button class="btn-ver-mais" data-id="${contato.id}">Ver Detalhes</button>
                        <button class="btn-atualizar-status" data-id="${contato.id}">Atualizar Status</button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        
        // Event listeners
        document.querySelectorAll('.atendimento-card .btn-ver-mais').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                abrirModalContato(id);
            });
        });
        
        document.querySelectorAll('.atendimento-card .btn-atualizar-status').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                abrirModalAtualizarStatus(id);
            });
        });
    }

    // Inicializar gráficos para relatórios
    function inicializarGraficos() {
        if (!dadosContato) return;
        
        // Dados para o gráfico de status
        const statusCounts = {
            pendente: dadosContato.contatos.filter(c => c.status === 'pendente').length,
            em_atendimento: dadosContato.contatos.filter(c => c.status === 'em_atendimento').length,
            concluido: dadosContato.contatos.filter(c => c.status === 'concluido').length,
            cancelado: dadosContato.contatos.filter(c => c.status === 'cancelado').length || 0
        };
        
        // Gráfico de Status
        const ctxStatus = document.getElementById('chart-status').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Pendente', 'Em Atendimento', 'Concluído', 'Cancelado'],
                datasets: [{
                    data: [
                        statusCounts.pendente,
                        statusCounts.em_atendimento,
                        statusCounts.concluido,
                        statusCounts.cancelado
                    ],
                    backgroundColor: [
                        '#ffc107', // warning - pendente
                        '#17a2b8', // info - em atendimento
                        '#28a745', // success - concluído
                        '#dc3545'  // danger - cancelado
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Preparar dados para gráfico de linha (últimos 7 dias)
        const hoje = new Date();
        const ultimos7Dias = [...Array(7)].map((_, i) => {
            const data = new Date();
            data.setDate(hoje.getDate() - (6 - i));
            return data;
        });
        
        const contagemPorDia = ultimos7Dias.map(data => {
            const dataString = data.toISOString().split('T')[0];
            return {
                data: dataString,
                label: formatarDataCurta(data),
                contagem: dadosContato.contatos.filter(c => 
                    c.data.split('T')[0] === dataString
                ).length
            };
        });
        
        // Gráfico de Timeline
        const ctxTimeline = document.getElementById('chart-timeline').getContext('2d');
        new Chart(ctxTimeline, {
            type: 'line',
            data: {
                labels: contagemPorDia.map(d => d.label),
                datasets: [{
                    label: 'Novos Contatos',
                    data: contagemPorDia.map(d => d.contagem),
                    backgroundColor: 'rgba(30, 44, 138, 0.2)',
                    borderColor: 'rgba(30, 44, 138, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Calcular métricas
        const totalContatos = dadosContato.contatos.length;
        const contatosConcluidos = statusCounts.concluido;
        const taxaConclusao = totalContatos > 0 ? (contatosConcluidos / totalContatos * 100).toFixed(1) : 0;
        
        // Atualizar métricas
        document.getElementById('metric-taxa-conclusao').textContent = `${taxaConclusao}%`;
        document.getElementById('metric-tempo-resposta').textContent = dadosContato.config.tempo_resposta_maximo || '24h';
        document.getElementById('metric-taxa-conversao').textContent = `${Math.round(contatosConcluidos / (totalContatos || 1) * 100)}%`;
    }

    // Inicializar modais
    function inicializarModais() {
        // Modal de Contato
        const modalContato = document.getElementById('modal-contato');
        const modalContatoClose = document.getElementById('modal-contato-close');
        
        modalContatoClose.addEventListener('click', () => {
            modalContato.style.display = 'none';
        });
        
        window.addEventListener('click', (event) => {
            if (event.target === modalContato) {
                modalContato.style.display = 'none';
            }
        });
        
        // Modal de Status
        const modalStatus = document.getElementById('modal-status');
        const modalStatusClose = document.getElementById('modal-status-close');
        const btnCancelStatus = document.getElementById('btn-cancel-status');
        
        modalStatusClose.addEventListener('click', () => {
            modalStatus.style.display = 'none';
        });
        
        btnCancelStatus.addEventListener('click', () => {
            modalStatus.style.display = 'none';
        });
        
        window.addEventListener('click', (event) => {
            if (event.target === modalStatus) {
                modalStatus.style.display = 'none';
            }
        });
        
        // Form de atualização de status
        const formStatus = document.getElementById('form-atualizar-status');
        formStatus.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const id = document.getElementById('status-contato-id').value;
            const novoStatus = document.getElementById('status-select').value;
            const observacao = document.getElementById('status-observacao').value;
            
            atualizarStatusContato(id, novoStatus, observacao);
            modalStatus.style.display = 'none';
        });
        
        // Filtros
        const filterStatus = document.getElementById('filter-status');
        filterStatus.addEventListener('change', () => {
            filtroAtual = filterStatus.value;
            atualizarTabelaContatos();
        });
        
        const searchContatos = document.getElementById('search-contatos');
        const btnSearch = document.getElementById('btn-search');
        
        btnSearch.addEventListener('click', () => {
            busca = searchContatos.value;
            atualizarTabelaContatos();
        });
        
        searchContatos.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') {
                busca = searchContatos.value;
                atualizarTabelaContatos();
            }
        });
        
        const btnRefresh = document.getElementById('btn-refresh');
        btnRefresh.addEventListener('click', () => {
            carregarDadosContato();
        });
        
        // Filtro de atendimentos
        const filterAtendimentosStatus = document.getElementById('filter-atendimentos-status');
        filterAtendimentosStatus.addEventListener('change', () => {
            atualizarCardsAtendimentos();
        });
        
        const btnAtendimentosRefresh = document.getElementById('btn-atendimentos-refresh');
        btnAtendimentosRefresh.addEventListener('click', () => {
            carregarDadosContato();
        });
        
        // Geração de relatórios
        const btnGenerateReport = document.getElementById('btn-generate-report');
        btnGenerateReport.addEventListener('click', () => {
            const dateStart = document.getElementById('date-start').value;
            const dateEnd = document.getElementById('date-end').value;
            
            if (dateStart && dateEnd) {
                // Aqui você poderia filtrar os dados pelo período selecionado
                // e regenerar os gráficos, mas para simplicidade vamos apenas
                // recarregar os gráficos existentes
                inicializarGraficos();
            } else {
                alert('Por favor, selecione o período do relatório.');
            }
        });
        
        // Configurações
        const configForm = document.getElementById('config-form');
        configForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Salvar configurações
            if (dadosContato && dadosContato.config) {
                dadosContato.config.mensagem_padrao_whatsapp = document.getElementById('config-mensagem-padrao').value;
                dadosContato.config.tempo_resposta_maximo = document.getElementById('config-tempo-resposta').value;
                dadosContato.config.notificacoes_email = document.getElementById('config-notificacoes-email').checked;
                dadosContato.config.notificacoes_sistema = document.getElementById('config-notificacoes-sistema').checked;
                
                // Em um sistema real, você enviaria esses dados para o servidor
                // Aqui apenas simulamos o salvamento
                alert('Configurações salvas com sucesso!');
            }
        });
    }

    // Abrir modal de contato com detalhes
    function abrirModalContato(id) {
        if (!dadosContato) return;
        
        const contato = dadosContato.contatos.find(c => c.id === id);
        if (!contato) return;
        
        const modalContent = document.getElementById('modal-contato-content');
        
        let html = `
            <h2>Detalhes do Contato</h2>
            <div class="contato-info">
                <p><strong>ID:</strong> ${contato.id}</p>
                <p><strong>Data:</strong> ${formatarData(contato.data)}</p>
                <p><strong>Nome:</strong> ${contato.nome}</p>
                <p><strong>Email:</strong> ${contato.email}</p>
                <p><strong>Telefone:</strong> ${formatarTelefone(contato.telefone)}</p>
                <p><strong>Clínica:</strong> ${contato.clinica}</p>
                <p><strong>Status:</strong> <span class="status-${contato.status}">${traduzirStatus(contato.status)}</span></p>
                <p><strong>Origem:</strong> ${traduzirOrigem(contato.origem)}</p>
                <p><strong>Mensagem:</strong></p>
                <div class="mensagem-box">${contato.mensagem}</div>
                
                <div class="actions">
                    <a href="${contato.whatsapp_link}" target="_blank" class="btn-primary">Responder via WhatsApp</a>
                    <button class="btn-secondary btn-atualizar-status" data-id="${contato.id}">Atualizar Status</button>
                </div>
            </div>
        `;
        
        modalContent.innerHTML = html;
        
        // Event listener para o botão de atualizar status
        modalContent.querySelector('.btn-atualizar-status').addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('modal-contato').style.display = 'none';
            abrirModalAtualizarStatus(id);
        });
        
        // Exibir modal
        document.getElementById('modal-contato').style.display = 'block';
    }

    // Abrir modal para atualizar status
    function abrirModalAtualizarStatus(id) {
        if (!dadosContato) return;
        
        const contato = dadosContato.contatos.find(c => c.id === id);
        if (!contato) return;
        
        // Preencher o form
        document.getElementById('status-contato-id').value = id;
        document.getElementById('status-select').value = contato.status;
        document.getElementById('status-observacao').value = '';
        
        // Exibir modal
        document.getElementById('modal-status').style.display = 'block';
    }

    // Atualizar status de um contato
    function atualizarStatusContato(id, novoStatus, observacao) {
        if (!dadosContato) return;
        
        const contato = dadosContato.contatos.find(c => c.id === id);
        if (!contato) return;
        
        // Atualizar status
        contato.status = novoStatus;
        
        // Em um sistema real, você enviaria estes dados para o servidor
        // Aqui apenas atualizamos os dados locais
        
        // Gerar mensagem personalizada para WhatsApp baseada no novo status
        let mensagemPersonalizada = '';
        
        switch(novoStatus) {
            case 'pendente':
                mensagemPersonalizada = 'Recebemos sua solicitação e logo entraremos em contato.';
                break;
            case 'em_atendimento':
                mensagemPersonalizada = 'Estamos dando andamento ao seu atendimento. Podemos falar mais sobre sua solicitação?';
                break;
            case 'concluido':
                mensagemPersonalizada = 'Seu atendimento foi concluído. Agradecemos a confiança em nossos serviços!';
                break;
            case 'cancelado':
                mensagemPersonalizada = 'Seu atendimento foi cancelado. Se precisar de algo mais, fique à vontade para entrar em contato.';
                break;
        }
        
        // Atualizar link do WhatsApp
        const mensagemBase = dadosContato.config.mensagem_padrao_whatsapp
            .replace('{nome}', contato.nome)
            .replace('{id}', contato.id)
            .replace('{mensagem_personalizada}', mensagemPersonalizada);
            
        contato.whatsapp_link = `https://api.whatsapp.com/send?phone=${contato.telefone}&text=${encodeURIComponent(mensagemBase)}`;
        
        // Adicionar observação se tiver
        if (observacao) {
            // Em um sistema real, você armazenaria histórico de observações
            console.log(`Observação para ${id}: ${observacao}`);
        }
        
        // Atualizar a interface
        atualizarDashboard();
        atualizarTabelaContatos();
        atualizarCardsAtendimentos();
        inicializarGraficos();
    }

    // Funções utilitárias
    function formatarData(dataISO) {
        if (!dataISO) return '';
        
        const data = new Date(dataISO);
        const dia = data.getDate().toString().padStart(2, '0');
        const mes = (data.getMonth() + 1).toString().padStart(2, '0');
        const ano = data.getFullYear();
        const hora = data.getHours().toString().padStart(2, '0');
        const minuto = data.getMinutes().toString().padStart(2, '0');
        
        return `${dia}/${mes}/${ano} às ${hora}:${minuto}`;
    }
    
    function formatarDataCurta(data) {
        const dia = data.getDate().toString().padStart(2, '0');
        const mes = (data.getMonth() + 1).toString().padStart(2, '0');
        
        return `${dia}/${mes}`;
    }
    
    function formatarTelefone(telefone) {
        // Remover o código do país para exibição
        if (telefone.startsWith('55')) {
            telefone = telefone.substring(2);
        }
        
        // Formatar como (XX) XXXXX-XXXX para celulares brasileiros
        if (telefone.length === 11) {
            return `(${telefone.substring(0, 2)}) ${telefone.substring(2, 7)}-${telefone.substring(7)}`;
        } 
        // Formatar como (XX) XXXX-XXXX para telefones fixos
        else if (telefone.length === 10) {
            return `(${telefone.substring(0, 2)}) ${telefone.substring(2, 6)}-${telefone.substring(6)}`;
        }
        
        // Retornar o formato original se não reconhecer o padrão
        return telefone;
    }
    
    function traduzirStatus(status) {
        const statusMap = {
            'pendente': 'Pendente',
            'em_atendimento': 'Em Atendimento',
            'concluido': 'Concluído',
            'cancelado': 'Cancelado'
        };
        
        return statusMap[status] || status;
    }
    
    function traduzirOrigem(origem) {
        const origemMap = {
            'formulario_site': 'Formulário do Site',
            'telefone': 'Telefone',
            'email': 'E-mail',
            'indicacao': 'Indicação',
            'redes_sociais': 'Redes Sociais'
        };
        
        return origemMap[origem] || origem;
    }
});

// Função para atualizar os contatos via PHP
function atualizarDados(id, novoStatus, observacao) {
    // Em um sistema real, isto enviaria uma requisição AJAX para o servidor
    // para atualizar o arquivo JSON
    const formData = new FormData();
    formData.append('id', id);
    formData.append('status', novoStatus);
    formData.append('observacao', observacao);
    
    fetch('atualizar-contato.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recarregar os dados atualizados
            carregarDadosContato();
        } else {
            alert('Erro ao atualizar o status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de comunicação ao atualizar o status.');
    });
}