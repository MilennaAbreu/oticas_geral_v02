<?php
require_once 'config.php';
header('Content-Type: application/json');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(!$id){
    echo json_encode(null);
    exit;
}
$stmt = $pdo->prepare("SELECT CEP,RUA,BAIRRO,ID_CIDADE FROM CLIENTE WHERE ID=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$row){
    echo json_encode(null);
    exit;
}
echo json_encode($row);
?>
