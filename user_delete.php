<?php
require 'config.php';
require 'auth.php';
$id = $_GET['id'] ?? null;
if ($id) {
    $pdo->prepare("DELETE FROM USUARIO_EMPRESA WHERE id_usuario=?")->execute([$id]);
    $pdo->prepare("DELETE FROM USUARIO WHERE id=?")->execute([$id]);
}
header('Location: user_list.php');
exit();
