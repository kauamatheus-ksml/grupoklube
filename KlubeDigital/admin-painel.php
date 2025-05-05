<?php
// admin-painel.php - Arquivo principal do painel administrativo

session_start();

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Obter nome do administrador
$adminName = $_SESSION['admin_name'] ?? 'Administrador';

// O restante do arquivo é o HTML do painel administrativo
// Você pode incluir o HTML do painel aqui ou usar um include
// Vou usar um include para manter o código organizado

include('admin-panel.html');