<?php
require_once 'db.php';

$met = $_GET['met'] ?? 0;
$cond = $_GET['cond'] ?? 0;
$juros = 0;
if($met && $cond){
    $sql = "SELECT c.PARCELAS, j.JUROS_MENSAL
              FROM CONDICAO_PAGAMENTO c
              JOIN JUROS_METODO_CONDICAO j
                ON j.ID_CONDICAO = c.ID
               AND j.ID_METODO_PAGAMENTO = ?
             WHERE c.ID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$met, $cond]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row){
        $juros = (float)$row['JUROS_MENSAL'] * (int)$row['PARCELAS'];
    }
}
header('Content-Type: application/json');
echo json_encode(['juros'=>$juros]);
