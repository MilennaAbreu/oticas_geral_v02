<?php
require_once 'db.php';
$met = $_GET['met'] ?? 0;
$cond = $_GET['cond'] ?? 0;
$jurosCol = null;
try{
    $cols = $pdo->query("SHOW COLUMNS FROM JUROS_METODO_CONDICAO")->fetchAll(PDO::FETCH_COLUMN);
    foreach($cols as $c){ if(strtolower($c)=='juros' || strtolower($c)=='juros_aplicado'){ $jurosCol=$c; break; } }
}catch(Exception $e){ }
$juros = 0;
if($jurosCol){
    $stmt = $pdo->prepare("SELECT `{$jurosCol}` FROM JUROS_METODO_CONDICAO WHERE ID_METODO_PAGAMENTO=? AND ID_CONDICAO_PAGAMENTO=?");
    $stmt->execute([$met,$cond]);
    $val = $stmt->fetchColumn();
    if($val!==false) $juros = (float)$val;
}
header('Content-Type: application/json');
echo json_encode(['juros'=>$juros]);
