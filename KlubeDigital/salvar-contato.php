<?php
header('Content-Type: application/json; charset=UTF-8');

// Apenas aceitar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Método não permitido']);
  exit;
}

// Validar e processar os campos
$nome = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$telefone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$clinica = filter_input(INPUT_POST, 'clinic', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$mensagem = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Validar campos obrigatórios
if (!$nome || !$email || !$telefone || !$clinica) {
  echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
  exit;
}

// Arquivo JSON
$file = __DIR__ . '/dados/contatos.json';

// Verificar se o diretório existe, senão criar
$dir = __DIR__ . '/dados';
if (!file_exists($dir)) {
  mkdir($dir, 0755, true);
}

// Inicializar ou ler dados existentes
$dados = [
  'contatos' => [],
  'meta' => [
    'total_registros' => 0,
    'ultima_atualizacao' => date('c'),
    'status_disponiveis' => ['pendente', 'em_atendimento', 'concluido', 'cancelado'],
    'origens_disponiveis' => ['formulario_site', 'telefone', 'email', 'indicacao', 'redes_sociais']
  ],
  'config' => [
    'mensagem_padrao_whatsapp' => 'Olá {nome}, referente ao seu atendimento {id} na Klube Digital. {mensagem_personalizada}',
    'tempo_resposta_maximo' => '24h',
    'notificacoes_email' => true,
    'notificacoes_sistema' => true
  ]
];

if (file_exists($file)) {
  $jsonContent = file_get_contents($file);
  $tempDados = json_decode($jsonContent, true);
  if ($tempDados) {
    $dados = $tempDados;
  }
}

// Formatar telefone (remover caracteres não numéricos)
$telefoneFormatado = preg_replace('/\D/', '', $telefone);

// Adicionar prefixo do Brasil se necessário
if (!preg_match('/^55/', $telefoneFormatado)) {
  $telefoneFormatado = '55' . $telefoneFormatado;
}

// Gerar ID único
$novoId = 'CON' . str_pad(count($dados['contatos']) + 1, 3, '0', STR_PAD_LEFT);

// Criar mensagem para WhatsApp
$mensagemWhatsApp = urlencode("Olá {$nome}, recebemos sua solicitação {$novoId} na Klube Digital. Como podemos ajudar com seu atendimento?");
$whatsappLink = "https://api.whatsapp.com/send?phone={$telefoneFormatado}&text={$mensagemWhatsApp}";

// Criar novo contato
$novoContato = [
  'id' => $novoId,
  'data' => date('c'),
  'nome' => $nome,
  'email' => $email,
  'telefone' => $telefoneFormatado,
  'clinica' => $clinica,
  'mensagem' => $mensagem,
  'status' => 'pendente',
  'origem' => 'formulario_site',
  'whatsapp_link' => $whatsappLink
];

// Adicionar ao array
$dados['contatos'][] = $novoContato;
$dados['meta']['total_registros'] = count($dados['contatos']);
$dados['meta']['ultima_atualizacao'] = date('c');

// Salvar no arquivo
$success = file_put_contents($file, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($success) {
  // Você pode descomentar isso se tiver configuração de e-mail no servidor
  // mail('contato@klubedigital.com.br', 'Novo contato recebido: ' . $novoId, "Nome: {$nome}\nEmail: {$email}\nTelefone: {$telefone}\nClínica: {$clinica}\nMensagem: {$mensagem}");
  
  echo json_encode([
    'success' => true, 
    'message' => 'Contato registrado com sucesso!',
    'id' => $novoId
  ]);
} else {
  echo json_encode(['success' => false, 'message' => 'Erro ao salvar os dados']);
}
?>