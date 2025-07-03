<?php
include 'header.php';
$id = $_GET['id'] ?? null;
if($id){
    $pdo->prepare("DELETE FROM TIPO_PRODUTO WHERE id=?")->execute([$id]);
}
header('Location: tipo_produtos_list.php');
