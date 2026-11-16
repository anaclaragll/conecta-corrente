<?php
require_once 'conexao.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    echo "Evento não encontrado.";
    exit;
}

// Galeria de Fotos do Evento
$galeria = [];
if (!empty($evento['imagem_url'])) {
    $galeria[] = $evento['imagem_url'];
}
if (!empty($evento['galeria_fotos'])) {
    $extras = explode(',', $evento['galeria_fotos']);
    foreach ($extras as $img) {
        $img = trim($img);
        if (!empty($img) && !in_array($img, $galeria)) {
            $galeria[] = $img;
        }
    }
}

$lat = !empty($evento['latitude']) ? floatval($evento['latitude']) : -10.4431;
$lng = !empty($evento['longitude']) ? floatval($evento['longitude']) : -45.1611;
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($evento['titulo']) ?> — Conecta Corrente</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    .hide-scrollbar { scrollbar-width: none; }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    #mapa-evento {
      height: 220px;
      width: 100%;
      border-radius: 1rem;
      z-index: 10;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
  <div class="mx-auto min-h-screen max-w-[430px] bg-white shadow-lg relative pb-12">
    
    <!-- Topo -->
    <div class="p-4 bg-white sticky top-0 z-30 flex items-center justify-between border-b border-gray-100">
      <a href="index.php" class="w-9 h-9 rounded-full bg-gray-100 text-gray-800 flex items-center justify-center font-bold text-sm">←</a>
      <span class="text-xs font-bold text-green-700 bg-green-50 px-3 py-1 rounded-full">🎉 Evento Local</span>
    </div>

    <!-- Galeria de Fotos do Evento -->
    <section class="mt-2">
      <div class="px-5 mb-2 flex justify-between items-center">
        <h2 class="font-extrabold text-xs text-gray-700 uppercase">📷 Galeria de Fotos (<?= count($galeria) ?>)</h2>
        <span class="text-[10px] text-gray-400">Deslize para ver →</span>
      </div>
      
      <div class="flex gap-3 overflow-x-auto px-5 hide-scrollbar">
        <?php if (empty($galeria)): ?>
          <div class="w-full h-40 rounded-2xl bg-gray-100 flex items-center justify-center text-xs text-gray-400">Sem fotos cadastradas</div>
        <?php else: ?>
          <?php foreach ($galeria as $foto): ?>
            <a href="<?= htmlspecialchars($foto) ?>" target="_blank" class="shrink-0 w-64 h-44 rounded-2xl overflow-hidden bg-gray-100 border border-gray-100 block">
              <img src="<?= htmlspecialchars($foto) ?>" class="w-full h-full object-cover" alt="Foto do Evento" />
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- Detalhes e Descrição -->
    <div class="px-5 pt-4 space-y-5">
      <div>
        <div class="text-xs font-bold text-green-700">📅 <?= date('d/m/Y', strtotime($evento['data_evento'])) ?></div>
        <h1 class="text-2xl font-extrabold mt-1"><?= htmlspecialchars($evento['titulo']) ?></h1>
        <p class="text-xs text-gray-500 mt-1">📍 Local: <?= htmlspecialchars($evento['local']) ?></p>
      </div>

      <section class="bg-gray-50 p-4 rounded-2xl">
        <h2 class="font-extrabold text-sm mb-1 text-gray-800">Sobre o Evento</h2>
        <p class="text-xs leading-5 text-gray-600">
          <?= nl2br(htmlspecialchars($evento['descricao'] ?? 'Sem descrição detalhada cadastrada.')) ?>
        </p>
      </section>

      <!-- Mapa do Local do Evento -->
      <section>
        <h2 class="font-extrabold text-sm mb-2 text-gray-800">📍 Localização no Mapa</h2>
        <div id="mapa-evento" class="border border-gray-200 shadow-sm"></div>
      </section>
    </div>

  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const lat = <?= $lat ?>;
    const lng = <?= $lng ?>;

    const map = L.map('mapa-evento').setView([lat, lng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);

    L.marker([lat, lng])
      .addTo(map)
      .bindPopup('<strong style="font-size:12px; color:#15803d;"><?= addslashes(htmlspecialchars($evento['titulo'])) ?></strong><br><span style="font-size:10px; color:#666;"><?= addslashes(htmlspecialchars($evento['local'])) ?></span>')
      .openPopup();
  </script>
</body>
</html>