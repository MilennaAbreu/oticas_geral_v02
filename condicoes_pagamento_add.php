<?php
include 'db.php';
$nome = $_POST['nome'] ?? '';
$juros = $_POST['juros'] ?? '0';
$condicao = $_POST['condicao'] ?? '';
$hasJuros = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM CONDICAO_PAGAMENTO LIKE 'juros'");
    $hasJuros = $chk->fetch(PDO::FETCH_ASSOC) ? true : false;
} catch (PDOException $e) {
    $hasJuros = false;
}
if($nome){
    if($hasJuros){
        $stmt = $pdo->prepare("INSERT INTO CONDICAO_PAGAMENTO (nome,juros,condicao) VALUES (?,?,?)");
        $stmt->execute([$nome, str_replace(',','.',str_replace('.','',$juros)), $condicao]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO CONDICAO_PAGAMENTO (nome,condicao) VALUES (?,?)");
        $stmt->execute([$nome, $condicao]);
    }
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'nome'=>$nome]);
}else{
    echo json_encode(['success'=>false]);
}
