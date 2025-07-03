<?php
include 'header.php';
$id = $_GET['id']??null;
if($id){ $pdo->prepare("DELETE FROM FORNECEDOR WHERE id=?")->execute([$id]); }
header('Location: fornecedor_list.php');