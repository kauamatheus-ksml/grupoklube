<?php
// gerenciar-usuario.php - Script para processar ações de gerenciamento de usuários

session_start();

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Arquivo de usuários
$usuarios_arquivo = __DIR__ . '/dados/usuarios.json';

// Carregar usuários existentes
$usuarios = [];
if (file_exists($usuarios_arquivo)) {
    $json_content = file_get_contents($usuarios_arquivo);
    $dados = json_decode($json_content, true);
    if ($dados && isset($dados['usuarios'])) {
        $usuarios = $dados['usuarios'];
    }
}

// Função para salvar os dados
function salvar_usuarios($usuarios) {
    global $usuarios_arquivo;
    
    // Verificar se o diretório existe, senão criar
    $dir = dirname($usuarios_arquivo);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    $dados = [
        'usuarios' => $usuarios,
        'meta' => [
            'ultima_atualizacao' => date('c')
        ]
    ];
    
    return file_put_contents($usuarios_arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Processar a ação solicitada
$acao = $_REQUEST['acao'] ?? '';

switch ($acao) {
    case 'adicionar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $senha = filter_input(INPUT_POST, 'senha', FILTER_UNSAFE_RAW);
            $nivel = filter_input(INPUT_POST, 'nivel', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            // Validar campos obrigatórios
            if (!$username || !$nome || !$email || !$senha) {
                $_SESSION['mensagem'] = 'Erro: Todos os campos são obrigatórios.';
                header('Location: usuarios.php');
                exit;
            }
            
            // Verificar se o usuário já existe
            if (isset($usuarios[$username]) || $username === 'admin') {
                $_SESSION['mensagem'] = 'Erro: Este nome de usuário já está em uso.';
                header('Location: usuarios.php');
                exit;
            }
            
            // Criar hash da senha
            $senha_hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);
            
            // Adicionar novo usuário
            $usuarios[$username] = [
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha_hash,
                'nivel' => $nivel,
                'criado_em' => date('c')
            ];
            
            // Salvar alterações
            if (salvar_usuarios($usuarios)) {
                $_SESSION['mensagem'] = 'Usuário adicionado com sucesso!';
            } else {
                $_SESSION['mensagem'] = 'Erro ao salvar os dados. Verifique as permissões de escrita.';
            }
        }
        break;
        
    case 'editar':
        $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        // Verificar se é o usuário padrão (que não pode ser editado)
        if ($username === 'admin') {
            $_SESSION['mensagem'] = 'Erro: O usuário administrador padrão não pode ser editado.';
            header('Location: usuarios.php');
            exit;
        }
        
        // Verificar se o usuário existe
        if (!isset($usuarios[$username])) {
            $_SESSION['mensagem'] = 'Erro: Usuário não encontrado.';
            header('Location: usuarios.php');
            exit;
        }
        
        // Se for POST, está salvando as alterações
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $senha = filter_input(INPUT_POST, 'senha', FILTER_UNSAFE_RAW);
            $nivel = filter_input(INPUT_POST, 'nivel', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            
            // Validar campos obrigatórios
            if (!$nome || !$email) {
                $_SESSION['mensagem'] = 'Erro: Nome e e-mail são obrigatórios.';
                header('Location: usuarios.php');
                exit;
            }
            
            // Atualizar dados do usuário
            $usuarios[$username]['nome'] = $nome;
            $usuarios[$username]['email'] = $email;
            $usuarios[$username]['nivel'] = $nivel;
            $usuarios[$username]['atualizado_em'] = date('c');
            
            // Atualizar senha apenas se foi fornecida
            if ($senha) {
                $usuarios[$username]['senha'] = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);
            }
            
            // Salvar alterações
            if (salvar_usuarios($usuarios)) {
                $_SESSION['mensagem'] = 'Usuário atualizado com sucesso!';
                header('Location: usuarios.php');
                exit;
            } else {
                $_SESSION['mensagem'] = 'Erro ao salvar os dados. Verifique as permissões de escrita.';
                header('Location: usuarios.php');
                exit;
            }
        } else {
            // Exibir formulário de edição
            $usuario = $usuarios[$username];
            include 'editar-usuario.php';
            exit;
        }
        break;
        
    case 'excluir':
        $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        // Verificar se é o usuário padrão (que não pode ser excluído)
        if ($username === 'admin') {
            $_SESSION['mensagem'] = 'Erro: O usuário administrador padrão não pode ser excluído.';
            header('Location: usuarios.php');
            exit;
        }
        
        // Verificar se o usuário existe
        if (!isset($usuarios[$username])) {
            $_SESSION['mensagem'] = 'Erro: Usuário não encontrado.';
            header('Location: usuarios.php');
            exit;
        }
        
        // Remover o usuário
        unset($usuarios[$username]);
        
        // Salvar alterações
        if (salvar_usuarios($usuarios)) {
            $_SESSION['mensagem'] = 'Usuário excluído com sucesso!';
        } else {
            $_SESSION['mensagem'] = 'Erro ao salvar os dados. Verifique as permissões de escrita.';
        }
        break;
        
    default:
        $_SESSION['mensagem'] = 'Ação inválida.';
}

// Redirecionar de volta para a página de usuários
header('Location: usuarios.php');
exit;
