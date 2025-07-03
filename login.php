<?php
require_once 'config.php';
// Se já autenticado, redireciona para dashboard
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
<html>
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; background:#f5f5f5; }
        .login-box { background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.1); width:300px; }
        input { width:100%; padding:10px; margin:5px 0; border:1px solid #ccc; border-radius:4px; }
        button { width:100%; padding:10px; margin:10px 0; border:none; border-radius:4px; background:#007BFF; color:#fff; cursor:pointer; }
        button:hover { background:#0056b3; }
        .forgot { text-align:right; }
        .error { color:red; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <?php if ($message): ?><p class="error"><?= $message ?></p><?php endif; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Usuário" required>
            <input type="password" name="password" placeholder="Senha" required>
            <button class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" type="submit">Entrar</button>
        </form>
        <div class="forgot"><a href="#">Esqueci minha senha</a></div>
    </div>
</body>
</html>