<?php
require_once 'config.php';
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT id, senha, permissoes FROM USUARIO WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['senha'])) {
        $_SESSION['user'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['permissoes'] = $user['permissoes'];
        header("Location: dashboard.php");
        exit();
    } else {
        $message = "Usuário ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {theme: {extend: {colors: {primary: '#8E070D'}}}}
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-100 font-[Poppins]">
  <div class="bg-white shadow-md rounded-lg p-8 w-full max-w-sm">
    <div class="flex justify-center mb-4 text-primary space-x-4">
      <i class="fas fa-glasses fa-2x"></i>
      <i class="fas fa-shopping-cart fa-2x"></i>
      <i class="fas fa-truck fa-2x"></i>
    </div>
    <h2 class="text-center text-2xl font-semibold mb-6 text-primary">Login</h2>
    <?php if ($message): ?>
      <p class="text-red-600 text-center mb-2"><?= $message ?></p>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <input class="w-full border border-gray-300 rounded p-2 focus:border-primary focus:ring-0" type="text" name="username" placeholder="Usuário" required>
      <input class="w-full border border-gray-300 rounded p-2 focus:border-primary focus:ring-0" type="password" name="password" placeholder="Senha" required>
      <button type="submit" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 transition">Entrar</button>
    </form>
    <div class="text-right mt-2">
      <a href="#" class="text-sm text-primary hover:underline">Esqueci minha senha</a>
    </div>
  </div>
</body>
</html>
