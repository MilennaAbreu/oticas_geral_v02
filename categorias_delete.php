<?php
include 'header.php';
$id = $_GET['id'] ?? null;
if($id){
    $pdo->prepare("DELETE FROM CATEGORIA_PRODUTO WHERE id=?")->execute([$id]);
}
header('Location: categorias_list.php');
