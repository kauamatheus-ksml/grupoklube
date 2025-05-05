
<?php
// logout.php - Encerrar a sessão administrativa

session_start();

// Limpar variáveis de sessão
$_SESSION = [];

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header('Location: admin.php');
exit;