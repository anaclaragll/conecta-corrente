<?php
require_once 'conexao.php';

// Captura e limpa os filtros de busca e categoria
$categoria_filtro = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$busca = isset($_GET['q']) ? trim($_GET['q']) : '';

// Construção da consulta SQL dinâmica para busca
$sql = "SELECT * FROM estabelecimentos WHERE ativo = 1";
$params = [];

if (!empty($categoria_filtro)) {
    $sql .= " AND categoria = ?";
    $params[] = $categoria_filtro;
}

if (!empty($busca)) {
    $sql .= " AND (nome LIKE ? OR descricao LIKE ? OR endereco LIKE ? OR categoria LIKE ?)";
    $searchTerm = "%{$busca}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$estabelecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Busca próximos eventos
$stmt_ev = $pdo->query("SELECT * FROM eventos WHERE data_evento >= CURDATE() ORDER BY data_evento ASC LIMIT 5");
$eventos = $stmt_ev->fetchAll(PDO::FETCH_ASSOC);

// Formata pontos do mapa Leaflet
$pontos_mapa = [];
foreach ($estabelecimentos as $est) {
    $lat = !empty($est['latitude']) ? floatval($est['latitude']) : -10.4431;
    $lng = !empty($est['longitude']) ? floatval($est['longitude']) : -45.1611;
    
    $pontos_mapa[] = [
        'id' => $est['id'],
        'nome' => $est['nome'],
        'categoria' => $est['categoria'],
        'endereco' => $est['endereco'],
        'lat' => $lat,
        'lng' => $lng
    ];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Conecta Corrente — Guia Interativo & Eventos</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    .hide-scrollbar { scrollbar-width: none; }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    #mapa {
      height: 260px;
      width: 100%;
      border-radius: 1.25rem;
      z-index: 10;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-900 font-sans">
  <div class="mx-auto min-h-screen max-w-[430px] bg-white shadow-lg relative pb-28">
    
    <!-- Cabeçalho & Pesquisa Corrigida -->
    <header class="px-5 pt-5 pb-3 bg-white border-b border-gray-100 sticky top-0 z-40 bg-white/95 backdrop-blur">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-green-700">Guia & Turismo</p>
          <h1 class="text-xl font-extrabold">Conecta <span class="text-green-700">Corrente</span></h1>
        </div>
        <a href="login.php" class="bg-green-50 text-green-700 text-xs px-3 py-2 rounded-xl font-bold">+ Cadastrar</a>
      </div>

      <!-- Formulário de Pesquisa -->
      <form method="GET" action="index.php" class="mt-3 relative">
        <input type="search" name="q" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar estabelecimentos, serviços..." class="w-full h-11 rounded-xl bg-gray-100 border-none pl-10 pr-10 text-xs outline-none focus:ring-2 focus:ring-green-600" />
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></span>
        <?php if (!empty($busca)): ?>
          <a href="index.php<?= !empty($categoria_filtro) ? '?cat=' . urlencode($categoria_filtro) : '' ?>" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs font-bold hover:text-gray-600">✕</a>
        <?php endif; ?>
        <?php if (!empty($categoria_filtro)): ?>
          <input type="hidden" name="cat" value="<?= htmlspecialchars($categoria_filtro) ?>">
        <?php endif; ?>
      </form>
    </header>

    <!-- Carrossel de Categorias -->
    <section class="pt-3 pb-2 border-b border-gray-50">
      <div class="flex gap-2 overflow-x-auto px-5 hide-scrollbar text-xs font-bold">
        <a href="index.php<?= !empty($busca) ? '?q=' . urlencode($busca) : '' ?>" class="shrink-0 px-4 py-2 rounded-xl border <?= empty($categoria_filtro) ? 'bg-green-700 text-white border-green-700' : 'bg-gray-50 text-gray-600 border-gray-200' ?>">Todas</a>
        <a href="index.php?cat=Alimentação<?= !empty($busca) ? '&q=' . urlencode($busca) : '' ?>" class="shrink-0 px-4 py-2 rounded-xl border <?= $categoria_filtro === 'Alimentação' ? 'bg-green-700 text-white border-green-700' : 'bg-gray-50 text-gray-600 border-gray-200' ?>"> Alimentação</a>
        <a href="index.php?cat=Hotelaria<?= !empty($busca) ? '&q=' . urlencode($busca) : '' ?>" class="shrink-0 px-4 py-2 rounded-xl border <?= $categoria_filtro === 'Hotelaria' ? 'bg-green-700 text-white border-green-700' : 'bg-gray-50 text-gray-600 border-gray-200' ?>"> Hotelaria</a>
        <a href="index.php?cat=Serviços<?= !empty($busca) ? '&q=' . urlencode($busca) : '' ?>" class="shrink-0 px-4 py-2 rounded-xl border <?= $categoria_filtro === 'Serviços' ? 'bg-green-700 text-white border-green-700' : 'bg-gray-50 text-gray-600 border-gray-200' ?>"> Serviços</a>
        <a href="index.php?cat=Artesanato<?= !empty($busca) ? '&q=' . urlencode($busca) : '' ?>" class="shrink-0 px-4 py-2 rounded-xl border <?= $categoria_filtro === 'Artesanato' ? 'bg-green-700 text-white border-green-700' : 'bg-gray-50 text-gray-600 border-gray-200' ?>"> Artesanato</a>
        <a href="index.php?cat=Ecoturismo<?= !empty($busca) ? '&q=' . urlencode($busca) : '' ?>" class="shrink-0 px-4 py-2 rounded-xl border <?= $categoria_filtro === 'Ecoturismo' ? 'bg-green-700 text-white border-green-700' : 'bg-gray-50 text-gray-600 border-gray-200' ?>"> Ecoturismo</a>
      </div>
    </section>

    <main class="px-5 pt-4 space-y-6">
      
      <!-- Seção do Mapa Interativo -->
      <section>
        <div class="flex items-center justify-between mb-2">
          <h2 class="font-extrabold text-sm text-gray-700 uppercase tracking-wider"> Mapa Comercial & Turístico</h2>
          <span class="text-[10px] text-gray-400">Toque no card para focar</span>
        </div>
        <div id="mapa" class="shadow-sm border border-gray-200"></div>
      </section>

      <!-- Lista de Estabelecimentos -->
      <section class="space-y-4">
        <h2 class="text-lg font-extrabold">Locais encontrados (<?= count($estabelecimentos) ?>)</h2>

        <?php if (empty($estabelecimentos)): ?>
          <div class="text-center py-8 bg-gray-50 rounded-2xl">
            <p class="text-xs text-gray-500">Nenhum local encontrado para esta busca.</p>
            <a href="index.php" class="inline-block mt-3 text-xs font-bold bg-green-700 text-white px-3 py-2 rounded-xl">Limpar filtros</a>
          </div>
        <?php else: ?>
          <?php foreach ($estabelecimentos as $est): ?>
            <?php 
              $lat = !empty($est['latitude']) ? floatval($est['latitude']) : -10.4431;
              $lng = !empty($est['longitude']) ? floatval($est['longitude']) : -45.1611;
            ?>
            <article class="rounded-2xl overflow-hidden border border-gray-100 bg-white shadow-sm hover:shadow-md transition p-3 flex gap-3 cursor-pointer" onclick="focarNoMapa(<?= $lat ?>, <?= $lng ?>, '<?= addslashes(htmlspecialchars($est['nome'])) ?>')">
              <img class="w-24 h-24 rounded-xl object-cover bg-gray-100 shrink-0" src="<?= htmlspecialchars($est['imagem_url'] ?? 'https://images.unsplash.com/photo-1445116572660-236099ec97a0?auto=format&fit=crop&w=300&q=80') ?>" alt="Foto" />
              <div class="flex-1 min-w-0 flex flex-col justify-between">
                <div>
                  <span class="text-[10px] font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-md inline-block"><?= htmlspecialchars($est['categoria']) ?></span>
                  <h3 class="font-bold text-sm truncate mt-1"><?= htmlspecialchars($est['nome']) ?></h3>
                  <p class="text-xs text-gray-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($est['descricao']) ?></p>
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-50">
                  <span class="text-[10px] text-gray-400 truncate"> <?= htmlspecialchars($est['endereco']) ?></span>
                  <a href="detalhe.php?id=<?= $est['id'] ?>" onclick="event.stopPropagation();" class="text-[11px] font-bold text-green-700 hover:underline shrink-0">Ver mais →</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <!-- Seção de Eventos Locais -->
      <section class="bg-gradient-to-br from-green-50 to-emerald-50 p-4 rounded-2xl border border-green-100">
        <div class="flex items-center justify-between mb-3">
          <div>
            <span class="text-[10px] uppercase font-bold text-green-700 tracking-wider">Agenda Cultural</span>
            <h2 class="text-md font-extrabold"> Próximos Eventos em Corrente</h2>
          </div>
        </div>

        <?php if (empty($eventos)): ?>
          <p class="text-xs text-gray-500">Nenhum evento agendado para os próximos dias.</p>
        <?php else: ?>
          <div class="space-y-2">
            <?php foreach ($eventos as $ev): ?>
              <a href="evento_detalhe.php?id=<?= $ev['id'] ?>" class="bg-white p-3 rounded-xl border border-gray-100 flex gap-3 items-center hover:border-green-300 transition block">
                <div class="w-12 h-12 rounded-lg bg-green-700 text-white flex flex-col items-center justify-center shrink-0">
                  <span class="text-[10px] font-bold uppercase"><?= date('M', strtotime($ev['data_evento'])) ?></span>
                  <span class="text-base font-extrabold leading-none"><?= date('d', strtotime($ev['data_evento'])) ?></span>
                </div>
                <div class="min-w-0 flex-1">
                  <h3 class="font-bold text-xs truncate"><?= htmlspecialchars($ev['titulo']) ?></h3>
                  <p class="text-[11px] text-gray-500 truncate"> <?= htmlspecialchars($ev['local']) ?></p>
                </div>
                <span class="text-xs text-green-700 font-bold">Ver →</span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

    </main>

    <!-- Navegação Fixa Inferior -->
    <nav class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-[430px] bg-white/95 backdrop-blur border-t border-gray-100 px-3 pt-2 pb-3 z-50">
      <div class="grid grid-cols-2 gap-2">
        <a href="index.php" class="flex flex-col items-center justify-center py-2 text-green-700 font-bold text-xs">
          <span> Guia & Mapa</span>
        </a>
        <a href="login.php" class="flex flex-col items-center justify-center py-2 text-gray-400 hover:text-green-700 font-bold text-xs">
          <span> Cadastrar / Painel</span>
        </a>
      </div>
    </nav>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const correnteLat = -10.4431;
    const correnteLng = -45.1611;

    const map = L.map('mapa').setView([correnteLat, correnteLng], 14);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);

    const pontos = <?= json_encode($pontos_mapa); ?>;
    const markers = {};

    pontos.forEach(ponto => {
      const popupContent = `
        <div style="font-family: sans-serif; text-align: center;">
          <strong style="font-size: 13px; color: #15803d;">${ponto.nome}</strong><br>
          <span style="font-size: 10px; color: #666;">${ponto.categoria}</span><br>
          <a href="detalhe.php?id=${ponto.id}" style="display: inline-block; margin-top: 5px; padding: 3px 8px; background: #15803d; color: white; border-radius: 6px; text-decoration: none; font-size: 10px; font-weight: bold;">Ver perfil</a>
        </div>
      `;

      const marker = L.marker([ponto.lat, ponto.lng])
        .addTo(map)
        .bindPopup(popupContent);

      markers[`${ponto.lat}_${ponto.lng}`] = marker;
    });

    function focarNoMapa(lat, lng, nome) {
      map.flyTo([lat, lng], 16, { duration: 1.2 });
      const key = `${lat}_${lng}`;
      if (markers[key]) {
        markers[key].openPopup();
      }
      document.getElementById('mapa').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  </script>
</body>
</html>