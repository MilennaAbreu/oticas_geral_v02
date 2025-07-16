<?php
include 'db.php';
$descricao = $_POST['descricao'] ?? '';
$valor = $_POST['valor'] ?? '';
$status = $_POST['status'] ?? 'ATIVO';
if($descricao){
    $stmt = $pdo->prepare("INSERT INTO FRETE (descricao,valor,status) VALUES (?,?,?)");
    $stmt->execute([$descricao, str_replace(',','.',str_replace('.','',$valor)), $status]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'descricao'=>$descricao]);
}else{
    echo json_encode(['success'=>false]);
}
