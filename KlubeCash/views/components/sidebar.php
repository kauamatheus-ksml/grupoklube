<?php
// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /klube-cash/views/auth/login.php');
    exit;
}

// Definir o item de menu ativo (deve ser configurado na página que inclui a sidebar)
$activeMenu = $activeMenu ?? 'painel';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Estilos para a sidebar */
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --border-radius: 20px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 80px;
            --header-height: 70px;
            --transition-speed: 0.3s;
        }
        
        /* Estilos gerais da sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            background-color: var(--white);
            box-shadow: var(--shadow);
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            transition: width var(--transition-speed) ease;
            overflow: hidden;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        /* Estado expandido da sidebar */
        .sidebar-expanded {
            width: var(--sidebar-width);
        }

        /* Estado recolhido da sidebar */
        .sidebar-collapsed {
            width: var(--sidebar-collapsed-width);
        }

        /* Cabeçalho da sidebar */
        .sidebar-header {
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        /* Logo */
        .sidebar-logo {
            display: flex;
            align-items: center;
        }

        .sidebar-logo img {
            height: 30px;
        }

        .sidebar-logo-text {
            margin-left: 10px;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--primary-color);
            white-space: nowrap;
        }

        /* Botão de toggle */
        .sidebar-toggle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--white);
            border: 1px solid var(--light-gray);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            right: -15px;
            top: calc(var(--header-height) / 2);
            transform: translateY(-50%);
            z-index: 1001;
            transition: transform var(--transition-speed) ease;
        }

        /* Menu de navegação */
        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            padding: 20px 0;
        }

        .sidebar-menu-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            text-decoration: none;
            color: var(--dark-gray);
            transition: background-color 0.2s ease;
            margin-bottom: 5px;
        }

        .sidebar-menu-item:hover {
            background-color: var(--light-gray);
        }

        .sidebar-menu-item.active {
            background-color: var(--primary-light);
            color: var(--primary-color);
            font-weight: bold;
        }

        .sidebar-menu-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-menu-text {
            margin-left: 15px;
            white-space: nowrap;
        }

        /* Botão de sair */
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--light-gray);
        }

        .sidebar-logout {
            display: flex;
            align-items: center;
            padding: 12px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            justify-content: center;
            width: 100%;
        }

        .sidebar-logout-text {
            margin-left: 10px;
            white-space: nowrap;
        }

        /* Estado da sidebar em mobile */
        .sidebar-mobile {
            width: 0;
            left: -var(--sidebar-width);
        }

        .sidebar-mobile.active {
            width: var(--sidebar-width);
            left: 0;
        }

        /* Botão de menu mobile */
        .mobile-menu-button {
            position: fixed;
            top: 15px;
            left: 15px;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background-color: var(--white);
            border: 1px solid var(--light-gray);
            box-shadow: var(--shadow);
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999;
        }

        /* Overlay para fechar o menu mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }

        /* Ajustes para telas pequenas */
        @media (max-width: 768px) {
            .sidebar {
                left: -var(--sidebar-width);
                z-index: 1001;
            }

            .sidebar.sidebar-mobile.active {
                left: 0;
                width: var(--sidebar-width);
            }

            .mobile-menu-button {
                display: flex;
            }

            .sidebar-overlay.active {
                display: block;
            }

            .sidebar-toggle {
                display: none;
            }

            /* Garante que o conteúdo principal não fique sob a sidebar */
            .main-content {
                margin-left: 0 !important;
            }
        }

        /* Classe helper para o conteúdo principal */
        .main-content {
            transition: margin-left var(--transition-speed) ease;
        }

        .main-content-expanded {
            margin-left: var(--sidebar-width);
        }

        .main-content-collapsed {
            margin-left: var(--sidebar-collapsed-width);
        }
    </style>
