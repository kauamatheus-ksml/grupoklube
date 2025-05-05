<?php
// atualizar-contato.php
header('Content-Type: application/json; charset=UTF-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Método não permitido']);
  exit;
}

// Validar parâmetros
$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$observacao = filter_input(INPUT_POST, 'observacao', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

if (!$id || !$status) {
  echo json_encode(['success' => false, 'message' => 'ID e status são obrigatórios']);
  exit;
}

// Validar status
$statusValidos = ['pendente', 'em_atendimento', 'concluido', 'cancelado'];
if (!in_array($status, $statusValidos)) {
  echo json_encode(['success' => false, 'message' => 'Status inválido']);
  exit;
}

// Arquivo JSON
$file = __DIR__ . '/dados/contatos.json';

if (!file_exists($file)) {
  echo json_encode(['success' => false, 'message' => 'Arquivo de contatos não encontrado']);
  exit;
}

// Ler dados existentes
$jsonContent = file_get_contents($file);
$dados = json_decode($jsonContent, true);

if (!$dados || !isset($dados['contatos'])) {
  echo json_encode(['success' => false, 'message' => 'Formato de dados inválido']);
  exit;
}

// Encontrar o contato pelo ID
$contatoEncontrado = false;
foreach ($dados['contatos'] as &$contato) {
  if ($contato['id'] === $id) {
    // Atualizar status
    $contato['status'] = $status;
    
    // Gerar mensagem personalizada para WhatsApp
    $mensagemPersonalizada = '';
    switch($status) {
      case 'pendente':
        $mensagemPersonalizada = 'Recebemos sua solicitação e logo entraremos em contato.';
        break;
      case 'em_atendimento':
        $mensagemPersonalizada = 'Estamos dando andamento ao seu atendimento. Podemos falar mais sobre sua solicitação?';
        break;
      case 'concluido':
        $mensagemPersonalizada = 'Seu atendimento foi concluído. Agradecemos a confiança em nossos serviços!';
        break;
      case 'cancelado':
        $mensagemPersonalizada = 'Seu atendimento foi cancelado. Se precisar de algo mais, fique à vontade para entrar em contato.';
        break;
    }
    
    // Atualizar link do WhatsApp
    $mensagemBase = $dados['config']['mensagem_padrao_whatsapp'];
    $mensagemBase = str_replace('{nome}', $contato['nome'], $mensagemBase);
    $mensagemBase = str_replace('{id}', $contato['id'], $mensagemBase);
    $mensagemBase = str_replace('{mensagem_personalizada}', $mensagemPersonalizada, $mensagemBase);
    
    $contato['whatsapp_link'] = 'https://api.whatsapp.com/send?phone=' . $contato['telefone'] . '&text=' . urlencode($mensagemBase);
    
    // Adicionar observação se tiver
    if ($observacao) {
      // Em um sistema real, você armazenaria um histórico de observações
      // Para simplificar, apenas registramos a data da atualização
      $contato['ultima_atualizacao'] = date('c');
      
      // Se você quiser armazenar o histórico, pode fazer algo como:
      if (!isset($contato['observacoes'])) {
        $contato['observacoes'] = [];
      }
      
      $contato['observacoes'][] = [
        'data' => date('c'),
        'status' => $status,
        'texto' => $observacao
      ];
    }
    
    $contatoEncontrado = true;
    break;
  }
}

if (!$contatoEncontrado) {
  echo json_encode(['success' => false, 'message' => 'Contato não encontrado']);
  exit;
}

// Atualizar metadados
$dados['meta']['ultima_atualizacao'] = date('c');

// Salvar alterações
$success = file_put_contents($file, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($success) {
  echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
} else {
  echo json_encode(['success' => false, 'message' => 'Erro ao salvar as alterações']);
}
?>