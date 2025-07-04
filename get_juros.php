<?php
require_once 'db.php';

$met = $_GET['met'] ?? 0;
$cond = $_GET['cond'] ?? 0;
$juros = 0;
$parcelas = 1;
if($met && $cond){
    $st = $pdo->prepare("SELECT PARCELAS FROM CONDICAO_PAGAMENTO WHERE ID=?");
    $st->execute([$cond]);
    $parcelas = (int)($st->fetchColumn() ?: 1);

    $st = $pdo->prepare("SELECT JUROS_MENSAL FROM JUROS_METODO_CONDICAO WHERE ID_METODO_PAGAMENTO=?");
    $st->execute([$met]);
    $jurosMes = (float)($st->fetchColumn() ?: 0);

    $juros = $jurosMes * $parcelas;
}
header('Content-Type: application/json');
echo json_encode(['juros'=>$juros,'parcelas'=>$parcelas]);