</head>
<body>
    <!-- Botão de menu para mobile -->
    <div class="mobile-menu-button" id="mobileMenuButton">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </div>

    <!-- Overlay para fechar o menu mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar sidebar-expanded" id="sidebar">
        <!-- Cabeçalho da sidebar -->
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="../../assets/images/logo-icon.png" alt="KlubeCash" id="logoIcon">
                <span class="sidebar-logo-text" id="logoText">KlubeCash</span>
            </div>
        </div>

        <!-- Botão de toggle da sidebar -->
        <div class="sidebar-toggle" id="sidebarToggle">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" id="toggleIcon">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </div>

        <!-- Menu de navegação -->
        <div class="sidebar-menu">
            <a href="/klube-cash/views/admin/dashboard.php" class="sidebar-menu-item <?php echo $activeMenu === 'painel' ? 'active' : ''; ?>">
                <div class="sidebar-menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <span class="sidebar-menu-text">Painel</span>
            </a>

            <a href="/klube-cash/views/admin/users.php" class="sidebar-menu-item <?php echo $activeMenu === 'usuarios' ? 'active' : ''; ?>">
                <div class="sidebar-menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <span class="sidebar-menu-text">Usuários</span>
            </a>

            <a href="/klube-cash/views/admin/stores.php" class="sidebar-menu-item <?php echo $activeMenu === 'lojas' ? 'active' : ''; ?>">
                <div class="sidebar-menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3h18v18H3zM21 9H3M21 15H3M12 3v18"></path>
                    </svg>
                </div>
                <span class="sidebar-menu-text">Lojas</span>
            </a>

            <a href="/klube-cash/views/admin/transactions.php" class="sidebar-menu-item <?php echo $activeMenu === 'compras' ? 'active' : ''; ?>">
                <div class="sidebar-menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                </div>
                <span class="sidebar-menu-text">Compras</span>
            </a>

            <a href="/klube-cash/views/admin/reports.php" class="sidebar-menu-item <?php echo $activeMenu === 'relatorios' ? 'active' : ''; ?>">
                <div class="sidebar-menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <span class="sidebar-menu-text">Relatórios</span>
            </a>

            <a href="/klube-cash/views/admin/settings.php" class="sidebar-menu-item <?php echo $activeMenu === 'configuracoes' ? 'active' : ''; ?>">
                <div class="sidebar-menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                </div>
                <span class="sidebar-menu-text">Configurações</span>
            </a>
        </div>

        <!-- Rodapé da sidebar com botão de sair -->
        <div class="sidebar-footer">
            <a href="/klube-cash/controllers/AuthController.php?action=logout" class="sidebar-logout">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span class="sidebar-logout-text">Sair</span>
            </a>
        </div>
    </div>

    <script>
        // Elementos DOM
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const toggleIcon = document.getElementById('toggleIcon');
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.querySelector('.main-content') || document.body;
        const logoText = document.getElementById('logoText');

        // Estado da sidebar (expandido/recolhido)
        let sidebarState = localStorage.getItem('sidebarState') || 'expanded';

        // Função para alternar a sidebar entre expandida e recolhida
        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                // Comportamento mobile
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            } else {
                // Comportamento desktop
                if (sidebarState === 'expanded') {
                    // Colapsar sidebar
                    sidebar.classList.remove('sidebar-expanded');
                    sidebar.classList.add('sidebar-collapsed');
                    mainContent.classList.remove('main-content-expanded');
                    mainContent.classList.add('main-content-collapsed');
                    toggleIcon.innerHTML = '<polyline points="9 18 15 12 9 6"></polyline>';
                    logoText.style.display = 'none';
                    sidebarState = 'collapsed';
                } else {
                    // Expandir sidebar
                    sidebar.classList.remove('sidebar-collapsed');
                    sidebar.classList.add('sidebar-expanded');
                    mainContent.classList.remove('main-content-collapsed');
                    mainContent.classList.add('main-content-expanded');
                    toggleIcon.innerHTML = '<polyline points="15 18 9 12 15 6"></polyline>';
                    logoText.style.display = 'block';
                    sidebarState = 'expanded';
                }
                // Salvar estado no localStorage
                localStorage.setItem('sidebarState', sidebarState);
            }
        }

        // Aplicar estado inicial da sidebar
        function applySidebarState() {
            if (window.innerWidth <= 768) {
                // Estado mobile
                sidebar.classList.add('sidebar-mobile');
                sidebar.classList.remove('sidebar-expanded', 'sidebar-collapsed');
                mainContent.classList.remove('main-content-expanded', 'main-content-collapsed');
            } else {
                // Estado desktop
                sidebar.classList.remove('sidebar-mobile', 'active');
                sidebarOverlay.classList.remove('active');
                
                if (sidebarState === 'expanded') {
                    sidebar.classList.add('sidebar-expanded');
                    sidebar.classList.remove('sidebar-collapsed');
                    mainContent.classList.add('main-content-expanded');
                    mainContent.classList.remove('main-content-collapsed');
                    toggleIcon.innerHTML = '<polyline points="15 18 9 12 15 6"></polyline>';
                    logoText.style.display = 'block';
                } else {
                    sidebar.classList.add('sidebar-collapsed');
                    sidebar.classList.remove('sidebar-expanded');
                    mainContent.classList.add('main-content-collapsed');
                    mainContent.classList.remove('main-content-expanded');
                    toggleIcon.innerHTML = '<polyline points="9 18 15 12 9 6"></polyline>';
                    logoText.style.display = 'none';
                }
            }
        }

        // Event listeners
        sidebarToggle.addEventListener('click', toggleSidebar);
        mobileMenuButton.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);

        // Responder a mudanças de tamanho da tela
        window.addEventListener('resize', applySidebarState);

        // Aplicar estado inicial ao carregar a página
        applySidebarState();
    </script>
</body>
</html>