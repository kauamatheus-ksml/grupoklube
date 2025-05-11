<?php
// views/client/profile.php
// Definir o menu ativo
$activeMenu = 'perfil';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/ClientController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header("Location: ../auth/login.php?error=acesso_restrito");
    exit;
}

// Obter dados do perfil
$userId = $_SESSION['user_id'];
$result = ClientController::getProfileData($userId);

// Verificar se houve erro
$hasError = !$result['status'];
$errorMessage = $hasError ? $result['message'] : '';

// Dados para exibição
$profileData = $hasError ? [] : $result['data'];

// Processar atualização de perfil
$updateSuccess = false;
$updateMessage = '';

if (isset($_POST['update_profile'])) {
    $updateData = [
        'nome' => $_POST['nome'] ?? '',
        'contato' => [
            'telefone' => $_POST['telefone'] ?? '',
            'celular' => $_POST['celular'] ?? '',
            'email_alternativo' => $_POST['email_alternativo'] ?? ''
        ],
        'endereco' => [
            'cep' => $_POST['cep'] ?? '',
            'logradouro' => $_POST['logradouro'] ?? '',
            'numero' => $_POST['numero'] ?? '',
            'complemento' => $_POST['complemento'] ?? '',
            'bairro' => $_POST['bairro'] ?? '',
            'cidade' => $_POST['cidade'] ?? '',
            'estado' => $_POST['estado'] ?? ''
        ]
    ];
    
    $updateResult = ClientController::updateProfile($userId, $updateData);
    $updateSuccess = $updateResult['status'];
    $updateMessage = $updateResult['message'];
    
    // Recarregar dados do perfil se atualização bem-sucedida
    if ($updateSuccess) {
        $result = ClientController::getProfileData($userId);
        $hasError = !$result['status'];
        $profileData = $hasError ? [] : $result['data'];
    }
}

// Processar atualização de senha
$passwordSuccess = false;
$passwordMessage = '';

