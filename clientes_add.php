<?php
include 'db.php';
$nome = $_POST['nome'] ?? '';
$cpf  = preg_replace('/\D/','', $_POST['cpf'] ?? '');
if($nome && $cpf){
    $stmt = $pdo->prepare("INSERT INTO CLIENTE (NOME,CPF,STATUS) VALUES (?,?, 'ATIVO')");
    $stmt->execute([$nome,$cpf]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'nome'=>$cpf.' - '.$nome]);
} else {
    echo json_encode(['success'=>false]);
}
?>
