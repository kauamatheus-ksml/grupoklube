<?php
// admin.php - Arquivo de login para o painel administrativo

session_start();

// Verificar se já está logado
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin-painel.php');
    exit;
}

// Definir credenciais de acesso (em um sistema real, essas credenciais estariam em um banco de dados)
$usuarios = [
    'admin' => [
        'senha' => '$2y$10$2X5XOBRrm4ZbYYBh0jnqX.ptrmmJEoGHs5AuTGCGNBL4MgYf1SOa2', // "admin123" com hash
        'nome' => 'Administrador'
    ]
];

$erro = '';

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);
    
    if (!$username || !$password) {
        $erro = 'Usuário e senha são obrigatórios';
    } else if (!isset($usuarios[$username])) {
        $erro = 'Usuário ou senha incorretos';
    } else if (!password_verify($password, $usuarios[$username]['senha'])) {
        $erro = 'Usuário ou senha incorretos';
    } else {
        // Login bem-sucedido
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_name'] = $usuarios[$username]['nome'];
        
        // Redirecionar para o painel
        header('Location: admin-painel.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Administrativo</title>
    <style>
        :root {
            --primary: #1e2c8a;
            --secondary: #0c195d;
            --accent: #a6ff00;
            --light: #f5f5f7;
            --dark: #1a1a1a;
            --text: #333333;
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
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header img {
            height: 60px;
            margin-bottom: 15px;
        }
        
        .login-header h1 {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .login-form input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .login-form input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(30, 44, 138, 0.1);
        }
        
        .login-form button {
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
        
        .login-form button:hover {
            background-color: var(--secondary);
        }
        
        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="assents/img/klubedigitallogo.svg" alt="Klube Digital Logo">
            <h1>Painel Administrativo</h1>
        </div>
        
        <?php if ($erro): ?>
        <div class="error-message">
            <?php echo $erro; ?>
        </div>
        <?php endif; ?>
        
        <form class="login-form" method="post" action="">
            <div class="form-group">
                <label for="username">Usuário</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Entrar</button>
        </form>
        
        <a href="index.html" class="back-link">Voltar para o site</a>
    </div>
</body>
</html>