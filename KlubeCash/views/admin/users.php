<?php
// Adicione no início do arquivo para ver os erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verifique como o ID está sendo recebido
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
error_log("API users.php - ID recebido: " . $id);
//views/admin/users.php
// Definir o menu ativo na sidebar
$activeMenu = 'usuarios';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/AdminController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_ADMIN) {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: /views/auth/login.php?error=acesso_restrito");
    exit;
}

// Obter parâmetros de paginação e filtros
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Preparar filtros
$filters = [];
if (!empty($search)) {
    $filters['busca'] = $search;
}
if (!empty($status)) {
    $filters['status'] = $status;
}
if (!empty($type)) {
    $filters['tipo'] = $type;
}

// Obter dados dos usuários
$result = AdminController::manageUsers($filters, $page);

// Verificar se houve erro
$hasError = !$result['status'];
$errorMessage = $hasError ? $result['message'] : '';

// Dados para exibição na página
$users = $hasError ? [] : $result['data']['usuarios'];
$statistics = $hasError ? [] : $result['data']['estatisticas'];
$pagination = $hasError ? [] : $result['data']['paginacao'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Klube Cash</title>
    <style>
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --success-color: #4CAF50;
            --danger-color: #F44336;
            --warning-color: #FFC107;
            --border-radius: 15px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }
        
        body {
            background-color: #FFF9F2;
            overflow-x: hidden;
        }
        
        /* Container principal */
        .main-content {
            padding-left: 250px;
            transition: padding-left 0.3s ease;
        }
        
        /* Wrapper da página */
        .page-wrapper {
            background-color: #FFF9F2;
            min-height: 100vh;
            padding: 30px;
        }
        
        /* Cabeçalho */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 24px;
            color: var(--dark-gray);
            font-weight: 600;
        }
        .alert-container {
            margin-bottom: 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .alert-danger {
            background-color: #FFEAE6;
            color: #F44336;
            border: 1px solid #F44336;
        }
        /* Barra de busca e ações */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .search-bar {
            position: relative;
            width: 300px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #FFD9B3;
            border-radius: 30px;
            background-color: var(--white);
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .search-bar .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }
        
        /* Botões */
        .btn {
            padding: 10px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: #E06E00;
        }
        
        /* Card principal */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid #FFD9B3;
            margin-bottom: 30px;
        }
        
        /* Tabela de usuários */
        .table-container {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 15px 10px;
            text-align: left;
        }
        
        .table th {
            font-weight: 600;
            color: var(--dark-gray);
            border-bottom: 2px solid #FFD9B3;
        }
        
        .table td {
            border-bottom: 1px solid #EEEEEE;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: var(--primary-light);
        }
        
        /* Status badges */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
        }
        
        .badge-success {
            background-color: #E6F7E6;
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: #FFF8E6;
            color: var(--warning-color);
        }
        
        .badge-danger {
            background-color: #FFEAE6;
            color: var(--danger-color);
        }
        
        /* Ações na tabela */
        .table-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--dark-gray);
            background-color: transparent;
            border: none;
        }
        
        .action-btn:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .action-btn.edit:hover {
            color: #2196F3;
        }
        
        .action-btn.delete:hover {
            color: var(--danger-color);
        }
        
        /* Checkbox personalizado */
        .checkbox-wrapper {
            display: inline-block;
            position: relative;
            width: 20px;
            height: 20px;
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            width: 0;
            height: 0;
        }
        
        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: #fff;
            border: 2px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .checkbox-wrapper input[type="checkbox"]:checked ~ .checkmark {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        
        .checkbox-wrapper input[type="checkbox"]:checked ~ .checkmark:after {
            display: block;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .pagination-item {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            color: var(--dark-gray);
            text-decoration: none;
        }
        
        .pagination-item:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .pagination-item.active {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .pagination-arrow {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--dark-gray);
            background-color: var(--white);
            border: 1px solid #EEEEEE;
            text-decoration: none;
        }
        
        .pagination-arrow:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        /* Modal de formulário */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background-color: var(--white);
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 500px;
            padding: 30px;
            box-shadow: var(--shadow);
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s;
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            cursor: pointer;
            color: var(--medium-gray);
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger-color);
        }
        
        /* Formulário */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #E0E0E0;
            border-radius: 8px;
            font-size: 14px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Cpath fill='%23333' d='M8 12l-6-6 1.41-1.41L8 9.17l4.59-4.58L14 6z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark-gray);
        }
        
        .btn-secondary:hover {
            background-color: #E0E0E0;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .main-content {
                padding-left: 0;
            }
            
            .page-wrapper {
                padding: 20px 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .actions-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-bar {
                width: 100%;
            }
            
            .modal-content {
                max-width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <!-- Cabeçalho da Página -->
            <div class="page-header">
                <h1>Usuários</h1>
                <button class="btn btn-primary" onclick="showUserModal()">Adicionar</button>
            </div>
            <div class="page-header">
                <h1>Usuários</h1>
                <button class="btn btn-primary" onclick="showUserModal()">Adicionar</button>
            </div>

            <!-- Adicione este elemento -->
            <div id="errorMessage" class="alert-container"></div>
            <?php if ($hasError): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
            
            <!-- Barra de Busca e Filtros -->
            <div class="actions-bar">
                <form method="GET" action="" class="search-bar">
                    <input type="text" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search); ?>">
                    <div class="search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                </form>
            </div>
            
            <!-- Card Principal -->
            <div class="card">
                <!-- Tabela de Usuários -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        <span class="checkmark"></span>
                                    </div>
                                </th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Cadastro</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Nenhum usuário encontrado</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="checkbox-wrapper">
                                                <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>">
                                                <span class="checkmark"></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['data_criacao'])); ?></td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                switch ($user['status']) {
                                                    case 'ativo':
                                                        $statusClass = 'badge-success';
                                                        break;
                                                    case 'inativo':
                                                        $statusClass = 'badge-warning';
                                                        break;
                                                    case 'bloqueado':
                                                        $statusClass = 'badge-danger';
                                                        break;
                                                }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="action-btn edit" onclick="editUser(<?php echo $user['id']; ?>)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </button>
                                                <button class="action-btn delete" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['nome']); ?>')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginação -->
                <?php if (!empty($pagination) && $pagination['total_paginas'] > 1): ?>
                    <div class="pagination">
                        <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>" class="pagination-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </a>
                        
                        <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($pagination['total_paginas'], $startPage + 4);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>" class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?php echo min($pagination['total_paginas'], $page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type=<?php echo urlencode($type); ?>" class="pagination-arrow">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Adicionar/Editar Usuário -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="userModalTitle">Adicionar Usuário</h3>
                <div class="modal-close" onclick="hideUserModal()">
                    <!-- Ícone de fechar -->
                </div>
            </div>
            
            <form id="userForm" onsubmit="submitUserForm(event)">
                <input type="hidden" id="userId" name="id" value="">
                
                <!-- Campos do formulário -->
                <div class="form-group">
                    <label class="form-label" for="userName">Nome</label>
                    <input type="text" class="form-control" id="userName" name="nome" required>
                </div>
                
                <!-- Outros campos... -->
                
                <div class="form-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideUserModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Variáveis globais
        let currentUserId = null;

        // Função para mostrar modal de adicionar usuário
        function showUserModal() {
            document.getElementById('userModalTitle').textContent = 'Adicionar Usuário';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('userPassword').required = true;
            document.getElementById('passwordHelp').textContent = 'Mínimo de 8 caracteres';
            currentUserId = null;
            
            // Mostrar modal
            document.getElementById('userModal').classList.add('show');
        }

        // Função para esconder modal
        function hideUserModal() {
            document.getElementById('userModal').classList.remove('show');
        }
        // Certifique-se de que a URL está correta
        fetch(`/api/users.php?id=${userId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Dados recebidos:", data);
                if (data.status) {
                    // Preencha o formulário com os dados
                    document.getElementById('edit-user-id').value = data.data.id;
                    document.getElementById('edit-user-name').value = data.data.nome;
                    // Mais campos...
                    $('#editUserModal').modal('show');
                } else {
                    alert(data.message || "Erro ao carregar dados do usuário");
                }
            })
            .catch(error => {
                console.error("Erro na requisição:", error);
                alert("Erro ao conectar com o servidor. Verifique o console para mais detalhes.");
            });
        // Função para editar usuário
        function editUser(userId) {
            console.log("Editando usuário ID:", userId);
            let errorElement = document.getElementById('errorMessage');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = 'errorMessage';
                document.querySelector('.page-header').after(errorElement);
            }
            
            
            fetch('../../controllers/AdminController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=getUserDetails&user_id=' + userId
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error(`Resposta não é JSON: ${text.substring(0, 100)}...`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log("Dados recebidos:", data);
                
                if (data.status) {
                    // Verificar se os dados do usuário estão na estrutura correta
                    const userData = data.data && data.data.usuario ? data.data.usuario : null;
                    
                    if (!userData) {
                        console.error('Estrutura de dados inválida:', data);
                        alert('Erro: Dados do usuário não encontrados na resposta');
                        return;
                    }
                    
                    // Preencher o formulário
                    document.getElementById('userModalTitle').textContent = 'Editar Usuário';
                    document.getElementById('userId').value = userData.id;
                    document.getElementById('userName').value = userData.nome;
                    document.getElementById('userEmail').value = userData.email;
                    
                    // Campos opcionais - verificar se existem
                    if (document.getElementById('userPhone')) {
                        document.getElementById('userPhone').value = userData.telefone || '';
                    }
                    
                    document.getElementById('userStatus').value = userData.status || 'ativo';
                    
                    // Campo de senha opcional na edição
                    const passwordField = document.getElementById('userPassword');
                    if (passwordField) {
                        passwordField.required = false;
                        passwordField.value = '';
                        document.getElementById('passwordHelp').textContent = 'Deixe em branco para manter a senha atual';
                    }
                    
                    // Mostrar modal
                    document.getElementById('userModal').classList.add('show');
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                alert('Erro ao processar a solicitação: ' + error.message);
        }

        // Função para excluir usuário
        function deleteUser(userId, userName) {
            if (confirm(`Tem certeza que deseja EXCLUIR PERMANENTEMENTE o usuário "${userName}"?`)) {
                // Criar requisição para o endpoint correto
                const formData = new FormData();
                formData.append('action', 'updateUserStatus');  // Verifique se esta action existe ou use 'deleteUser'
                formData.append('user_id', userId);
                formData.append('status', 'inativo');  // Ou outro status para exclusão lógica
                
                fetch('../../controllers/AdminController.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        alert('Usuário excluído com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir usuário: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao processar a solicitação. Verifique o console para mais detalhes.');
                });
            }
        }

        // Função para enviar formulário
        function submitUserForm(event) {
            event.preventDefault();
            
            // Obter dados do formulário
            const form = document.getElementById('userForm');
            const formData = new FormData(form);
            const userId = formData.get('id');
            
            // Definir a URL e os parâmetros corretos
            let url = userId ? '../../controllers/AdminController.php' : '../../controllers/AuthController.php';
            let params = new FormData();
            
            if (userId) {
                // Editar usuário existente
                params.append('action', 'updateUser');
                params.append('user_id', userId);
                params.append('nome', formData.get('nome'));
                params.append('email', formData.get('email'));
                params.append('telefone', formData.get('telefone') || '');
                params.append('status', formData.get('status'));
                
                // Senha opcional na edição
                if (formData.get('senha')) {
                    params.append('senha', formData.get('senha'));
                }
            } else {
                // Adicionar novo usuário
                params.append('action', 'register');
                params.append('nome', formData.get('nome'));
                params.append('email', formData.get('email'));
                params.append('telefone', formData.get('telefone') || '');
                params.append('senha', formData.get('senha'));
                // Tipo padrão: cliente
                params.append('tipo', 'cliente');
            }
            
            // Enviar requisição
            fetch(url, {
                method: 'POST',
                body: params
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert(userId ? 'Usuário atualizado com sucesso!' : 'Usuário adicionado com sucesso!');
                    hideUserModal();
                    location.reload();
                } else {
                    alert('Erro: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar a solicitação. Verifique o console para mais detalhes.');
            });
        }

        // Função para marcar/desmarcar todos os checkboxes
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.user-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
    </script>
</body>
</html>