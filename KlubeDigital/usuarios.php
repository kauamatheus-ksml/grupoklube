<?php
// usuarios.php - Interface de administração de usuários

session_start();

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Carregar usuários (em um sistema real, viria do banco de dados)
$usuarios_arquivo = __DIR__ . '/dados/usuarios.json';
$usuarios = [];

if (file_exists($usuarios_arquivo)) {
    $json_content = file_get_contents($usuarios_arquivo);
    $dados = json_decode($json_content, true);
    if ($dados && isset($dados['usuarios'])) {
        $usuarios = $dados['usuarios'];
    }
}

// Mensagem de feedback
$mensagem = '';
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']); // Limpar após exibir
}

// HTML da página
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Klube Digital</title>
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
                <h2>Gerenciar Usuários</h2>
                <p>Adicione, edite e remova usuários administrativos</p>
            </div>
            
            <?php if ($mensagem): ?>
            <div class="alert <?php echo strpos($mensagem, 'sucesso') !== false ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($mensagem); ?>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Adicionar Novo Usuário</h3>
                </div>
                <div class="card-body">
                    <form action="gerenciar-usuario.php" method="post">
                        <input type="hidden" name="acao" value="adicionar">
                        
                        <div class="form-group">
                            <label for="username">Nome de Usuário</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="senha">Senha</label>
                            <input type="password" id="senha" name="senha" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="nivel">Nível de Acesso</label>
                            <select id="nivel" name="nivel" required>
                                <option value="admin">Administrador</option>
                                <option value="editor">Editor</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-primary">Adicionar Usuário</button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3>Usuários Cadastrados</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($usuarios)): ?>
                    <p>Nenhum usuário cadastrado além do administrador padrão.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="contatos-tabela">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Nome</th>
                                    <th>E-mail</th>
                                    <th>Nível</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $username => $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($username); ?></td>
                                    <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['nivel'] ?? 'admin'); ?></td>
                                    <td><?php echo isset($user['criado_em']) ? date('d/m/Y H:i', strtotime($user['criado_em'])) : 'N/A'; ?></td>
                                    <td>
                                        <a href="gerenciar-usuario.php?acao=editar&username=<?php echo urlencode($username); ?>" class="btn-secondary btn-sm">Editar</a>
                                        <a href="gerenciar-usuario.php?acao=excluir&username=<?php echo urlencode($username); ?>" class="btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <!-- Mostrar o admin padrão -->
                                <tr>
                                    <td>admin</td>
                                    <td>Administrador</td>
                                    <td>admin@klubedigital.com.br</td>
                                    <td>admin</td>
                                    <td>Usuário padrão</td>
                                    <td>
                                        <span class="btn-secondary btn-sm disabled">Editar</span>
                                        <span class="btn-danger btn-sm disabled">Excluir</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
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
