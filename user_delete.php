<?php
require_once 'config.php';
require_once 'auth.php';
$id = $_GET['id'] ?? null;
if ($id) {
    $pdo->prepare("DELETE FROM USUARIO_EMPRESA WHERE ID_USUARIO=?")->execute([$id]);
    $pdo->prepare("DELETE FROM USUARIO WHERE id=?")->execute([$id]);
}
header('Location: user_list.php');
exit();
