<?php
require_once 'db.php';

$met = $_GET['met'] ?? 0;
$cond = $_GET['cond'] ?? 0;
$juros = 0;
$parcelas = 1;
if($met && $cond){
    $st = $pdo->prepare(
        "SELECT c.PARCELAS, j.JUROS_MENSAL
           FROM CONDICAO_PAGAMENTO c
           LEFT JOIN JUROS_METODO_CONDICAO j
             ON j.ID_CONDICAO = c.ID
            AND j.ID_METODO_PAGAMENTO = ?
          WHERE c.ID = ?"
    );
    $st->execute([$met, $cond]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if($row){
        $parcelas = (int)$row['PARCELAS'];
        $juros = (float)$row['JUROS_MENSAL'];
    }
}
header('Content-Type: application/json');
echo json_encode(['juros'=>$juros,'parcelas'=>$parcelas]);