if (isset($_POST['update_password'])) {
    $passwordData = [
        'senha_atual' => $_POST['senha_atual'] ?? '',
        'nova_senha' => $_POST['nova_senha'] ?? '',
        'confirmar_senha' => $_POST['confirmar_senha'] ?? ''
    ];
    
    // Validar senhas
    if ($passwordData['nova_senha'] !== $passwordData['confirmar_senha']) {
        $passwordSuccess = false;
        $passwordMessage = 'As senhas não coincidem.';
    } else {
        $updateResult = ClientController::updateProfile($userId, ['senha_atual' => $passwordData['senha_atual'], 'nova_senha' => $passwordData['nova_senha']]);
        $passwordSuccess = $updateResult['status'];
        $passwordMessage = $updateResult['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Cabeçalho da página */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 28px;
            color: var(--dark-gray);
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: var(--medium-gray);
            font-size: 16px;
        }
        
        /* Grade de perfil */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }
        
        /* Cards */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 18px;
            color: var(--dark-gray);
        }
        
        /* Perfil lateral */
        .profile-sidebar {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .profile-name {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .profile-email {
            color: var(--medium-gray);
            margin-bottom: 20px;
        }
        
        .profile-stats {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }
        
        .stat-item {
            background-color: var(--primary-light);
            padding: 15px;
            border-radius: 10px;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--medium-gray);
        }
        
        /* Formulários */
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
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        /* Botões */
        .btn {
            padding: 12px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #E06E00;
            transform: translateY(-2px);
        }
        
        /* Alertas */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #E6F7E6;
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: #FFEAE6;
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        /* Formatação de detalhes */
        .details-item {
            margin-bottom: 15px;
        }
        
        .details-label {
            font-size: 14px;
            color: var(--medium-gray);
        }
        
        .details-value {
            font-size: 16px;
            color: var(--dark-gray);
            font-weight: 500;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Incluir navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="container" style="margin-top: 80px;">
        <div class="page-header">
            <h1>Meu Perfil</h1>
            <p class="page-subtitle">Gerencie suas informações pessoais e preferências</p>
        </div>
        
        <?php if ($hasError): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
        
        <!-- Grade de Perfil -->
        <div class="profile-grid">
            <!-- Sidebar de Perfil -->
            <div class="profile-sidebar-container">
                <div class="card profile-sidebar">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($profileData['perfil']['nome'] ?? 'U', 0, 1)); ?>
                    </div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($profileData['perfil']['nome'] ?? 'Usuário'); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($profileData['perfil']['email'] ?? 'email@exemplo.com'); ?></p>
                    <p style="color: var(--medium-gray); font-size: 14px;">
                        Membro desde: <?php echo date('d/m/Y', strtotime($profileData['perfil']['data_criacao'] ?? 'now')); ?>
                    </p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value">R$ <?php echo number_format($profileData['estatisticas']['total_cashback'] ?? 0, 2, ',', '.'); ?></div>
                            <div class="stat-label">Cashback Total</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $profileData['estatisticas']['total_transacoes'] ?? 0; ?></div>
                            <div class="stat-label">Transações</div>
                        </div>
                    </div>
                </div>
                
                <!-- Card de Detalhes da Conta -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Detalhes da Conta</h3>
                    </div>
                    
                    <div class="details-item">
                        <div class="details-label">Status</div>
                        <div class="details-value">
                            <span style="color: var(--success-color); background-color: #E6F7E6; padding: 3px 10px; border-radius: 20px; font-size: 14px;">
                                <?php echo ucfirst($profileData['perfil']['status'] ?? 'Ativo'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="details-item">
                        <div class="details-label">Último Login</div>
                        <div class="details-value">
                            <?php 
                            $ultimoLogin = $profileData['perfil']['ultimo_login'] ?? null;
                            echo $ultimoLogin ? date('d/m/Y H:i', strtotime($ultimoLogin)) : 'Não disponível';
                            ?>
                        </div>
                    </div>
                    
                    <div class="details-item">
                        <div class="details-label">Lojas Utilizadas</div>
                        <div class="details-value"><?php echo $profileData['estatisticas']['total_lojas_utilizadas'] ?? 0; ?></div>
                    </div>
                    
                    <div class="details-item">
                        <div class="details-label">Total de Compras</div>
                        <div class="details-value">R$ <?php echo number_format($profileData['estatisticas']['total_compras'] ?? 0, 2, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Conteúdo Principal -->
            <div class="profile-content">
                <!-- Alertas de atualização -->
                <?php if ($updateSuccess): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($updateMessage); ?>
                    </div>
                <?php elseif (!empty($updateMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($updateMessage); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($passwordSuccess): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($passwordMessage); ?>
                    </div>
                <?php elseif (!empty($passwordMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($passwordMessage); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário de Informações Pessoais -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Informações Pessoais</h3>
                    </div>
                    
                    <form action="" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($profileData['perfil']['nome'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($profileData['perfil']['email'] ?? ''); ?>" disabled>
                            <small style="color: var(--medium-gray); font-size: 12px;">O e-mail não pode ser alterado.</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" class="form-control" value="<?php echo htmlspecialchars($profileData['contato']['telefone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="celular">Celular</label>
                                <input type="tel" id="celular" name="celular" class="form-control" value="<?php echo htmlspecialchars($profileData['contato']['celular'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email_alternativo">E-mail Alternativo</label>
                                <input type="email" id="email_alternativo" name="email_alternativo" class="form-control" value="<?php echo htmlspecialchars($profileData['contato']['email_alternativo'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">Salvar Alterações</button>
                    </form>
                </div>
                
                <!-- Formulário de Endereço -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Endereço</h3>
                    </div>
                    
                    <form action="" method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="cep">CEP</label>
                                <input type="text" id="cep" name="cep" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['cep'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label" for="logradouro">Logradouro</label>
                                <input type="text" id="logradouro" name="logradouro" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['logradouro'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="numero">Número</label>
                                <input type="text" id="numero" name="numero" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['numero'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="complemento">Complemento</label>
                            <input type="text" id="complemento" name="complemento" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['complemento'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="bairro">Bairro</label>
                                <input type="text" id="bairro" name="bairro" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['bairro'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['cidade'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="estado">Estado</label>
                                <input type="text" id="estado" name="estado" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['estado'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">Salvar Alterações</button>
                    </form>
                </div>
                
                <!-- Formulário de Alteração de Senha -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Alterar Senha</h3>
                    </div>
                    
                    <form action="" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="senha_atual">Senha Atual</label>
                            <input type="password" id="senha_atual" name="senha_atual" class="form-control" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="nova_senha">Nova Senha</label>
                                <input type="password" id="nova_senha" name="nova_senha" class="form-control" required>
                                <small style="color: var(--medium-gray); font-size: 12px;">Mínimo de <?php echo PASSWORD_MIN_LENGTH; ?> caracteres</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirmar_senha">Confirmar Nova Senha</label>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_password" class="btn btn-primary">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Script para preenchimento automático de endereço via CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                return;
            }
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                        document.getElementById('numero').focus();
                    }
                })
                .catch(error => console.error('Erro:', error));
        });
    </script>
</body>
</html>