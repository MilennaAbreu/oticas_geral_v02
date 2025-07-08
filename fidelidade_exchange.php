<?php
require_once 'db.php';
require_once 'auth.php';

$idCliente = $_POST['id_cliente'] ?? null;
$produtos = $_POST['produto_id'] ?? [];
$pontos   = $_POST['pontos'] ?? [];
if(!$idCliente){
    echo json_encode(['success'=>false,'error'=>'Cliente inválido']);
    exit;
}
$total = 0;
foreach($pontos as $p){
    $total += (int)$p;
}
$pdo->beginTransaction();
$stmt = $pdo->prepare("SELECT PONTOS FROM FIDELIDADE_CLIENTE WHERE ID_CLIENTE=? FOR UPDATE");
$stmt->execute([$idCliente]);
$atual = (int)$stmt->fetchColumn();
if($total > $atual){
    $pdo->rollBack();
    echo json_encode(['success'=>false,'error'=>'Pontos insuficientes']);
    exit;
}
$new = $atual - $total;
$pdo->prepare("UPDATE FIDELIDADE_CLIENTE SET PONTOS=?, ULTIMA_ATUALIZACAO=NOW() WHERE ID_CLIENTE=?")->execute([$new,$idCliente]);
for($i=0;$i<count($produtos);$i++){
    $pid = $produtos[$i];
    if(!$pid) continue;
    $pdo->prepare("UPDATE PRODUTO SET ESTOQUE_ATUAL = ESTOQUE_ATUAL - 1 WHERE ID=?")->execute([$pid]);
}
$pdo->commit();
echo json_encode(['success'=>true]);
?>
