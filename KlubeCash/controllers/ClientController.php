<?php
// controllers/ClientController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/AuthController.php';

/**
 * Controlador do Cliente
 * Gerencia operações relacionadas a clientes como obtenção de extrato,
 * visualização de cashback, perfil e interação com lojas parceiras
 */
class ClientController {
    
    /**
     * Obtém os dados do dashboard do cliente
     * 
     * @param int $userId ID do cliente
     * @return array Dados do dashboard
     */
    public static function getDashboardData($userId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Obter saldo total de cashback
            $balanceStmt = $db->prepare("
                SELECT SUM(valor_cashback) as saldo_total
                FROM transacoes_cashback
                WHERE usuario_id = :user_id AND status = :status
            ");
            $balanceStmt->bindParam(':user_id', $userId);
            $status = TRANSACTION_APPROVED;
            $balanceStmt->bindParam(':status', $status);
            $balanceStmt->execute();
            $balanceData = $balanceStmt->fetch(PDO::FETCH_ASSOC);
            $totalBalance = $balanceData['saldo_total'] ?? 0;
            
            // Obter transações recentes
            $transactionsStmt = $db->prepare("
                SELECT t.*, l.nome_fantasia as loja_nome
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id
                ORDER BY t.data_transacao DESC
                LIMIT 5
            ");
            $transactionsStmt->bindParam(':user_id', $userId);
            $transactionsStmt->execute();
            $recentTransactions = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter estatísticas de cashback
            $statisticsStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_transacoes,
                    SUM(valor_total) as total_compras,
                    SUM(valor_cashback) as total_cashback,
                    MAX(data_transacao) as ultima_transacao
                FROM transacoes_cashback
                WHERE usuario_id = :user_id AND status = :status
            ");
            $statisticsStmt->bindParam(':user_id', $userId);
            $statisticsStmt->bindParam(':status', $status);
            $statisticsStmt->execute();
            $statistics = $statisticsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter lojas favoritas/mais utilizadas
            $favoritesStmt = $db->prepare("
                SELECT 
                    l.id, l.nome_fantasia, l.logo,
                    COUNT(t.id) as total_compras,
                    SUM(t.valor_cashback) as total_cashback
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id AND t.status = :status
                GROUP BY l.id
                ORDER BY total_compras DESC
                LIMIT 3
            ");
            $favoritesStmt->bindParam(':user_id', $userId);
            $favoritesStmt->bindParam(':status', $status);
            $favoritesStmt->execute();
            $favoriteStores = $favoritesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter notificações do cliente
            $notifications = self::getClientNotifications($userId);
            
            // Consolidar dados
            return [
                'status' => true,
                'data' => [
                    'saldo_total' => $totalBalance,
                    'transacoes_recentes' => $recentTransactions,
                    'estatisticas' => $statistics,
                    'lojas_favoritas' => $favoriteStores,
                    'notificacoes' => $notifications
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter dados do dashboard: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar dados do dashboard. Tente novamente.'];
        }
    }
    
    /**
     * Obtém o extrato de transações do cliente
     * 
     * @param int $userId ID do cliente
     * @param array $filters Filtros para o extrato (período, loja, etc)
     * @param int $page Página atual
     * @return array Extrato de transações
     */
    public static function getStatement($userId, $filters = [], $page = 1) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Preparar consulta base
            $query = "
                SELECT t.*, l.nome_fantasia as loja_nome
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.usuario_id = :user_id
            ";
            
            $params = [':user_id' => $userId];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por data inicial
                if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
                    $query .= " AND t.data_transacao >= :data_inicio";
                    $params[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
                }
                
                // Filtro por data final
                if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
                    $query .= " AND t.data_transacao <= :data_fim";
                    $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
                }
                
                // Filtro por loja
                if (isset($filters['loja_id']) && !empty($filters['loja_id'])) {
                    $query .= " AND t.loja_id = :loja_id";
                    $params[':loja_id'] = $filters['loja_id'];
                }
                
                // Filtro por status
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $query .= " AND t.status = :status";
                    $params[':status'] = $filters['status'];
                }
            }
            
            // Adicionar ordenação
            $query .= " ORDER BY t.data_transacao DESC";
            
            // Calcular total de registros para paginação
            $countStmt = $db->prepare(str_replace('t.*, l.nome_fantasia as loja_nome', 'COUNT(*) as total', $query));
            foreach ($params as $param => $value) {
                $countStmt->bindValue($param, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Adicionar paginação
            $perPage = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $offset, $perPage";
            
            // Executar consulta
            $stmt = $db->prepare($query);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estatísticas
            $statisticsQuery = "
                SELECT 
                    SUM(valor_total) as total_compras,
                    SUM(valor_cashback) as total_cashback,
                    COUNT(*) as total_transacoes
                FROM transacoes_cashback
                WHERE usuario_id = :user_id
            ";
            
            // Aplicar os mesmos filtros nas estatísticas
            if (!empty($filters)) {
                if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
                    $statisticsQuery .= " AND data_transacao >= :data_inicio";
                }
                
                if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
                    $statisticsQuery .= " AND data_transacao <= :data_fim";
                }
                
                if (isset($filters['loja_id']) && !empty($filters['loja_id'])) {
                    $statisticsQuery .= " AND loja_id = :loja_id";
                }
                
                if (isset($filters['status']) && !empty($filters['status'])) {
                    $statisticsQuery .= " AND status = :status";
                }
            }
            
            $statsStmt = $db->prepare($statisticsQuery);
            foreach ($params as $param => $value) {
                $statsStmt->bindValue($param, $value);
            }
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular informações de paginação
            $totalPages = ceil($totalCount / $perPage);
            
            return [
                'status' => true,
                'data' => [
                    'transacoes' => $transactions,
                    'estatisticas' => $statistics,
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter extrato: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar extrato. Tente novamente.'];
        }
    }
    
    /**
     * Obtém lista de lojas parceiras para o cliente
     * 
     * @param int $userId ID do cliente
     * @param array $filters Filtros para as lojas
     * @param int $page Página atual
     * @return array Lista de lojas parceiras
     */
    public static function getPartnerStores($userId, $filters = [], $page = 1) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Preparar consulta base
            $query = "
                SELECT l.*, 
                       IFNULL(
                           (SELECT SUM(t.valor_cashback) 
                            FROM transacoes_cashback t 
                            WHERE t.loja_id = l.id AND t.usuario_id = :user_id AND t.status = 'aprovado'), 
                           0
                       ) as cashback_recebido,
                       (SELECT COUNT(*) 
                        FROM transacoes_cashback t 
                        WHERE t.loja_id = l.id AND t.usuario_id = :user_id_count) as compras_realizadas
                FROM lojas l
                WHERE l.status = :status
            ";
            
            $params = [
                ':user_id' => $userId,
                ':user_id_count' => $userId,
                ':status' => STORE_APPROVED
            ];
            
            // Aplicar filtros
            if (!empty($filters)) {
                // Filtro por categoria
                if (isset($filters['categoria']) && !empty($filters['categoria'])) {
                    $query .= " AND l.categoria = :categoria";
                    $params[':categoria'] = $filters['categoria'];
                }
                
                // Filtro por nome
                if (isset($filters['nome']) && !empty($filters['nome'])) {
                    $query .= " AND (l.nome_fantasia LIKE :nome OR l.razao_social LIKE :nome)";
                    $params[':nome'] = '%' . $filters['nome'] . '%';
                }
                
                // Filtro por porcentagem de cashback
                if (isset($filters['cashback_min']) && !empty($filters['cashback_min'])) {
                    $query .= " AND l.porcentagem_cashback >= :cashback_min";
                    $params[':cashback_min'] = $filters['cashback_min'];
                }
            }
            
            // Adicionar ordenação (padrão: porcentagem de cashback decrescente)
            $orderBy = isset($filters['order_by']) ? $filters['order_by'] : 'porcentagem_cashback';
            $orderDir = isset($filters['order_dir']) && strtolower($filters['order_dir']) == 'asc' ? 'ASC' : 'DESC';
            $query .= " ORDER BY l.$orderBy $orderDir";
            
            // Calcular total de registros para paginação
            $countStmt = $db->prepare(str_replace('l.*, IFNULL', 'COUNT(*) as total, IFNULL', $query));
            foreach ($params as $param => $value) {
                $countStmt->bindValue($param, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Adicionar paginação
            $perPage = ITEMS_PER_PAGE;
            $offset = ($page - 1) * $perPage;
            $query .= " LIMIT $offset, $perPage";
            
            // Executar consulta
            $stmt = $db->prepare($query);
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            $stmt->execute();
            $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obter categorias disponíveis para filtro
            $categoriesStmt = $db->prepare("SELECT DISTINCT categoria FROM lojas WHERE status = :status ORDER BY categoria");
            $categoriesStmt->bindValue(':status', STORE_APPROVED);
            $categoriesStmt->execute();
            $categories = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Obter estatísticas
            $statsStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_lojas,
                    AVG(porcentagem_cashback) as media_cashback,
                    MAX(porcentagem_cashback) as maior_cashback
                FROM lojas
                WHERE status = :status
            ");
            $statsStmt->bindValue(':status', STORE_APPROVED);
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular informações de paginação
            $totalPages = ceil($totalCount / $perPage);
            
            return [
                'status' => true,
                'data' => [
                    'lojas' => $stores,
                    'categorias' => $categories,
                    'estatisticas' => $statistics,
                    'paginacao' => [
                        'total' => $totalCount,
                        'por_pagina' => $perPage,
                        'pagina_atual' => $page,
                        'total_paginas' => $totalPages
                    ]
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter lojas parceiras: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar lojas parceiras. Tente novamente.'];
        }
    }
    
    /**
     * Obtém detalhes do perfil do cliente
     * 
     * @param int $userId ID do cliente
     * @return array Dados do perfil
     */
    public static function getProfileData($userId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Obter dados do perfil
            $stmt = $db->prepare("
                SELECT id, nome, email, data_criacao, ultimo_login, status
                FROM usuarios
                WHERE id = :user_id AND tipo = :tipo
            ");
            $stmt->bindParam(':user_id', $userId);
            $tipo = USER_TYPE_CLIENT;
            $stmt->bindParam(':tipo', $tipo);
            $stmt->execute();
            $profileData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$profileData) {
                return ['status' => false, 'message' => 'Perfil não encontrado.'];
            }
            
            // Obter estatísticas do cliente
            $statsStmt = $db->prepare("
                SELECT 
                    SUM(valor_cashback) as total_cashback,
                    COUNT(*) as total_transacoes,
                    SUM(valor_total) as total_compras,
                    COUNT(DISTINCT loja_id) as total_lojas_utilizadas
                FROM transacoes_cashback
                WHERE usuario_id = :user_id AND status = :status
            ");
            $statsStmt->bindParam(':user_id', $userId);
            $status = TRANSACTION_APPROVED;
            $statsStmt->bindParam(':status', $status);
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Obter informações adicionais do cliente
            $addressStmt = $db->prepare("
                SELECT *
                FROM usuarios_endereco
                WHERE usuario_id = :user_id
                ORDER BY principal DESC
                LIMIT 1
            ");
            $addressStmt->bindParam(':user_id', $userId);
            $addressStmt->execute();
            $address = $addressStmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar se existe tabela de informações adicionais
            $contactStmt = $db->prepare("
                SELECT *
                FROM usuarios_contato
                WHERE usuario_id = :user_id
                LIMIT 1
            ");
            $contactStmt->bindParam(':user_id', $userId);
            $contactStmt->execute();
            $contact = $contactStmt->fetch(PDO::FETCH_ASSOC);
            
            // Consolidar dados
            return [
                'status' => true,
                'data' => [
                    'perfil' => $profileData,
                    'estatisticas' => $statistics,
                    'endereco' => $address ?: null,
                    'contato' => $contact ?: null
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter dados do perfil: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar dados do perfil. Tente novamente.'];
        }
    }
    
    /**
     * Atualiza os dados do perfil do cliente
     * 
     * @param int $userId ID do cliente
     * @param array $data Dados a serem atualizados
     * @return array Resultado da operação
     */
    public static function updateProfile($userId, $data) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Iniciar transação
            $db->beginTransaction();
            
            // Atualizar dados básicos
            if (isset($data['nome']) && !empty($data['nome'])) {
                $updateStmt = $db->prepare("UPDATE usuarios SET nome = :nome WHERE id = :user_id");
                $updateStmt->bindParam(':nome', $data['nome']);
                $updateStmt->bindParam(':user_id', $userId);
                $updateStmt->execute();
            }
            
            // Atualizar senha se fornecida
            if (isset($data['senha_atual']) && isset($data['nova_senha']) && !empty($data['senha_atual']) && !empty($data['nova_senha'])) {
                // Verificar senha atual
                $checkStmt = $db->prepare("SELECT senha_hash FROM usuarios WHERE id = :user_id");
                $checkStmt->bindParam(':user_id', $userId);
                $checkStmt->execute();
                $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!password_verify($data['senha_atual'], $user['senha_hash'])) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'Senha atual incorreta.'];
                }
                
                // Validar nova senha
                if (strlen($data['nova_senha']) < PASSWORD_MIN_LENGTH) {
                    $db->rollBack();
                    return ['status' => false, 'message' => 'A nova senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres.'];
                }
                
                // Atualizar senha
                $senha_hash = password_hash($data['nova_senha'], PASSWORD_DEFAULT);
                $updatePassStmt = $db->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :user_id");
                $updatePassStmt->bindParam(':senha_hash', $senha_hash);
                $updatePassStmt->bindParam(':user_id', $userId);
                $updatePassStmt->execute();
            }
            
            // Atualizar/inserir endereço se fornecido
            if (isset($data['endereco']) && !empty($data['endereco'])) {
                // Verificar se já existe endereço
                $checkAddrStmt = $db->prepare("SELECT id FROM usuarios_endereco WHERE usuario_id = :user_id LIMIT 1");
                $checkAddrStmt->bindParam(':user_id', $userId);
                $checkAddrStmt->execute();
                
                if ($checkAddrStmt->rowCount() > 0) {
                    // Atualizar endereço existente
                    $addrId = $checkAddrStmt->fetch(PDO::FETCH_ASSOC)['id'];
                    $updateAddrStmt = $db->prepare("
                        UPDATE usuarios_endereco 
                        SET 
                            cep = :cep,
                            logradouro = :logradouro,
                            numero = :numero,
                            complemento = :complemento,
                            bairro = :bairro,
                            cidade = :cidade,
                            estado = :estado,
                            principal = :principal
                        WHERE id = :id
                    ");
                    $updateAddrStmt->bindParam(':id', $addrId);
                } else {
                    // Inserir novo endereço
                    $updateAddrStmt = $db->prepare("
                        INSERT INTO usuarios_endereco 
                        (usuario_id, cep, logradouro, numero, complemento, bairro, cidade, estado, principal)
                        VALUES
                        (:user_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado, :principal)
                    ");
                    $updateAddrStmt->bindParam(':user_id', $userId);
                }
                
                // Bind dos parâmetros comuns
                $updateAddrStmt->bindParam(':cep', $data['endereco']['cep']);
                $updateAddrStmt->bindParam(':logradouro', $data['endereco']['logradouro']);
                $updateAddrStmt->bindParam(':numero', $data['endereco']['numero']);
                $updateAddrStmt->bindParam(':complemento', $data['endereco']['complemento'] ?? '');
                $updateAddrStmt->bindParam(':bairro', $data['endereco']['bairro']);
                $updateAddrStmt->bindParam(':cidade', $data['endereco']['cidade']);
                $updateAddrStmt->bindParam(':estado', $data['endereco']['estado']);
                $principal = isset($data['endereco']['principal']) ? $data['endereco']['principal'] : 1;
                $updateAddrStmt->bindParam(':principal', $principal);
                $updateAddrStmt->execute();
            }
            
            // Atualizar/inserir contato se fornecido
            if (isset($data['contato']) && !empty($data['contato'])) {
                // Verificar se já existe contato
                $checkContactStmt = $db->prepare("SELECT id FROM usuarios_contato WHERE usuario_id = :user_id LIMIT 1");
                $checkContactStmt->bindParam(':user_id', $userId);
                $checkContactStmt->execute();
                
                if ($checkContactStmt->rowCount() > 0) {
                    // Atualizar contato existente
                    $contactId = $checkContactStmt->fetch(PDO::FETCH_ASSOC)['id'];
                    $updateContactStmt = $db->prepare("
                        UPDATE usuarios_contato 
                        SET 
                            telefone = :telefone,
                            celular = :celular,
                            email_alternativo = :email_alternativo
                        WHERE id = :id
                    ");
                    $updateContactStmt->bindParam(':id', $contactId);
                } else {
                    // Inserir novo contato
                    $updateContactStmt = $db->prepare("
                        INSERT INTO usuarios_contato 
                        (usuario_id, telefone, celular, email_alternativo)
                        VALUES
                        (:user_id, :telefone, :celular, :email_alternativo)
                    ");
                    $updateContactStmt->bindParam(':user_id', $userId);
                }
                
                // Bind dos parâmetros comuns
                $updateContactStmt->bindParam(':telefone', $data['contato']['telefone'] ?? '');
                $updateContactStmt->bindParam(':celular', $data['contato']['celular'] ?? '');
                $updateContactStmt->bindParam(':email_alternativo', $data['contato']['email_alternativo'] ?? '');
                $updateContactStmt->execute();
            }
            
            // Confirmar transação
            $db->commit();
            
            return ['status' => true, 'message' => 'Perfil atualizado com sucesso.'];
            
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao atualizar perfil: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao atualizar perfil. Tente novamente.'];
        }
    }
    
    /**
     * Registra uma nova transação de cashback
     * 
     * @param array $data Dados da transação
     * @return array Resultado da operação
     */
    public static function registerTransaction($data) {
        try {
            // Validar dados obrigatórios
            $requiredFields = ['usuario_id', 'loja_id', 'valor_total', 'codigo_transacao'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['status' => false, 'message' => 'Dados da transação incompletos. Campo faltante: ' . $field];
                }
            }
            
            // Verificar se é um cliente válido
            if (!self::validateClient($data['usuario_id'])) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe e está aprovada
            $storeStmt = $db->prepare("SELECT * FROM lojas WHERE id = :loja_id AND status = :status");
            $storeStmt->bindParam(':loja_id', $data['loja_id']);
            $status = STORE_APPROVED;
            $storeStmt->bindParam(':status', $status);
            $storeStmt->execute();
            $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não aprovada.'];
            }
            
            // Verificar se o valor da transação é válido
            if ($data['valor_total'] < MIN_TRANSACTION_VALUE) {
                return ['status' => false, 'message' => 'Valor mínimo para transação é R$ ' . number_format(MIN_TRANSACTION_VALUE, 2, ',', '.')];
            }
            
            // Verificar se já existe uma transação com o mesmo código
            $checkStmt = $db->prepare("
                SELECT id FROM transacoes_cashback 
                WHERE codigo_transacao = :codigo_transacao AND loja_id = :loja_id
            ");
            $checkStmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
            $checkStmt->bindParam(':loja_id', $data['loja_id']);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                return ['status' => false, 'message' => 'Já existe uma transação com este código.'];
            }
            
            // Obter configurações de cashback
            $configStmt = $db->prepare("SELECT * FROM configuracoes_cashback ORDER BY id DESC LIMIT 1");
            $configStmt->execute();
            $config = $configStmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular valores de cashback
            $porcentagemTotal = isset($config['porcentagem_total']) ? $config['porcentagem_total'] : DEFAULT_CASHBACK_TOTAL;
            $porcentagemCliente = isset($config['porcentagem_cliente']) ? $config['porcentagem_cliente'] : DEFAULT_CASHBACK_CLIENT;
            $porcentagemAdmin = isset($config['porcentagem_admin']) ? $config['porcentagem_admin'] : DEFAULT_CASHBACK_ADMIN;
            $porcentagemLoja = isset($config['porcentagem_loja']) ? $config['porcentagem_loja'] : DEFAULT_CASHBACK_STORE;
            
            // Verificar se a loja tem porcentagem específica
            if (isset($store['porcentagem_cashback']) && $store['porcentagem_cashback'] > 0) {
                $porcentagemTotal = $store['porcentagem_cashback'];
                // Ajustar proporcionalmente
                $fator = $porcentagemTotal / DEFAULT_CASHBACK_TOTAL;
                $porcentagemCliente = DEFAULT_CASHBACK_CLIENT * $fator;
                $porcentagemAdmin = DEFAULT_CASHBACK_ADMIN * $fator;
                $porcentagemLoja = DEFAULT_CASHBACK_STORE * $fator;
            }
            
            // Calcular valores
            $valorCashbackTotal = ($data['valor_total'] * $porcentagemTotal) / 100;
            $valorCashbackCliente = ($data['valor_total'] * $porcentagemCliente) / 100;
            $valorCashbackAdmin = ($data['valor_total'] * $porcentagemAdmin) / 100;
            $valorCashbackLoja = ($data['valor_total'] * $porcentagemLoja) / 100;
            
            // Iniciar transação
            $db->beginTransaction();
            
            // Registrar transação principal
            $stmt = $db->prepare("
                INSERT INTO transacoes_cashback (
                    usuario_id, loja_id, valor_total, valor_cashback,
                    codigo_transacao, data_transacao, status, descricao
                ) VALUES (
                    :usuario_id, :loja_id, :valor_total, :valor_cashback,
                    :codigo_transacao, NOW(), :status, :descricao
                )
            ");
            
            $stmt->bindParam(':usuario_id', $data['usuario_id']);
            $stmt->bindParam(':loja_id', $data['loja_id']);
            $stmt->bindParam(':valor_total', $data['valor_total']);
            $stmt->bindParam(':valor_cashback', $valorCashbackCliente);
            $stmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
            $status = TRANSACTION_APPROVED; // Ou TRANSACTION_PENDING dependendo da lógica de negócio
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':descricao', $data['descricao'] ?? 'Compra na ' . $store['nome_fantasia']);
            $stmt->execute();
            
            $transactionId = $db->lastInsertId();
            
            // Registrar transação para o administrador (comissão admin)
            if ($valorCashbackAdmin > 0) {
                $adminStmt = $db->prepare("
                    INSERT INTO transacoes_comissao (
                        tipo_usuario, usuario_id, loja_id, transacao_id,
                        valor_total, valor_comissao, data_transacao, status
                    ) VALUES (
                        :tipo_usuario, :usuario_id, :loja_id, :transacao_id,
                        :valor_total, :valor_comissao, NOW(), :status
                    )
                ");
                
                $tipoAdmin = USER_TYPE_ADMIN;
                $adminStmt->bindParam(':tipo_usuario', $tipoAdmin);
                $adminId = 1; // Administrador padrão (ajustar conforme necessário)
                $adminStmt->bindParam(':usuario_id', $adminId);
                $adminStmt->bindParam(':loja_id', $data['loja_id']);
                $adminStmt->bindParam(':transacao_id', $transactionId);
                $adminStmt->bindParam(':valor_total', $data['valor_total']);
                $adminStmt->bindParam(':valor_comissao', $valorCashbackAdmin);
                $adminStmt->bindParam(':status', $status);
                $adminStmt->execute();
            }
            
            // Registrar transação para a loja (comissão loja)
            if ($valorCashbackLoja > 0) {
                $storeStmt = $db->prepare("
                    INSERT INTO transacoes_comissao (
                        tipo_usuario, usuario_id, loja_id, transacao_id,
                        valor_total, valor_comissao, data_transacao, status
                    ) VALUES (
                        :tipo_usuario, :usuario_id, :loja_id, :transacao_id,
                        :valor_total, :valor_comissao, NOW(), :status
                    )
                ");
                
                $tipoLoja = USER_TYPE_STORE;
                $storeStmt->bindParam(':tipo_usuario', $tipoLoja);
                $storeUserId = $store['usuario_id'] ?? $store['id']; // ID do usuário da loja ou da própria loja
                $storeStmt->bindParam(':usuario_id', $storeUserId);
                $storeStmt->bindParam(':loja_id', $data['loja_id']);
                $storeStmt->bindParam(':transacao_id', $transactionId);
                $storeStmt->bindParam(':valor_total', $data['valor_total']);
                $storeStmt->bindParam(':valor_comissao', $valorCashbackLoja);
                $storeStmt->bindParam(':status', $status);
                $storeStmt->execute();
            }
            
            // Enviar notificação ao cliente
            self::notifyClient($data['usuario_id'], 'Nova transação de cashback', 'Você recebeu R$ ' . number_format($valorCashbackCliente, 2, ',', '.') . ' de cashback na loja ' . $store['nome_fantasia']);
            
            // Enviar email de confirmação ao cliente
            $userStmt = $db->prepare("SELECT nome, email FROM usuarios WHERE id = :user_id");
            $userStmt->bindParam(':user_id', $data['usuario_id']);
            $userStmt->execute();
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $transactionData = [
                    'loja' => $store['nome_fantasia'],
                    'valor_total' => $data['valor_total'],
                    'valor_cashback' => $valorCashbackCliente,
                    'data_transacao' => date('Y-m-d H:i:s')
                ];
                
                Email::sendTransactionConfirmation($user['email'], $user['nome'], $transactionData);
            }
            
            // Confirmar transação
            $db->commit();
            
            return [
                'status' => true, 
                'message' => 'Transação registrada com sucesso.',
                'data' => [
                    'transaction_id' => $transactionId,
                    'cashback_value' => $valorCashbackCliente
                ]
            ];
            
        } catch (PDOException $e) {
            // Reverter transação em caso de erro
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('Erro ao registrar transação: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao registrar transação. Tente novamente.'];
        }
    }
    
    /**
     * Obtém detalhes de uma transação específica
     * 
     * @param int $userId ID do cliente
     * @param int $transactionId ID da transação
     * @return array Dados da transação
     */
    public static function getTransactionDetails($userId, $transactionId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Obter detalhes da transação
            $stmt = $db->prepare("
                SELECT t.*, l.nome_fantasia as loja_nome, l.logo as loja_logo, l.categoria as loja_categoria
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                WHERE t.id = :transaction_id AND t.usuario_id = :user_id
            ");
            $stmt->bindParam(':transaction_id', $transactionId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$transaction) {
                return ['status' => false, 'message' => 'Transação não encontrada ou não pertence a este usuário.'];
            }
            
            // Obter histórico de status, se existir
            $historyStmt = $db->prepare("
                SELECT *
                FROM transacoes_status_historico
                WHERE transacao_id = :transaction_id
                ORDER BY data_alteracao DESC
            ");
            $historyStmt->bindParam(':transaction_id', $transactionId);
            $historyStmt->execute();
            $statusHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'transacao' => $transaction,
                    'historico_status' => $statusHistory
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter detalhes da transação: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar detalhes da transação. Tente novamente.'];
        }
    }
    
    /**
     * Gera relatório de cashback para o cliente
     * 
     * @param int $userId ID do cliente
     * @param array $filters Filtros para o relatório
     * @return array Dados do relatório
     */
    public static function generateCashbackReport($userId, $filters = []) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Preparar condições da consulta
            $conditions = "WHERE t.usuario_id = :user_id";
            $params = [':user_id' => $userId];
            
            // Aplicar filtros de data
            if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
                $conditions .= " AND t.data_transacao >= :data_inicio";
                $params[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
            }
            
            if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
                $conditions .= " AND t.data_transacao <= :data_fim";
                $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
            }
            
            // Estatísticas gerais
            $statsQuery = "
                SELECT 
                    COUNT(*) as total_transacoes,
                    SUM(valor_total) as total_compras,
                    SUM(valor_cashback) as total_cashback,
                    AVG(valor_cashback) as media_cashback
                FROM transacoes_cashback t
                $conditions
                AND t.status = :status
            ";
            
            $statsStmt = $db->prepare($statsQuery);
            foreach ($params as $param => $value) {
                $statsStmt->bindValue($param, $value);
            }
            $status = TRANSACTION_APPROVED;
            $statsStmt->bindValue(':status', $status);
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Cashback por loja
            $storesQuery = "
                SELECT 
                    l.id, l.nome_fantasia, l.categoria,
                    COUNT(t.id) as total_transacoes,
                    SUM(t.valor_total) as total_compras,
                    SUM(t.valor_cashback) as total_cashback
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                $conditions
                AND t.status = :status
                GROUP BY l.id
                ORDER BY total_cashback DESC
            ";
            
            $storesStmt = $db->prepare($storesQuery);
            foreach ($params as $param => $value) {
                $storesStmt->bindValue($param, $value);
            }
            $storesStmt->bindValue(':status', $status);
            $storesStmt->execute();
            $storesData = $storesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cashback por mês
            $monthlyQuery = "
                SELECT 
                    DATE_FORMAT(t.data_transacao, '%Y-%m') as mes,
                    COUNT(t.id) as total_transacoes,
                    SUM(t.valor_total) as total_compras,
                    SUM(t.valor_cashback) as total_cashback
                FROM transacoes_cashback t
                $conditions
                AND t.status = :status
                GROUP BY DATE_FORMAT(t.data_transacao, '%Y-%m')
                ORDER BY mes DESC
            ";
            
            $monthlyStmt = $db->prepare($monthlyQuery);
            foreach ($params as $param => $value) {
                $monthlyStmt->bindValue($param, $value);
            }
            $monthlyStmt->bindValue(':status', $status);
            $monthlyStmt->execute();
            $monthlyData = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cashback por categoria
            $categoryQuery = "
                SELECT 
                    l.categoria,
                    COUNT(t.id) as total_transacoes,
                    SUM(t.valor_total) as total_compras,
                    SUM(t.valor_cashback) as total_cashback
                FROM transacoes_cashback t
                JOIN lojas l ON t.loja_id = l.id
                $conditions
                AND t.status = :status
                GROUP BY l.categoria
                ORDER BY total_cashback DESC
            ";
            
            $categoryStmt = $db->prepare($categoryQuery);
            foreach ($params as $param => $value) {
                $categoryStmt->bindValue($param, $value);
            }
            $categoryStmt->bindValue(':status', $status);
            $categoryStmt->execute();
            $categoryData = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => [
                    'estatisticas' => $statistics,
                    'por_loja' => $storesData,
                    'por_mes' => $monthlyData,
                    'por_categoria' => $categoryData
                ]
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao gerar relatório: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao gerar relatório. Tente novamente.'];
        }
    }
    
    /**
     * Marca uma loja como favorita
     * 
     * @param int $userId ID do cliente
     * @param int $storeId ID da loja
     * @param bool $favorite true para favoritar, false para desfavoritar
     * @return array Resultado da operação
     */
    public static function toggleFavoriteStore($userId, $storeId, $favorite = true) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a loja existe e está aprovada
            $storeStmt = $db->prepare("SELECT id FROM lojas WHERE id = :store_id AND status = :status");
            $storeStmt->bindParam(':store_id', $storeId);
            $status = STORE_APPROVED;
            $storeStmt->bindParam(':status', $status);
            $storeStmt->execute();
            
            if ($storeStmt->rowCount() == 0) {
                return ['status' => false, 'message' => 'Loja não encontrada ou não aprovada.'];
            }
            
            // Verificar se a tabela de favoritos existe, se não, criar
            self::createFavoritesTableIfNotExists($db);
            
            // Verificar se já está favoritada
            $checkStmt = $db->prepare("
                SELECT id FROM lojas_favoritas
                WHERE usuario_id = :user_id AND loja_id = :store_id
            ");
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':store_id', $storeId);
            $checkStmt->execute();
            $isFavorite = $checkStmt->rowCount() > 0;
            
            if ($favorite && !$isFavorite) {
                // Adicionar aos favoritos
                $addStmt = $db->prepare("
                    INSERT INTO lojas_favoritas (usuario_id, loja_id, data_criacao)
                    VALUES (:user_id, :store_id, NOW())
                ");
                $addStmt->bindParam(':user_id', $userId);
                $addStmt->bindParam(':store_id', $storeId);
                $addStmt->execute();
                
                return ['status' => true, 'message' => 'Loja adicionada aos favoritos.'];
            } else if (!$favorite && $isFavorite) {
                // Remover dos favoritos
                $removeStmt = $db->prepare("
                    DELETE FROM lojas_favoritas
                    WHERE usuario_id = :user_id AND loja_id = :store_id
                ");
                $removeStmt->bindParam(':user_id', $userId);
                $removeStmt->bindParam(':store_id', $storeId);
                $removeStmt->execute();
                
                return ['status' => true, 'message' => 'Loja removida dos favoritos.'];
            }
            
            return ['status' => true, 'message' => 'Nenhuma alteração necessária.'];
            
        } catch (PDOException $e) {
            error_log('Erro ao atualizar favoritos: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao atualizar favoritos. Tente novamente.'];
        }
    }
    
    /**
     * Obtém as lojas favoritas do cliente
     * 
     * @param int $userId ID do cliente
     * @return array Lista de lojas favoritas
     */
    public static function getFavoriteStores($userId) {
        try {
            // Verificar se é um cliente válido
            if (!self::validateClient($userId)) {
                return ['status' => false, 'message' => 'Cliente não encontrado ou inativo.'];
            }
            
            $db = Database::getConnection();
            
            // Verificar se a tabela de favoritos existe
            self::createFavoritesTableIfNotExists($db);
            
            // Obter lojas favoritas
            $stmt = $db->prepare("
                SELECT l.*, f.data_criacao as data_favoritado
                FROM lojas_favoritas f
                JOIN lojas l ON f.loja_id = l.id
                WHERE f.usuario_id = :user_id AND l.status = :status
                ORDER BY f.data_criacao DESC
            ");
            $stmt->bindParam(':user_id', $userId);
            $status = STORE_APPROVED;
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => true,
                'data' => $favorites
            ];
            
        } catch (PDOException $e) {
            error_log('Erro ao obter lojas favoritas: ' . $e->getMessage());
            return ['status' => false, 'message' => 'Erro ao carregar lojas favoritas. Tente novamente.'];
        }
    }
    
    /**
     * Valida se o usuário é um cliente ativo
     * 
     * @param int $userId ID do usuário
     * @return bool true se for cliente ativo, false caso contrário
     */
    private static function validateClient($userId) {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT id FROM usuarios
                WHERE id = :user_id AND tipo = :tipo AND status = :status
            ");
            $stmt->bindParam(':user_id', $userId);
            $tipo = USER_TYPE_CLIENT;
            $stmt->bindParam(':tipo', $tipo);
            $status = USER_ACTIVE;
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Erro ao validar cliente: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém notificações para o cliente
     * 
     * @param int $userId ID do cliente
     * @param int $limit Limite de notificações
     * @return array Lista de notificações
     */
    private static function getClientNotifications($userId, $limit = 5) {
        try {
            $db = Database::getConnection();
            
            // Verificar se a tabela de notificações existe
            self::createNotificationsTableIfNotExists($db);
            
            // Obter notificações
            $stmt = $db->prepare("
                SELECT *
                FROM notificacoes
                WHERE usuario_id = :user_id
                ORDER BY data_criacao DESC
                LIMIT :limit
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Erro ao obter notificações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Envia uma notificação para o cliente
     * 
     * @param int $userId ID do cliente
     * @param string $titulo Título da notificação
     * @param string $mensagem Mensagem da notificação
     * @param string $tipo Tipo da notificação (info, success, warning, error)
     * @return bool Resultado da operação
     */
    private static function notifyClient($userId, $titulo, $mensagem, $tipo = 'info') {
        try {
            $db = Database::getConnection();
            
            // Verificar se a tabela de notificações existe
            self::createNotificationsTableIfNotExists($db);
            
            // Inserir notificação
            $stmt = $db->prepare("
                INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo, data_criacao, lida)
                VALUES (:user_id, :titulo, :mensagem, :tipo, NOW(), 0)
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':mensagem', $mensagem);
            $stmt->bindParam(':tipo', $tipo);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('Erro ao enviar notificação: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cria a tabela de favoritos se não existir
     * 
     * @param PDO $db Conexão com o banco de dados
     * @return void
     */
    private static function createFavoritesTableIfNotExists($db) {
        try {
            // Verificar se a tabela existe
            $stmt = $db->prepare("SHOW TABLES LIKE 'lojas_favoritas'");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Criar a tabela
                $createTable = "CREATE TABLE lojas_favoritas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    loja_id INT NOT NULL,
                    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_favorite (usuario_id, loja_id),
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
                    FOREIGN KEY (loja_id) REFERENCES lojas(id)
                )";
                
                $db->exec($createTable);
            }
        } catch (PDOException $e) {
            error_log('Erro ao criar tabela de favoritos: ' . $e->getMessage());
        }
    }
    
    /**
     * Cria a tabela de notificações se não existir
     * 
     * @param PDO $db Conexão com o banco de dados
     * @return void
     */
    private static function createNotificationsTableIfNotExists($db) {
        try {
            // Verificar se a tabela existe
            $stmt = $db->prepare("SHOW TABLES LIKE 'notificacoes'");
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // Criar a tabela
                $createTable = "CREATE TABLE notificacoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    titulo VARCHAR(100) NOT NULL,
                    mensagem TEXT NOT NULL,
                    tipo ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
                    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    lida TINYINT(1) DEFAULT 0,
                    data_leitura TIMESTAMP NULL,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                )";
                
                $db->exec($createTable);
            }
        } catch (PDOException $e) {
            error_log('Erro ao criar tabela de notificações: ' . $e->getMessage());
        }
    }
}

// Processar requisições diretas de acesso ao controlador
if (basename($_SERVER['PHP_SELF']) === 'ClientController.php') {
    // Verificar se o usuário está autenticado
    if (!AuthController::isAuthenticated()) {
        header('Location: ../views/auth/login.php?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
        exit;
    }
    
    // Verificar se é um cliente
    if (AuthController::isAdmin() || AuthController::isStore()) {
        header('Location: ../views/auth/login.php?error=' . urlencode('Acesso restrito a clientes.'));
        exit;
    }
    
    $userId = AuthController::getCurrentUserId();
    $action = $_REQUEST['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            $result = ClientController::getDashboardData($userId);
            echo json_encode($result);
            break;
            
        case 'statement':
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = ClientController::getStatement($userId, $filters, $page);
            echo json_encode($result);
            break;
            
        case 'profile':
            $result = ClientController::getProfileData($userId);
            echo json_encode($result);
            break;
            
        case 'update_profile':
            $data = $_POST;
            $result = ClientController::updateProfile($userId, $data);
            echo json_encode($result);
            break;
            
        case 'stores':
            $filters = $_POST['filters'] ?? [];
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $result = ClientController::getPartnerStores($userId, $filters, $page);
            echo json_encode($result);
            break;
            
        case 'favorite_store':
            $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
            $favorite = isset($_POST['favorite']) ? (bool)$_POST['favorite'] : true;
            $result = ClientController::toggleFavoriteStore($userId, $storeId, $favorite);
            echo json_encode($result);
            break;
            
        case 'favorites':
            $result = ClientController::getFavoriteStores($userId);
            echo json_encode($result);
            break;
            
        case 'transaction':
            $transactionId = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
            $result = ClientController::getTransactionDetails($userId, $transactionId);
            echo json_encode($result);
            break;
            
        case 'report':
            $filters = $_POST['filters'] ?? [];
            $result = ClientController::generateCashbackReport($userId, $filters);
            echo json_encode($result);
            break;
            
        default:
            // Acesso inválido ao controlador
            header('Location: ../views/client/dashboard.php');
            exit;
    }
}
?>