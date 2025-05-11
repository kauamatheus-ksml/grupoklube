<?php
// components/navbar.php

// Garantir que a sessão está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se usuário está logado
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? $_SESSION['user_name'] : '';
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';

// Função para determinar o tipo de usuário em português
function getUserTypeLabel($type) {
    switch ($type) {
        case 'admin':
            return 'Administrador';
        case 'client':
        case 'cliente':
            return 'Cliente';
        case 'store':
        case 'loja':
            return 'Loja';
        default:
            return 'Usuário';
    }
}
?>

<nav class="navbar">
    <div class="navbar-left">
        <button id="toggleSidebar" class="toggle-sidebar-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        
        <div class="page-title">
            <?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?>
        </div>
    </div>
    
    <div class="navbar-right">
        <?php if ($isLoggedIn): ?>
            <div class="notifications-dropdown">
                <button class="notifications-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notifications-count">0</span>
                </button>
                
                <div class="notifications-menu">
                    <div class="notifications-header">
                        <h3>Notificações</h3>
                        <button class="mark-all-read">Marcar todas como lidas</button>
                    </div>
                    <div class="notifications-list">
                        <div class="empty-notifications">
                            <p>Nenhuma notificação</p>
                        </div>
                        <!-- As notificações serão carregadas via AJAX -->
                    </div>
                    <div class="notifications-footer">
                        <a href="/notifications">Ver todas</a>
                    </div>
                </div>
            </div>
            
            <div class="user-dropdown">
                <button class="user-btn">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </button>
                
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-avatar-large">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                        <div>
                            <h3><?php echo htmlspecialchars($userName); ?></h3>
                            <p><?php echo getUserTypeLabel($userType); ?></p>
                        </div>
                    </div>
                    <div class="menu-items">
                        <a href="/perfil" class="menu-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Meu Perfil</span>
                        </a>
                        <a href="/configuracoes" class="menu-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            <span>Configurações</span>
                        </a>
                        <form action="/controllers/AuthController.php" method="post" id="logoutForm">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="menu-item logout-btn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                <span>Sair</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="auth-buttons">
                <a href="/views/auth/login.php" class="login-btn">Entrar</a>
                <a href="/views/auth/register.php" class="register-btn">Cadastrar</a>
            </div>
        <?php endif; ?>
    </div>
</nav>

<style>
/* Estilos da Navbar */
.navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: var(--white);
    padding: 15px 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.navbar-left {
    display: flex;
    align-items: center;
}

.toggle-sidebar-btn {
    background: none;
    border: none;
    color: var(--dark-gray);
    cursor: pointer;
    margin-right: 15px;
    padding: 5px;
    display: none;
}

.page-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--dark-gray);
}

.navbar-right {
    display: flex;
    align-items: center;
}

/* Notificações */
.notifications-dropdown {
    position: relative;
    margin-right: 20px;
}

.notifications-btn {
    background: none;
    border: none;
    color: var(--dark-gray);
    cursor: pointer;
    padding: 5px;
    position: relative;
}

.notifications-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: var(--primary-color);
    color: white;
    border-radius: 50%;
    font-size: 10px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2px;
}

.notifications-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    width: 300px;
    max-height: 400px;
    overflow: hidden;
    display: none;
    z-index: 101;
    border: 1px solid #FFD9B3;
}

.notifications-dropdown:hover .notifications-menu {
    display: block;
}

.notifications-header {
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #FFD9B3;
}

.notifications-header h3 {
    font-size: 14px;
    font-weight: 600;
    color: var(--dark-gray);
    margin: 0;
}

.mark-all-read {
    background: none;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
}

.notifications-list {
    max-height: 300px;
    overflow-y: auto;
}

.empty-notifications {
    padding: 20px;
    text-align: center;
    color: var(--medium-gray);
}

.notifications-footer {
    padding: 10px 15px;
    border-top: 1px solid #FFD9B3;
    text-align: center;
}

.notifications-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
}

/* Dropdown do usuário */
.user-dropdown {
    position: relative;
}

.user-btn {
    display: flex;
    align-items: center;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    color: var(--dark-gray);
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 10px;
}

.user-name {
    margin-right: 5px;
    font-weight: 500;
}

.user-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    width: 250px;
    display: none;
    z-index: 101;
    border: 1px solid #FFD9B3;
    overflow: hidden;
}

.user-dropdown:hover .user-menu {
    display: block;
}

.user-info {
    padding: 15px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #FFD9B3;
}

.user-avatar-large {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background-color: var(--primary-color);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    margin-right: 15px;
}

.user-info h3 {
    font-size: 16px;
    font-weight: 600;
    color: var(--dark-gray);
    margin: 0 0 5px 0;
}

.user-info p {
    font-size: 12px;
    color: var(--medium-gray);
    margin: 0;
}

.menu-items {
    padding: 10px 0;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: var(--dark-gray);
    text-decoration: none;
    transition: background-color 0.3s;
}

.menu-item:hover {
    background-color: var(--primary-light);
}

.menu-item svg {
    margin-right: 10px;
    color: var(--primary-color);
}

.logout-btn {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    color: var(--dark-gray);
    cursor: pointer;
    font-family: var(--font-family);
    font-size: 14px;
}

/* Botões de autenticação */
.auth-buttons {
    display: flex;
    gap: 10px;
}

.login-btn, .register-btn {
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
}

.login-btn {
    color: var(--primary-color);
    background-color: transparent;
    border: 1px solid var(--primary-color);
}

.register-btn {
    color: var(--white);
    background-color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

/* Responsividade */
@media (max-width: 768px) {
    .toggle-sidebar-btn {
        display: block;
    }
    
    .user-name {
        display: none;
    }
}
</style>

<script>
// Toggle da sidebar em dispositivos móveis
document.getElementById('toggleSidebar').addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.getElementById('mainContent');
    
    // Se a sidebar estiver presente
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
        
        // Alterar padding do conteúdo principal
        if (mainContent) {
            if (sidebar.classList.contains('collapsed')) {
                mainContent.style.paddingLeft = '0';
            } else {
                mainContent.style.paddingLeft = '250px';
            }
        }
    }
});

// Carregar notificações via AJAX (exemplo)
function loadNotifications() {
    // Implementar depois com AJAX
    // Por enquanto, simular com um contador
    const count = Math.floor(Math.random() * 5);
    document.querySelector('.notifications-count').textContent = count;
    
    // Atualizar menu de notificações
    const notificationsList = document.querySelector('.notifications-list');
    
    if (count === 0) {
        notificationsList.innerHTML = '<div class="empty-notifications"><p>Nenhuma notificação</p></div>';
    } else {
        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="notification-item">
                    <div class="notification-content">
                        <strong>Nova transação</strong>
                        <p>Você recebeu cashback de uma compra</p>
                        <small>Há 5 minutos</small>
                    </div>
                </div>
            `;
        }
        notificationsList.innerHTML = html;
    }
}

// Carregar notificações ao iniciar
document.addEventListener('DOMContentLoaded', loadNotifications);

// Marcar todas como lidas
document.querySelector('.mark-all-read').addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelector('.notifications-count').textContent = '0';
    document.querySelector('.notifications-list').innerHTML = '<div class="empty-notifications"><p>Nenhuma notificação</p></div>';
});
</script>