<?php
// views/admin/settings.php
// Definir o menu ativo na sidebar
$activeMenu = 'configuracoes';

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

// Inicializar variáveis
$message = '';
$messageType = '';

// Processar formulário se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug para ver o que está sendo enviado
    error_log('POST data: ' . print_r($_POST, true));
    
    try {
        // Converter valores para float para garantir o formato correto
        $data = [
            'porcentagem_total' => floatval($_POST['porcentagem_total']),
            'porcentagem_cliente' => floatval($_POST['porcentagem_cliente']),
            'porcentagem_admin' => floatval($_POST['porcentagem_admin']),
            'porcentagem_loja' => floatval($_POST['porcentagem_loja'])
        ];
        
        // Verificar se a soma está correta
        $soma = $data['porcentagem_cliente'] + $data['porcentagem_admin'] + $data['porcentagem_loja'];
        if (abs($soma - $data['porcentagem_total']) > 0.01) {
            $message = 'Erro: A soma das porcentagens (' . number_format($soma, 2) . '%) não é igual à porcentagem total (' . number_format($data['porcentagem_total'], 2) . '%).';
            $messageType = 'danger';
        } else {
            $result = AdminController::updateSettings($data);
            
            if ($result['status']) {
                $message = 'Configurações atualizadas com sucesso!';
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'danger';
            }
        }
    } catch (Exception $e) {
        error_log('Erro ao atualizar configurações: ' . $e->getMessage());
        $message = 'Erro ao atualizar configurações: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Obter configurações atuais
try {
    $settingsResult = AdminController::getSettings();
    
    if ($settingsResult['status']) {
        $settings = $settingsResult['data'];
    } else {
        $message = $settingsResult['message'];
        $messageType = 'danger';
        $settings = [
            'porcentagem_total' => DEFAULT_CASHBACK_TOTAL,
            'porcentagem_cliente' => DEFAULT_CASHBACK_CLIENT,
            'porcentagem_admin' => DEFAULT_CASHBACK_ADMIN,
            'porcentagem_loja' => DEFAULT_CASHBACK_STORE
        ];
    }
} catch (Exception $e) {
    error_log('Erro ao carregar configurações: ' . $e->getMessage());
    $message = 'Erro ao carregar configurações: ' . $e->getMessage();
    $messageType = 'danger';
    $settings = [
        'porcentagem_total' => DEFAULT_CASHBACK_TOTAL,
        'porcentagem_cliente' => DEFAULT_CASHBACK_CLIENT,
        'porcentagem_admin' => DEFAULT_CASHBACK_ADMIN,
        'porcentagem_loja' => DEFAULT_CASHBACK_STORE
    ];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Klube Cash</title>
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
            --border-radius: 8px;
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
            padding: 20px;
            min-height: 100vh;
        }
        
        /* Título da página */
        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-gray);
            margin-bottom: 25px;
        }
        
        /* Alertas */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #E8F5E9;
            color: var(--success-color);
            border: 1px solid #C8E6C9;
        }
        
        .alert-danger {
            background-color: #FFEBEE;
            color: var(--danger-color);
            border: 1px solid #FFCDD2;
        }
        
        /* Cards */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 25px;
            border: 1px solid #FFD9B3;
            overflow: hidden;
        }
        
        .card-header {
            padding: 15px 20px;
            background-color: #FFFAF3;
            border-bottom: 1px solid #FFD9B3;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Formulários */
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
            margin-bottom: 10px;
        }
        
        .form-group {
            flex: 1 0 200px;
            margin: 10px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-gray);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #E0E0E0;
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }
        
        .form-text {
            font-size: 12px;
            color: var(--medium-gray);
            margin-top: 5px;
        }
        
        .form-divider {
            width: 100%;
            height: 1px;
            background-color: #E0E0E0;
            margin: 20px 0;
        }
        
        /* Botões */
        .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: #E06E00;
        }
        
        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .page-wrapper {
                padding: 75px 20px;
            }
            .main-content {
                padding-left: 0;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .form-group {
                margin: 5px 0;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="page-wrapper">
            <h1 class="page-title">Configurações</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" id="settingsForm">
                <!-- Configurações de Cashback -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Configurações de Cashback</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="porcentagemTotal">Porcentagem Total de Cashback</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemTotal" name="porcentagem_total" value="<?php echo $settings['porcentagem_total']; ?>" required>
                                <small class="form-text">Porcentagem total aplicada sobre o valor da compra</small>
                            </div>
                        </div>
                        
                        <div class="form-divider"></div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="porcentagemCliente">Porcentagem para o Cliente</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemCliente" name="porcentagem_cliente" value="<?php echo $settings['porcentagem_cliente']; ?>" required>
                                <small class="form-text">Parte do cashback que vai para o cliente</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="porcentagemAdmin">Porcentagem para o Admin</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemAdmin" name="porcentagem_admin" value="<?php echo $settings['porcentagem_admin']; ?>" required>
                                <small class="form-text">Parte do cashback que vai para a plataforma (comissão)</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="porcentagemLoja">Porcentagem para a Loja</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="porcentagemLoja" name="porcentagem_loja" value="<?php echo $settings['porcentagem_loja']; ?>" required>
                                <small class="form-text">Parte do cashback que vai para a loja parceira</small>
                            </div>
                        </div>
                        
                        <p class="form-text" id="somaInfo">
                            A soma das porcentagens deve ser igual à porcentagem total.
                            <strong>Soma atual: <span id="somaAtual">0.00</span>%</strong>
                        </p>
                    </div>
                </div>
                
                <!-- Limites e Valores Mínimos -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Limites e Valores Mínimos</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="minTransactionValue">Valor Mínimo de Transação</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="minTransactionValue" value="<?php echo MIN_TRANSACTION_VALUE; ?>" readonly>
                                <small class="form-text">Valor mínimo para uma transação ser processada</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="minWithdrawalValue">Valor Mínimo para Saque</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="minWithdrawalValue" value="<?php echo MIN_WITHDRAWAL_VALUE; ?>" readonly>
                                <small class="form-text">Valor mínimo para solicitação de saque</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Configurações de Sistema -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Configurações do Sistema</h2>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="systemName">Nome do Sistema</label>
                                <input type="text" class="form-control" id="systemName" value="<?php echo SYSTEM_NAME; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="systemVersion">Versão</label>
                                <input type="text" class="form-control" id="systemVersion" value="<?php echo SYSTEM_VERSION; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="adminEmail">Email do Administrador</label>
                                <input type="email" class="form-control" id="adminEmail" value="<?php echo ADMIN_EMAIL; ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="itemsPerPage">Itens por Página</label>
                                <input type="number" class="form-control" id="itemsPerPage" value="<?php echo ITEMS_PER_PAGE; ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botões de Ação -->
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Atualizar soma das porcentagens em tempo real
        function updateSoma() {
            const porcentagemCliente = parseFloat(document.getElementById('porcentagemCliente').value) || 0;
            const porcentagemAdmin = parseFloat(document.getElementById('porcentagemAdmin').value) || 0;
            const porcentagemLoja = parseFloat(document.getElementById('porcentagemLoja').value) || 0;
            
            const soma = porcentagemCliente + porcentagemAdmin + porcentagemLoja;
            document.getElementById('somaAtual').textContent = soma.toFixed(2);
            
            // Verificar se soma é igual à porcentagem total
            const porcentagemTotal = parseFloat(document.getElementById('porcentagemTotal').value) || 0;
            const somaInfo = document.getElementById('somaInfo');
            
            if (Math.abs(soma - porcentagemTotal) > 0.01) {
                somaInfo.style.color = 'var(--danger-color)';
            } else {
                somaInfo.style.color = 'var(--success-color)';
            }
        }
        
        // Adicionar eventos para campos de porcentagem
        document.getElementById('porcentagemTotal').addEventListener('input', updateSoma);
        document.getElementById('porcentagemCliente').addEventListener('input', updateSoma);
        document.getElementById('porcentagemAdmin').addEventListener('input', updateSoma);
        document.getElementById('porcentagemLoja').addEventListener('input', updateSoma);
        
        // Inicializar soma
        document.addEventListener('DOMContentLoaded', updateSoma);
        
        // Validar formulário antes de enviar
        document.getElementById('settingsForm').addEventListener('submit', function(event) {
            const porcentagemTotal = parseFloat(document.getElementById('porcentagemTotal').value);
            const porcentagemCliente = parseFloat(document.getElementById('porcentagemCliente').value);
            const porcentagemAdmin = parseFloat(document.getElementById('porcentagemAdmin').value);
            const porcentagemLoja = parseFloat(document.getElementById('porcentagemLoja').value);
            
            // Verificar se valores são válidos
            if (isNaN(porcentagemTotal) || isNaN(porcentagemCliente) || isNaN(porcentagemAdmin) || isNaN(porcentagemLoja)) {
                alert('Por favor, preencha todos os campos com valores numéricos válidos.');
                event.preventDefault();
                return false;
            }
            
            // Verificar se porcentagens estão entre 0 e 100
            if (porcentagemTotal < 0 || porcentagemTotal > 100 || 
                porcentagemCliente < 0 || porcentagemCliente > 100 || 
                porcentagemAdmin < 0 || porcentagemAdmin > 100 || 
                porcentagemLoja < 0 || porcentagemLoja > 100) {
                alert('As porcentagens devem estar entre 0 e 100.');
                event.preventDefault();
                return false;
            }
            
            // Verificar se a soma das porcentagens é igual à porcentagem total
            const soma = porcentagemCliente + porcentagemAdmin + porcentagemLoja;
            if (Math.abs(soma - porcentagemTotal) > 0.01) {
                alert('A soma das porcentagens (cliente, admin e loja) deve ser igual à porcentagem total.');
                event.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>