<?php
session_start();

if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

require_once 'conexao.php';

$msg_est = '';
$msg_ev = '';

// Excluir estabelecimento
if (isset($_GET['excluir'])) {
    $id_excluir = intval($_GET['excluir']);
    $pdo->prepare("DELETE FROM avaliacoes WHERE estabelecimento_id = ?")->execute([$id_excluir]);
    $pdo->prepare("DELETE FROM estabelecimentos WHERE id = ?")->execute([$id_excluir]);
    header("Location: admin.php?removido=1");
    exit;
}

if (isset($_GET['removido'])) {
    $msg_est = "Estabelecimento removido com sucesso!";
}

// Cadastro de Estabelecimento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_estabelecimento'])) {
    $nome = $_POST['nome'] ?? '';
    $categoria = $_POST['categoria'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $imagem_url = $_POST['imagem_url'] ?? '';
    $galeria_fotos = $_POST['galeria_fotos'] ?? '';
    $horario = $_POST['horario_funcionamento'] ?? '';
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : '-10.4431';
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : '-45.1611';

    if (!empty($nome) && !empty($categoria) && !empty($whatsapp)) {
        $stmt = $pdo->prepare("INSERT INTO estabelecimentos (nome, categoria, descricao, endereco, whatsapp, imagem_url, galeria_fotos, horario_funcionamento, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $categoria, $descricao, $endereco, $whatsapp, $imagem_url, $galeria_fotos, $horario, $latitude, $longitude]);
        $msg_est = "Estabelecimento cadastrado com sucesso!";
    }
}

// Cadastro de Evento com Galeria e Coordenadas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_evento'])) {
    $titulo = $_POST['titulo'] ?? '';
    $local = $_POST['local'] ?? '';
    $data_evento = $_POST['data_evento'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $imagem_url = $_POST['imagem_url'] ?? '';
    $galeria_fotos = $_POST['galeria_fotos'] ?? '';
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : '-10.4431';
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : '-45.1611';

    if (!empty($titulo) && !empty($local) && !empty($data_evento)) {
        $stmt = $pdo->prepare("INSERT INTO eventos (titulo, local, data_evento, descricao, imagem_url, galeria_fotos, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titulo, $local, $data_evento, $descricao, $imagem_url, $galeria_fotos, $latitude, $longitude]);
        $msg_ev = "Evento cadastrado com sucesso!";
    }
}

$estabelecimentos = $pdo->query("SELECT id, nome, categoria FROM estabelecimentos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Painel Administrativo — Conecta Corrente</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans p-4 pb-20">
  <div class="mx-auto max-w-[480px] bg-white shadow-lg p-5 rounded-2xl space-y-6">
    <div class="flex items-center justify-between border-b pb-3">
      <div>
        <h1 class="text-lg font-extrabold text-green-700">Painel Administrativo</h1>
        <p class="text-[10px] text-gray-400">Gestão de Conteúdo</p>
      </div>
      <div class="flex gap-2 items-center">
        <a href="index.php" class="text-xs font-bold text-gray-600 bg-gray-100 px-3 py-1.5 rounded-lg">Ver Guia</a>
        <a href="admin.php?logout=1" class="text-xs font-bold text-red-600 bg-red-50 px-3 py-1.5 rounded-lg">Sair</a>
      </div>
    </div>

    <!-- 1. Cadastrar Estabelecimento -->
    <div class="border-t pt-4">
      <h2 class="font-extrabold text-sm mb-3">1. Cadastrar Estabelecimento</h2>
      <?php if ($msg_est): ?><p class="text-xs bg-green-50 text-green-800 p-2 rounded mb-2 font-bold"><?= $msg_est ?></p><?php endif; ?>
      <form method="POST" class="space-y-3 text-xs">
        <input type="text" name="nome" required placeholder="Nome do local *" class="w-full h-10 border rounded-lg px-3" />
        <select name="categoria" required class="w-full h-10 border rounded-lg px-3 bg-white">
          <option value="Alimentação">Alimentação</option>
          <option value="Hotelaria">Hotelaria</option>
          <option value="Serviços">Serviços</option>
          <option value="Artesanato">Artesanato</option>
          <option value="Ecoturismo">Ecoturismo</option>
        </select>
        <textarea name="descricao" placeholder="Descrição curta" class="w-full border rounded-lg p-2 resize-none"></textarea>
        <input type="text" name="endereco" required placeholder="Endereço (Bairro, Rua) *" class="w-full h-10 border rounded-lg px-3" />
        <input type="text" name="whatsapp" required placeholder="WhatsApp (Ex: 5589999999999) *" class="w-full h-10 border rounded-lg px-3" />
        <input type="url" name="imagem_url" placeholder="URL da Foto Capa" class="w-full h-10 border rounded-lg px-3" />
        <input type="text" name="galeria_fotos" placeholder="URLs fotos extras (separadas por vírgula)" class="w-full h-10 border rounded-lg px-3" />
        <div class="grid grid-cols-2 gap-2">
          <input type="text" name="latitude" placeholder="Latitude (-10.4431)" class="h-10 border rounded-lg px-3" />
          <input type="text" name="longitude" placeholder="Longitude (-45.1611)" class="h-10 border rounded-lg px-3" />
        </div>
        <button type="submit" name="cadastrar_estabelecimento" class="w-full h-10 bg-green-700 text-white font-bold rounded-lg hover:bg-green-800">Salvar Estabelecimento</button>
      </form>
    </div>

    <!-- 2. Gerenciar / Excluir -->
    <div class="border-t pt-4">
      <h2 class="font-extrabold text-sm mb-3">2. Gerenciar / Excluir Estabelecimentos</h2>
      <?php if (empty($estabelecimentos)): ?>
        <p class="text-xs text-gray-400">Nenhum local cadastrado.</p>
      <?php else: ?>
        <div class="space-y-2 max-h-48 overflow-y-auto">
          <?php foreach ($estabelecimentos as $est): ?>
            <div class="flex items-center justify-between bg-gray-50 p-2.5 rounded-lg border border-gray-100 text-xs">
              <div class="min-w-0 flex-1 pr-2">
                <span class="font-bold truncate block"><?= htmlspecialchars($est['nome']) ?></span>
                <span class="text-[10px] text-gray-500"><?= htmlspecialchars($est['categoria']) ?></span>
              </div>
              <a href="admin.php?excluir=<?= $est['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir?');" class="bg-red-50 text-red-600 font-bold px-3 py-1.5 rounded-md hover:bg-red-100 shrink-0">Excluir</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- 3. Divulgar Evento -->
    <div class="border-t pt-4">
      <h2 class="font-extrabold text-sm mb-3">3. Divulgar Evento Local</h2>
      <?php if ($msg_ev): ?><p class="text-xs bg-green-50 text-green-800 p-2 rounded mb-2 font-bold"><?= $msg_ev ?></p><?php endif; ?>
      <form method="POST" class="space-y-3 text-xs">
        <input type="text" name="titulo" required placeholder="Título do Evento *" class="w-full h-10 border rounded-lg px-3" />
        <input type="text" name="local" required placeholder="Local (Ex: Praça Central) *" class="w-full h-10 border rounded-lg px-3" />
        <input type="date" name="data_evento" required class="w-full h-10 border rounded-lg px-3 bg-white" />
        <textarea name="descricao" placeholder="Descrição completa do evento" class="w-full border rounded-lg p-2 resize-none"></textarea>
        <input type="url" name="imagem_url" placeholder="URL da Capa do Evento" class="w-full h-10 border rounded-lg px-3" />
        <input type="text" name="galeria_fotos" placeholder="URLs das fotos extras (separadas por vírgula)" class="w-full h-10 border rounded-lg px-3" />
        <div class="grid grid-cols-2 gap-2">
          <input type="text" name="latitude" placeholder="Latitude (-10.4431)" class="h-10 border rounded-lg px-3" />
          <input type="text" name="longitude" placeholder="Longitude (-45.1611)" class="h-10 border rounded-lg px-3" />
        </div>
        <button type="submit" name="cadastrar_evento" class="w-full h-10 bg-emerald-800 text-white font-bold rounded-lg hover:bg-emerald-900">Publicar Evento</button>
      </form>
    </div>

  </div>
</body>
</html>