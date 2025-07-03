<?php
include 'db.php';
$nome = $_POST['nome'] ?? '';
$juros = $_POST['juros'] ?? '0';
$condicao = $_POST['condicao'] ?? '';
if($nome){
    $stmt = $pdo->prepare("INSERT INTO CONDICAO_PAGAMENTO (nome,juros,condicao) VALUES (?,?,?)");
    $stmt->execute([$nome, str_replace(',','.',str_replace('.','',$juros)), $condicao]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'nome'=>$nome]);
}else{
    echo json_encode(['success'=>false]);
}
