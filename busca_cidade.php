<?php
require_once 'config.php';
header('Content-Type: application/json');
$nome = isset($_GET['cidade']) ? trim($_GET['cidade']) : '';
$uf   = isset($_GET['uf']) ? trim($_GET['uf']) : '';
if(!$nome || !$uf){
    echo json_encode(['id'=>null]);
    exit;
}
$stmt = $pdo->prepare("SELECT ID FROM CIDADE WHERE NOME LIKE ? AND UF = ? LIMIT 1");
$stmt->execute([$nome, $uf]);
$id = $stmt->fetchColumn();
echo json_encode(['id'=>$id ? (int)$id : null]);

