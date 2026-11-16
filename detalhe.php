<?php
require_once 'conexao.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM estabelecimentos WHERE id = ?");
$stmt->execute([$id]);
$estabelecimento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estabelecimento) {
    echo "Estabelecimento não encontrado.";
    exit;
}

// Processa fotos da galeria (cardápios / ambiente)
$galeria = [];
if (!empty($estabelecimento['imagem_url'])) {
    $galeria[] = $estabelecimento['imagem_url'];
}
if (!empty($estabelecimento['galeria_fotos'])) {
    $extras = explode(',', $estabelecimento['galeria_fotos']);
    foreach ($extras as $img) {
        $img = trim($img);
        if (!empty($img) && !in_array($img, $galeria)) {
            $galeria[] = $img;
        }
    }
}

// Processa envio de avaliação
$mensagem_aviso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_avaliacao'])) {
    $nome_usuario = trim($_POST['nome_usuario']);
    $nota = intval($_POST['nota']);
    $comentario = trim($_POST['comentario']);

    if (!empty($nome_usuario) && $nota >= 1 && $nota <= 5) {
        $stmt_av = $pdo->prepare("INSERT INTO avaliacoes (estabelecimento_id, nome_usuario, nota, comentario) VALUES (?, ?, ?, ?)");
        $stmt_av->execute([$id, $nome_usuario, $nota, $comentario]);
        $mensagem_aviso = "Avaliação enviada com sucesso!";
    } else {
        $mensagem_aviso = "Preencha seu nome e escolha uma nota.";
    }
}

$stmt_avaliacoes = $pdo->prepare("SELECT * FROM avaliacoes WHERE estabelecimento_id = ? ORDER BY id DESC");
$stmt_avaliacoes->execute([$id]);
$avaliacoes = $stmt_avaliacoes->fetchAll(PDO::FETCH_ASSOC);

$total_notas = count($avaliacoes);
$media_notas = 0;
if ($total_notas > 0) {
    $soma_notas = array_sum(array_column($avaliacoes, 'nota'));
    $media_notas = round($soma_notas / $total_notas, 1);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($estabelecimento['nome']) ?> — Conecta Corrente</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .hide-scrollbar { scrollbar-width: none; }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
  <div class="mx-auto min-h-screen max-w-[430px] bg-white shadow-lg relative pb-16">
    
    <!-- Botão Voltar -->
    <div class="p-4 bg-white sticky top-0 z-30 flex items-center justify-between border-b border-gray-100">
      <a href="index.php" class="w-9 h-9 rounded-full bg-gray-100 text-gray-800 flex items-center justify-center font-bold text-sm">←</a>
      <span class="text-xs font-bold text-green-700 bg-green-50 px-3 py-1 rounded-full"><?= htmlspecialchars($estabelecimento['categoria']) ?></span>
    </div>

    <!-- Carrossel de Fotos & Cardápios (Inspirado na aba de Fotos do Google Maps) -->
    <section class="mt-2">
      <div class="px-5 mb-2 flex justify-between items-center">
        <h2 class="font-extrabold text-sm text-gray-700 uppercase">📷 Fotos & Cardápios (<?= count($galeria) ?>)</h2>
        <span class="text-[10px] text-gray-400">Deslize para o lado →</span>
      </div>
      
      <div class="flex gap-3 overflow-x-auto px-5 hide-scrollbar">
        <?php foreach ($galeria as $foto): ?>
          <a href="<?= htmlspecialchars($foto) ?>" target="_blank" class="shrink-0 w-64 h-44 rounded-2xl overflow-hidden bg-gray-100 border border-gray-100 block">
            <img src="<?= htmlspecialchars($foto) ?>" class="w-full h-full object-cover hover:scale-105 transition" alt="Foto / Cardápio" />
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <div class="px-5 pt-4">
      <h1 class="text-2xl font-extrabold"><?= htmlspecialchars($estabelecimento['nome']) ?></h1>
      <p class="text-xs text-gray-500 mt-1">📍 <?= htmlspecialchars($estabelecimento['endereco']) ?></p>

      <!-- Botão do WhatsApp -->
      <?php if (!empty($estabelecimento['whatsapp'])): ?>
        <a href="https://wa.me/<?= htmlspecialchars($estabelecimento['whatsapp']) ?>?text=Olá!%20Encontrei%20seu%20estabelecimento%20no%20Conecta%20Corrente." target="_blank" class="mt-4 w-full h-12 rounded-xl bg-[#25D366] text-white font-extrabold flex items-center justify-center gap-2 shadow-sm text-sm">
          💬 Conversar no WhatsApp
        </a>
      <?php endif; ?>

      <section class="mt-6">
        <h2 class="font-extrabold text-md">Sobre o local</h2>
        <p class="text-xs leading-5 text-gray-600 mt-2">
          <?= nl2br(htmlspecialchars($estabelecimento['descricao'] ?? 'Sem descrição.')) ?>
        </p>

        <?php if (!empty($estabelecimento['horario_funcionamento'])): ?>
          <div class="mt-3 rounded-xl bg-gray-50 p-3">
            <h3 class="font-bold text-xs">🕒 Horários</h3>
            <p class="text-xs text-gray-500 mt-0.5"><?= htmlspecialchars($estabelecimento['horario_funcionamento']) ?></p>
          </div>
        <?php endif; ?>
      </section>

      <!-- Avaliações -->
      <section class="mt-6 border-t border-gray-100 pt-5">
        <div class="flex items-center justify-between">
          <h2 class="font-extrabold text-md">Avaliações</h2>
          <span class="text-xs font-bold text-yellow-600">★ <?= $media_notas ?> (<?= $total_notas ?>)</span>
        </div>

        <?php if ($mensagem_aviso): ?>
          <div class="mt-2 p-2.5 rounded-lg bg-green-50 text-green-800 text-xs font-semibold">
            <?= htmlspecialchars($mensagem_aviso) ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="mt-3 rounded-xl border border-gray-100 p-3 bg-gray-50/50 space-y-2">
          <input type="text" name="nome_usuario" required class="w-full h-9 rounded-lg border border-gray-200 px-3 text-xs outline-none bg-white" placeholder="Seu nome" />
          <select name="nota" required class="w-full h-9 rounded-lg border border-gray-200 px-3 text-xs outline-none bg-white">
            <option value="5">★★★★★ (5 estrelas)</option>
            <option value="4">★★★★☆ (4 estrelas)</option>
            <option value="3">★★★☆☆ (3 estrelas)</option>            <option value="2">★★☆☆☆ (2 estrelas)</option>
            <option value="1">★☆☆☆☆ (1 estrela)</option>
          </select>
          <textarea name="comentario" rows="2" class="w-full rounded-lg border border-gray-200 p-2 text-xs outline-none bg-white resize-none" placeholder="Escreva seu comentário..."></textarea>
          <button type="submit" name="enviar_avaliacao" class="w-full h-9 rounded-lg bg-green-700 text-white font-bold text-xs">Enviar Avaliação</button>
        </form>

        <div class="mt-4 space-y-2">
          <?php foreach ($avaliacoes as $av): ?>
            <div class="border-b border-gray-100 pb-2">
              <div class="flex justify-between items-center">
                <span class="font-bold text-xs"><?= htmlspecialchars($av['nome_usuario']) ?></span>
                <span class="text-yellow-500 text-xs"><?= str_repeat('★', $av['nota']) ?></span>
              </div>
              <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($av['comentario']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

    </div>
  </div>
</body>
</html>