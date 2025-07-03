<?php
include 'header.php';
$id = $_GET['id']??null;
if($id){
    $pdo->prepare("DELETE FROM CLIENTE WHERE id=?")->execute([$id]);
}
header('Location: cliente_list.php');