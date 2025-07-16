<?php
include 'db.php';
$nome  = $_POST['nome']  ?? '';
$cpf   = preg_replace('/\D/','', $_POST['cpf'] ?? '');
$cid   = $_POST['id_cidade'] ?? null;
$nasc  = $_POST['nascimento'] ?? null;

if($nome && $cpf){
    $stmt = $pdo->prepare("INSERT INTO CLIENTE (NOME,CPF,DATA_NASCIMENTO,ID_CIDADE,STATUS) VALUES (?,?,?,?, 'ATIVO')");
    $stmt->execute([$nome,$cpf,$nasc,$cid]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'nome'=>$cpf.' - '.$nome]);
} else {
    echo json_encode(['success'=>false]);
}
?>
