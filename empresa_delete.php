<?php
require 'config.php';
require 'auth.php';
$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM EMPRESA WHERE id=?");
    $stmt->execute([$id]);
}
header('Location: empresa_list.php');
exit();
