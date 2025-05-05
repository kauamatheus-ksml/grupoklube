<?php
// editar-usuario.php - Formulário para edição de usuário
// Este arquivo é incluído pelo gerenciar-usuario.php quando a ação é 'editar'

// Verificar se as variáveis necessárias estão definidas
if (!isset($username) || !isset($usuario)) {
    header('Location: usuarios.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Klube Digital</title>
    <link rel="stylesheet" href="assents/css/admin-style.css">
</head>
<body>
    <!-- Overlay para a sidebar em mobile -->
    <div class="sidebar-overlay"></div>
    
    <!-- Header -->
    <header id="admin-header">
        <div class="container admin-header-container">
            <div class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="logo">
                <img src="assents/img/klubedigitallogo.svg" alt="Klube Digital Logo" class="logo-img">
                <h1>Klube Digital</h1>
            </div>
            <div class="admin-user">
                <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <a href="logout.php" class="admin-logout">Sair</a>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <nav class="sidebar-nav">
            <ul>
                <li><a href="admin-painel.php" data-section="dashboard"><span class="icon">📊</span> Dashboard</a></li>
                <li><a href="admin-painel.php#contatos" data-section="contatos"><span class="icon">👥</span> Contatos</a></li>
                <li><a href="admin-painel.php#atendimentos" data-section="atendimentos"><span class="icon">💬</span> Atendimentos</a></li>
                <li><a href="admin-painel.php#relatorios" data-section="relatorios"><span class="icon">📈</span> Relatórios</a></li>
                <li class="active"><a href="usuarios.php"><span class="icon">👤</span> Usuários</a></li>
                <li><a href="admin-painel.php#configuracoes" data-section="configuracoes"><span class="icon">⚙️</span> Configurações</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-content">
        <section class="content-section active">
            <div class="section-header">
                <h2>Editar Usuário</h2>
                <p>Altere os dados do usuário <?php echo htmlspecialchars($username); ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Dados do Usuário</h3>
                </div>
                <div class="card-body">
                    <form action="gerenciar-usuario.php?acao=editar&username=<?php echo urlencode($username); ?>" method="post">
                        <div class="form-group">
                            <label for="username">Nome de Usuário</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($username); ?>" disabled>
                            <p class="form-help">O nome de usuário não pode ser alterado</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="senha">Nova Senha</label>
                            <input type="password" id="senha" name="senha">
                            <p class="form-help">Deixe em branco para manter a senha atual</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="nivel">Nível de Acesso</label>
                            <select id="nivel" name="nivel" required>
                                <option value="admin" <?php echo ($usuario['nivel'] ?? 'admin') === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                <option value="editor" <?php echo ($usuario['nivel'] ?? '') === 'editor' ? 'selected' : ''; ?>>Editor</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Salvar Alterações</button>
                            <a href="usuarios.php" class="btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicialização da sidebar para mobile
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            if (mobileMenuBtn && sidebar && overlay) {
                // Toggle sidebar ao clicar no botão mobile
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
            }
        });
    </script>
</body>
</html>
