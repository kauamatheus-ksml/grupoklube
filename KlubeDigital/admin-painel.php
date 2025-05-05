<?php
// Arquivo: admin-painel.php
// Vamos corrigir o redirecionamento

session_start();

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Obter nome do administrador
$adminName = $_SESSION['admin_name'] ?? 'Administrador';

// Incluir o painel administrativo - vamos corrigir para usar o arquivo correto
include('painel.html');