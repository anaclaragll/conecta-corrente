<?php
session_start();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $adminUsuario = getenv('ADMIN_USER');
    $adminSenha = getenv('ADMIN_PASSWORD');

    if ($adminUsuario === false || $adminUsuario === '' || $adminSenha === false || $adminSenha === '') {
        $erro = "Credenciais do administrador não configuradas no servidor.";
    } elseif ($usuario === $adminUsuario && hash_equals($adminSenha, $senha)) {
        $_SESSION['admin_logado'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $erro = "Utilizador ou palavra-passe incorretos.";
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Administrativo — Conecta Corrente</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900 font-sans flex items-center justify-center min-h-screen p-4">
  <div class="max-w-[400px] w-full bg-white shadow-lg p-6 rounded-2xl">
    <div class="text-center mb-6">
      <h1 class="text-xl font-extrabold text-green-700">Painel Restrito</h1>
      <p class="text-xs text-gray-500 mt-1">Conecta Corrente — Área Administrativa</p>
    </div>

    <?php if ($erro): ?>
      <div class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 text-xs font-bold text-center">
        <?= htmlspecialchars($erro) ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div>
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Utilizador</label>
        <input type="text" name="usuario" required class="w-full h-11 rounded-xl border border-gray-200 px-3 text-sm outline-none focus:border-green-700" placeholder="admin" />
      </div>

      <div>
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Palavra-passe</label>
        <input type="password" name="senha" required class="w-full h-11 rounded-xl border border-gray-200 px-3 text-sm outline-none focus:border-green-700" placeholder="••••••••" />
      </div>

      <button type="submit" class="w-full h-11 rounded-xl bg-green-700 text-white font-bold text-sm hover:bg-green-800 transition">Entrar no Painel</button>
    </form>

    <div class="mt-6 text-center">
      <a href="index.php" class="text-xs font-bold text-gray-400 hover:text-green-700">← Voltar para o Guia Público</a>
    </div>
  </div>
</body>
</html>