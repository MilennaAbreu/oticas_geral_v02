<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? null;
if($id && $status){
    $stmt = $pdo->prepare("UPDATE VENDAS SET STATUS=? WHERE ID=?");
    $stmt->execute([$status,$id]);
}
?>
