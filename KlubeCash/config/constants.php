<?php
/**
 * Constantes do sistema
 * Klube Cash - Sistema de Cashback
 */

// Informações básicas do sistema
define('SYSTEM_NAME', 'Klube Cash');
define('SYSTEM_VERSION', '1.0.0');
define('SITE_URL', 'https://klubecash.com');
define('ADMIN_EMAIL', 'admin@klubecash.com');

// Diretórios
define('ROOT_DIR', dirname(__DIR__));
define('VIEWS_DIR', ROOT_DIR . '/views');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('LOGS_DIR', ROOT_DIR . '/logs');

// Configurações de cashback padrão (em porcentagem)
define('DEFAULT_CASHBACK_TOTAL', 5.00);  // 5% de cashback total
define('DEFAULT_CASHBACK_CLIENT', 3.00); // 3% para o cliente
define('DEFAULT_CASHBACK_ADMIN', 1.00);  // 1% para o administrador
define('DEFAULT_CASHBACK_STORE', 1.00);  // 1% para a loja

// Status de transação
define('TRANSACTION_PENDING', 'pendente');
define('TRANSACTION_APPROVED', 'aprovado');
define('TRANSACTION_CANCELED', 'cancelado');

// Status de usuário
define('USER_ACTIVE', 'ativo');
define('USER_INACTIVE', 'inativo');
define('USER_BLOCKED', 'bloqueado');

// Tipos de usuário
define('USER_TYPE_CLIENT', 'cliente');
define('USER_TYPE_ADMIN', 'admin');
define('USER_TYPE_STORE', 'loja');

// Status de loja
define('STORE_PENDING', 'pendente');
define('STORE_APPROVED', 'aprovado');
define('STORE_REJECTED', 'rejeitado');

// Configurações de segurança
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 86400); // 24 horas em segundos
define('TOKEN_EXPIRATION', 7200);  // 2 horas em segundos

// Configurações de paginação
define('ITEMS_PER_PAGE', 10);

// Limites de valor
define('MIN_TRANSACTION_VALUE', 5.00);  // Valor mínimo de transação: R$ 5,00
define('MIN_WITHDRAWAL_VALUE', 20.00);  // Valor mínimo para saque: R$ 20,00

// Caminhos de URL
define('LOGIN_URL', SITE_URL . '/views/auth/login');
define('REGISTER_URL', SITE_URL . '/registro');
define('RECOVER_PASSWORD_URL', SITE_URL . '/recuperar-senha');
define('CLIENT_DASHBOARD_URL', SITE_URL . '/views/admin/dashboard');
define('ADMIN_DASHBOARD_URL', SITE_URL . '/views/admin/dashboard');