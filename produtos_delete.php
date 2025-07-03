<?php
require 'config.php';
require 'auth.php';
$id = $_GET['id'] ?? null;
if($id){
    $pdo->prepare("DELETE FROM PRODUTO WHERE id=?")->execute([$id]);
}
header('Location: produtos_list.php');
exit();
