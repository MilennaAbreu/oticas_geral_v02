<?php
include 'db.php';
$nome = $_POST['nome'] ?? '';
$juros = $_POST['juros'] ?? '0';
$condicao = $_POST['condicao'] ?? '';
$hasJuros = false;
$hasCondicao = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM CONDICAO_PAGAMENTO LIKE 'juros'");
    $hasJuros = $chk->fetch(PDO::FETCH_ASSOC) ? true : false;
} catch (PDOException $e) {
    $hasJuros = false;
}
try {
    $chk = $pdo->query("SHOW COLUMNS FROM CONDICAO_PAGAMENTO LIKE 'condicao'");
    $hasCondicao = $chk->fetch(PDO::FETCH_ASSOC) ? true : false;
} catch (PDOException $e) {
    $hasCondicao = false;
}
if($nome){
    $cols = ['nome'];
    $place = ['?'];
    $vals = [$nome];
    if($hasJuros){
        $cols[] = 'juros';
        $place[] = '?';
        $vals[] = str_replace(',','.',str_replace('.','',$juros));
    }
    if($hasCondicao){
        $cols[] = 'condicao';
        $place[] = '?';
        $vals[] = $condicao;
    }
    $stmt = $pdo->prepare("INSERT INTO CONDICAO_PAGAMENTO (".implode(',', $cols).") VALUES (".implode(',', $place).")");
    $stmt->execute($vals);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId(),'nome'=>$nome]);
}else{
    echo json_encode(['success'=>false]);
}
