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
    adicionarComponenteNotificacao();
    
    // Implementação de WebSockets para atualizações em tempo real
    let socket;

    function inicializarWebSocket() {
        try {
            // Usar WebSocket ou um polyfill como Socket.IO se preferir
            socket = new WebSocket('wss://' + window.location.host + '/ws/admin');
            
            socket.onopen = function() {
                console.log('Conexão WebSocket estabelecida');
            };
            
            socket.onmessage = function(event) {
                const data = JSON.parse(event.data);
                
                if (data.type === 'novo_contato') {
                    // Adicionar notificação
                    mostrarNotificacao('Novo contato recebido', `${data.nome} da clínica ${data.clinica} enviou um contato.`);
                    
                    // Atualizar dados
                    carregarDadosContato();
                    
                    // Destacar card com efeito visual
                    destacarCardPendentes();
                }
            };
            
            socket.onerror = function(error) {
                console.error('Erro na conexão WebSocket:', error);
                // Fallback para polling se WebSocket falhar
                iniciarPolling();
            };
            
            socket.onclose = function() {
                console.log('Conexão WebSocket fechada');
                // Tentar reconectar após 5 segundos
                setTimeout(inicializarWebSocket, 5000);
            };
        } catch (error) {
            console.error('Falha ao inicializar WebSocket:', error);
            iniciarPolling();
        }
    }

    // Polling como fallback para navegadores sem suporte a WebSocket
    function iniciarPolling() {
        console.log('Iniciando polling para atualizações');
        setInterval(carregarDadosContato, 30000); // Verificar a cada 30 segundos
    }

    // Exibir notificação visual
    function mostrarNotificacao(titulo, mensagem) {
        // Verificar suporte a notificações
        if (!("Notification" in window)) {
            alert(titulo + ": " + mensagem);
            return;
        }
        
        // Se já temos permissão
        if (Notification.permission === "granted") {
            const notification = new Notification(titulo, {
                body: mensagem,
                icon: 'assents/img/klubedigitallogo.svg'
            });
            
            notification.onclick = function() {
                window.focus();
                this.close();
            };
        }
        // Se não solicitamos permissão ainda
        else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(function(permission) {
                if (permission === "granted") {
                    mostrarNotificacao(titulo, mensagem);
                }
            });
        }
        
        // Criar notificação dentro do sistema também
        const notificacoesLista = document.querySelector('.notification-list');
        if (notificacoesLista) {
            const novaNotificacao = document.createElement('div');
            novaNotificacao.className = 'notification-item unread new-notification';
            novaNotificacao.innerHTML = `
                <div class="notification-content">${mensagem}</div>
                <div class="notification-time">${new Date().toLocaleTimeString()}</div>
            `;
            notificacoesLista.prepend(novaNotificacao);
            
            // Atualizar contador
            const badge = document.querySelector('.notification-bell .badge');
            if (badge) {
                badge.textContent = parseInt(badge.textContent || 0) + 1;
            }
        }
        
        // Tocar um som sutil
        try {
            const audio = new Audio('assents/sound/notification.mp3');
            audio.volume = 0.5;
            audio.play().catch(e => console.log('Não foi possível tocar o som de notificação'));
        } catch (e) {
            console.log('Não foi possível tocar o som de notificação');
        }
    }

    function destacarCardPendentes() {
        const cardPendentes = document.querySelector('.card-pendentes');
        if (cardPendentes) {
            cardPendentes.classList.add('highlight');
            setTimeout(() => {
                cardPendentes.classList.remove('highlight');
            }, 3000);
        }
    }

    // Inicializar o WebSocket após carregar os dados iniciais
    setTimeout(() => {
        try {
            inicializarWebSocket();
        } catch (e) {
            console.error('Falha ao inicializar WebSocket, usando polling como fallback:', e);
            iniciarPolling();
        }
    }, 1000);

    // Adicionar sistema de notificação no header
    function adicionarComponenteNotificacao() {
        const adminUser = document.querySelector('.admin-user');
        if (!adminUser) return;
        
        const notificationBell = document.createElement('div');
        notificationBell.className = 'notification-bell';
        notificationBell.innerHTML = `
            <div class="bell-icon">🔔</div>
            <div class="badge">0</div>
            <div class="notification-dropdown">
                <div class="notification-header">
                    <h3>Notificações</h3>
                    <span class="clear-all">Limpar todas</span>
                </div>
                <div class="notification-list">
                    <!-- Notificações serão inseridas aqui -->
                </div>
                <div class="notification-footer">
                    <a href="#">Ver todas</a>
                </div>
            </div>
        `;
        
        adminUser.prepend(notificationBell);
        
        // Adicionar event listener para mostrar/esconder dropdown
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.querySelector('.notification-dropdown');
            dropdown.classList.toggle('show');
        });
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function() {
            const dropdown = document.querySelector('.notification-dropdown');
            if (dropdown) {
                dropdown.classList.remove('show');
            }
        });
        
        // Limpar notificações
        const clearAll = notificationBell.querySelector('.clear-all');
        clearAll.addEventListener('click', function(e) {
            e.stopPropagation();
            const notificationList = document.querySelector('.notification-list');
            if (notificationList) {
                notificationList.innerHTML = '';
            }
            
            const badge = document.querySelector('.notification-bell .badge');
            if (badge) {
                badge.textContent = '0';
            }
        });
    }

    // Funções de navegação entre seções - VERSÃO CORRIGIDA
    function inicializarNavegacao() {
        // Funcionalidade para toggle da sidebar em mobile
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const sidebar = document.querySelector('.admin-sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (mobileMenuBtn && sidebar && overlay) {
            mobileMenuBtn.addEventListener('click', function() {
                this.classList.toggle('active');
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                
                // Impedir scroll no body quando sidebar estiver aberta
                if (sidebar.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
            
            // Fechar sidebar ao clicar no overlay
            overlay.addEventListener('click', function() {
                mobileMenuBtn.classList.remove('active');
                sidebar.classList.remove('active');
                this.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Fechar sidebar ao clicar em um link
            const sidebarLinks = sidebar.querySelectorAll('a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        mobileMenuBtn.classList.remove('active');
                        sidebar.classList.remove('active');
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });
        }
        
        // Funcionalidade de navegação entre seções
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
                
                const targetElement = document.getElementById(targetSection);
                if (targetElement) {
                    targetElement.classList.add('active');
                    
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
                } else {
                    console.error('Seção alvo não encontrada:', targetSection);
                }
            });
        });
    }

    // Carregamento e atualização de dados - VERSÃO MELHORADA
    function carregarDadosContato() {
        // Mostrar indicador de carregamento sutil
        const dashboardSection = document.getElementById('dashboard');
        if (dashboardSection) {
            // Verificar se já existe um indicador de carregamento
            let loadingIndicator = document.querySelector('.corner-loading');
            if (!loadingIndicator) {
                loadingIndicator = document.createElement('div');
                loadingIndicator.className = 'corner-loading';
                loadingIndicator.innerHTML = 'Atualizando...';
                dashboardSection.appendChild(loadingIndicator);
            }
        }
        
        return fetch('dados/contatos.json?' + new Date().getTime()) // Evitar cache
            .then(response => {
                if (!response.ok) {
                    throw new Error('Arquivo de contatos não encontrado');
                }
                return response.json();
            })
            .then(data => {
                dadosContato = data;
                try {
                    atualizarDashboard();
                    atualizarTabelaContatos();
                    atualizarCardsAtendimentos();
                    inicializarGraficos();
                } catch (error) {
                    console.error("Erro ao atualizar UI:", error);
                }
                
                // Remover indicador de carregamento
                const loadingIndicator = document.querySelector('.corner-loading');
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
                
                return data; // Retornar os dados para uso em promessas encadeadas
            })
            .catch(error => {
                console.error('Erro ao carregar dados:', error);
                
                // Remover indicador de carregamento
                const loadingIndicator = document.querySelector('.corner-loading');
                if (loadingIndicator) {
                    loadingIndicator.remove();
                }
                
                // Criar dados de exemplo para demonstração ou manter dados anteriores
                if (!dadosContato) {
                    dadosContato = criarDadosExemplo();
                    atualizarDashboard();
                    atualizarTabelaContatos();
                    atualizarCardsAtendimentos();
                    inicializarGraficos();
                }
                
                // Mostrar notificação de erro
                const errorToast = document.createElement('div');
                errorToast.className = 'error-toast';
                errorToast.textContent = 'Erro ao atualizar dados. Tentando novamente em 30s...';
                document.body.appendChild(errorToast);
                
                setTimeout(() => {
                    errorToast.remove();
                }, 5000);
                
                return dadosContato;
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
        const countPendentes = document.getElementById('count-pendentes');
        const countEmAtendimento = document.getElementById('count-em-atendimento');
        const countConcluidos = document.getElementById('count-concluidos');
        const countTotal = document.getElementById('count-total');

        if (countPendentes) countPendentes.textContent = pendentes;
        if (countEmAtendimento) countEmAtendimento.textContent = emAtendimento;
        if (countConcluidos) countConcluidos.textContent = concluidos;
        if (countTotal) countTotal.textContent = total;

        // Contatos recentes
        const recentesContainer = document.getElementById('recent-contacts');
        if (!recentesContainer) return;
        
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
        if (!container) return;
        
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
                (c.nome && c.nome.toLowerCase().includes(termoBusca)) || 
                (c.email && c.email.toLowerCase().includes(termoBusca)) || 
                (c.clinica && c.clinica.toLowerCase().includes(termoBusca)) ||
                (c.id && c.id.toLowerCase().includes(termoBusca))
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
        if (!container) return;
        
        // Obter status do filtro
        const statusFilterElement = document.getElementById('filter-atendimentos-status');
        let statusFiltro = 'todos';
        
        if (statusFilterElement) {
            statusFiltro = statusFilterElement.value;
        }
        
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
        
        const ctxStatus = document.getElementById('chart-status');
        const ctxTimeline = document.getElementById('chart-timeline');
        
        if (!ctxStatus || !ctxTimeline) return;
        
        // Dados para o gráfico de status
        const statusCounts = {
            pendente: dadosContato.contatos.filter(c => c.status === 'pendente').length,
            em_atendimento: dadosContato.contatos.filter(c => c.status === 'em_atendimento').length,
            concluido: dadosContato.contatos.filter(c => c.status === 'concluido').length,
            cancelado: dadosContato.contatos.filter(c => c.status === 'cancelado').length || 0
        };
        
        // Gráfico de Status
        try {
            new Chart(ctxStatus.getContext('2d'), {
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
        } catch (error) {
            console.error('Erro ao criar gráfico de status:', error);
        }
        
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
        try {
            new Chart(ctxTimeline.getContext('2d'), {
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
        } catch (error) {
            console.error('Erro ao criar gráfico de timeline:', error);
        }
        
        // Calcular métricas
        const totalContatos = dadosContato.contatos.length;
        const contatosConcluidos = statusCounts.concluido;
        const taxaConclusao = totalContatos > 0 ? (contatosConcluidos / totalContatos * 100).toFixed(1) : 0;
        
        // Atualizar métricas
        const metricTaxaConclusao = document.getElementById('metric-taxa-conclusao');
        const metricTempoResposta = document.getElementById('metric-tempo-resposta');
        const metricTaxaConversao = document.getElementById('metric-taxa-conversao');
        
        if (metricTaxaConclusao) metricTaxaConclusao.textContent = `${taxaConclusao}%`;
        if (metricTempoResposta) metricTempoResposta.textContent = dadosContato.config.tempo_resposta_maximo || '24h';
        if (metricTaxaConversao) metricTaxaConversao.textContent = `${Math.round(contatosConcluidos / (totalContatos || 1) * 100)}%`;
    }

    // Inicializar modais
    function inicializarModais() {
        // Modal de Contato
        const modalContato = document.getElementById('modal-contato');
        const modalContatoClose = document.getElementById('modal-contato-close');
        
        if (modalContato && modalContatoClose) {
            modalContatoClose.addEventListener('click', () => {
                modalContato.style.display = 'none';
            });
            
            window.addEventListener('click', (event) => {
                if (event.target === modalContato) {
                    modalContato.style.display = 'none';
                }
            });
        }
        
        // Modal de Status
        const modalStatus = document.getElementById('modal-status');
        const modalStatusClose = document.getElementById('modal-status-close');
        const btnCancelStatus = document.getElementById('btn-cancel-status');
        
        if (modalStatus && modalStatusClose && btnCancelStatus) {
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
            if (formStatus) {
                formStatus.addEventListener('submit', (e) => {
                    e.preventDefault();
                    
                    const id = document.getElementById('status-contato-id').value;
                    const novoStatus = document.getElementById('status-select').value;
                    const observacao = document.getElementById('status-observacao').value;
                    
                    atualizarStatusContato(id, novoStatus, observacao);
                    modalStatus.style.display = 'none';
                });
            }
        }
        
        // Filtros
        const filterStatus = document.getElementById('filter-status');
        if (filterStatus) {
            filterStatus.addEventListener('change', () => {
                filtroAtual = filterStatus.value;
                atualizarTabelaContatos();
            });
        }
        
        const searchContatos = document.getElementById('search-contatos');
        const btnSearch = document.getElementById('btn-search');
        
        if (searchContatos && btnSearch) {
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
        }
        
        const btnRefresh = document.getElementById('btn-refresh');
        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => {
                carregarDadosContato();
            });
        }
        
        // Filtro de atendimentos
        const filterAtendimentosStatus = document.getElementById('filter-atendimentos-status');
        if (filterAtendimentosStatus) {
            filterAtendimentosStatus.addEventListener('change', () => {
                atualizarCardsAtendimentos();
            });
        }
        
        const btnAtendimentosRefresh = document.getElementById('btn-atendimentos-refresh');
        if (btnAtendimentosRefresh) {
            btnAtendimentosRefresh.addEventListener('click', () => {
                carregarDadosContato();
            });
        }
        
        // Geração de relatórios
        const btnGenerateReport = document.getElementById('btn-generate-report');
        if (btnGenerateReport) {
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
        }
        
        // Configurações
        const configForm = document.getElementById('config-form');
        if (configForm) {
            configForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                // Salvar configurações
                if (dadosContato && dadosContato.config) {
                    const configMensagemPadrao = document.getElementById('config-mensagem-padrao');
                    const configTempoResposta = document.getElementById('config-tempo-resposta');
                    const configNotificacoesEmail = document.getElementById('config-notificacoes-email');
                    const configNotificacoesSistema = document.getElementById('config-notificacoes-sistema');
                    
                    if (configMensagemPadrao) dadosContato.config.mensagem_padrao_whatsapp = configMensagemPadrao.value;
                    if (configTempoResposta) dadosContato.config.tempo_resposta_maximo = configTempoResposta.value;
                    if (configNotificacoesEmail) dadosContato.config.notificacoes_email = configNotificacoesEmail.checked;
                    if (configNotificacoesSistema) dadosContato.config.notificacoes_sistema = configNotificacoesSistema.checked;
                    
                    // Em um sistema real, você enviaria esses dados para o servidor
                    // Aqui apenas simulamos o salvamento
                    alert('Configurações salvas com sucesso!');
                }
            });
        }
    }

    // Abrir modal de contato com detalhes
    function abrirModalContato(id) {
        if (!dadosContato) return;
        
        const contato = dadosContato.contatos.find(c => c.id === id);
        if (!contato) return;
        
        const modalContent = document.getElementById('modal-contato-content');
        if (!modalContent) return;
        
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
        const modalContato = document.getElementById('modal-contato');
        if (modalContato) {
            modalContato.style.display = 'block';
        }
    }

    // Abrir modal para atualizar status
    function abrirModalAtualizarStatus(id) {
        if (!dadosContato) return;
        
        const contato = dadosContato.contatos.find(c => c.id === id);
        if (!contato) return;
        
        // Preencher o form
        const statusContatoId = document.getElementById('status-contato-id');
        const statusSelect = document.getElementById('status-select');
        const statusObservacao = document.getElementById('status-observacao');
        
        if (statusContatoId) statusContatoId.value = id;
        if (statusSelect) statusSelect.value = contato.status;
        if (statusObservacao) statusObservacao.value = '';
        
        // Exibir modal
        const modalStatus = document.getElementById('modal-status');
        if (modalStatus) {
            modalStatus.style.display = 'block';
        }
    }

    // Atualizar status de um contato
    function atualizarStatusContato(id, novoStatus, observacao) {
        if (!dadosContato) return;
        
        const contato = dadosContato.contatos.find(c => c.id === id);
        if (!contato) return;
        
        // Atualizar status
        contato.status = novoStatus;
        
        // Em um sistema real, você enviaria estes dados para o servidor
        // usando a função atualizarDados que está definida no escopo global
        
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
            if (!contato.observacoes) {
                contato.observacoes = [];
            }
            
            contato.observacoes.push({
                data: new Date().toISOString(),
                status: novoStatus,
                texto: observacao
            });
            
            console.log(`Observação para ${id}: ${observacao}`);
        }
        
        // Atualizar dados no servidor
        try {
            window.atualizarDados(id, novoStatus, observacao);
        } catch (error) {
            console.log('Erro ao chamar atualizarDados, atualizando apenas interface local:', error);
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
        
        try {
            const data = new Date(dataISO);
            if (isNaN(data.getTime())) return dataISO; // Retorna o original se não for uma data válida
            
            const dia = data.getDate().toString().padStart(2, '0');
            const mes = (data.getMonth() + 1).toString().padStart(2, '0');
            const ano = data.getFullYear();
            const hora = data.getHours().toString().padStart(2, '0');
            const minuto = data.getMinutes().toString().padStart(2, '0');
            
            return `${dia}/${mes}/${ano} às ${hora}:${minuto}`;
        } catch (error) {
            console.error('Erro ao formatar data:', error);
            return dataISO; // Retorna o original em caso de erro
        }
    }
    
    function formatarDataCurta(data) {
        try {
            const dia = data.getDate().toString().padStart(2, '0');
            const mes = (data.getMonth() + 1).toString().padStart(2, '0');
            
            return `${dia}/${mes}`;
        } catch (error) {
            console.error('Erro ao formatar data curta:', error);
            return '';
        }
    }
    
    function formatarTelefone(telefone) {
        if (!telefone) return '';
        
        try {
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
        } catch (error) {
            console.error('Erro ao formatar telefone:', error);
            return telefone;
        }
    }
    
    function traduzirStatus(status) {
        if (!status) return '';
        
        const statusMap = {
            'pendente': 'Pendente',
            'em_atendimento': 'Em Atendimento',
            'concluido': 'Concluído',
            'cancelado': 'Cancelado'
        };
        
        return statusMap[status] || status;
    }
    
    function traduzirOrigem(origem) {
        if (!origem) return '';
        
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

// Função global para atualizar os contatos via PHP
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
            console.log('Status atualizado com sucesso no servidor');
            // Recarregar os dados atualizados
            if (typeof carregarDadosContato === 'function') {
                carregarDadosContato();
            }
        } else {
            console.error('Erro ao atualizar o status:', data.message);
            alert('Erro ao atualizar o status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro de comunicação:', error);
        alert('Erro de comunicação ao atualizar o status.');
    });
}