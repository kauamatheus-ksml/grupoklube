<?php
// inicializar-usuario.php - Script para criar o primeiro usuário administrador
// Execute este script uma vez durante a instalação do sistema

// Verificar se já existe um usuário administrador
$usuarios_arquivo = __DIR__ . '/dados/usuarios.json';
$primeiro_acesso = true;

// Verificar se o diretório dados existe, senão criar
$dir = __DIR__ . '/dados';
if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
}

// Verificar se já existe o arquivo de usuários
if (file_exists($usuarios_arquivo)) {
    $json_content = file_get_contents($usuarios_arquivo);
    $dados = json_decode($json_content, true);
    
    // Verificar se já existem usuários cadastrados
    if ($dados && isset($dados['usuarios']) && !empty($dados['usuarios'])) {
        $primeiro_acesso = false;
    }
}

// Mensagem de status
$mensagem = '';
$sucesso = false;

// Processar o formulário de criação do primeiro usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $senha = filter_input(INPUT_POST, 'senha', FILTER_UNSAFE_RAW);
    $confirmar_senha = filter_input(INPUT_POST, 'confirmar_senha', FILTER_UNSAFE_RAW);
    
    // Validar campos
    if (!$username || !$nome || !$email || !$senha) {
        $mensagem = 'Todos os campos são obrigatórios.';
    } elseif ($senha !== $confirmar_senha) {
        $mensagem = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 8) {
        $mensagem = 'A senha deve ter pelo menos 8 caracteres.';
    } else {
        // Criar hash da senha
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);
        
        // Preparar dados do usuário
        $usuarios = [
            $username => [
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha_hash,
                'nivel' => 'admin',
                'criado_em' => date('c')
            ]
        ];
        
        // Preparar estrutura de dados
        $dados = [
            'usuarios' => $usuarios,
            'meta' => [
                'ultima_atualizacao' => date('c'),
                'iniciado_em' => date('c')
            ]
        ];
        
        // Salvar no arquivo
        if (file_put_contents($usuarios_arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $mensagem = 'Usuário administrador criado com sucesso! Agora você pode fazer login.';
            $sucesso = true;
            $primeiro_acesso = false;
        } else {
            $mensagem = 'Erro ao salvar os dados. Verifique as permissões de escrita.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicialização - Klube Digital</title>
    <style>
        :root {
            --primary: #1e2c8a;
            --secondary: #0c195d;
            --accent: #a6ff00;
            --light: #f5f5f7;
            --dark: #1a1a1a;
            --text: #333333;
            --success: #28a745;
            --danger: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', 'Helvetica', sans-serif;
        }
        
        body {
            background-color: var(--light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .setup-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 40px;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .setup-header img {
            height: 60px;
            margin-bottom: 15px;
        }
        
        .setup-header h1 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .setup-header p {
            color: var(--text);
            font-size: 0.95rem;
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }
        
        .success-message {
            background-color: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 44, 138, 0.1);
        }
        
        .form-group .form-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .form-button {
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .form-button:hover {
            background-color: var(--secondary);
        }
        
        .login-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.95rem;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
    </style>
    <link rel="shortcut icon" type="image/jpg" href="assents/img/klubedigital.ico"/>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <img src="assents/img/klubedigitallogo.svg" alt="Klube Digital Logo">
            <h1>Inicialização do Sistema</h1>
            <?php if ($primeiro_acesso): ?>
            <p>Crie o primeiro usuário administrador para acessar o sistema</p>
            <?php else: ?>
            <p>O sistema já foi inicializado</p>
            <?php endif; ?>
        </div>
        
        <?php if ($mensagem): ?>
        <div class="message <?php echo $sucesso ? 'success-message' : 'error-message'; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($primeiro_acesso): ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="username">Nome de Usuário</label>
                <input type="text" id="username" name="username" required>
                <div class="form-text">Será usado para fazer login no sistema</div>
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
                <div class="form-text">Mínimo de 8 caracteres</div>
            </div>
            
            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
            </div>
            
            <button type="submit" class="form-button">Criar Usuário Administrador</button>
        </form>
        <?php else: ?>
        <a href="admin.php" class="login-link">Ir para a página de login</a>
        <?php endif; ?>
    </div>
</body>
</html>