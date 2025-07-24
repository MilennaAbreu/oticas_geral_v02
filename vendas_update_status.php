<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = $_POST['status'] ?? '';

if ($id && $status) {
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare("SELECT STATUS, ID_CLIENTE, ID_EMPRESA FROM VENDAS WHERE ID=?");
        $st->execute([$id]);
        $sale = $st->fetch(PDO::FETCH_ASSOC);
        if(!$sale){ throw new Exception('Venda não encontrada'); }
        $old = $sale['STATUS'];
        $pdo->prepare("UPDATE VENDAS SET STATUS=? WHERE ID=?")->execute([$status,$id]);

        if(tableExists($pdo,'CONTAS_A_RECEBER')){
            $pdo->prepare("DELETE FROM CONTAS_A_RECEBER WHERE ID_VENDA=?")->execute([$id]);
            if($status==='CONCLUÍDA'){
                $tablePag = tableExists($pdo,'VENDAS_PAGAMENTOS') ? 'VENDAS_PAGAMENTOS' : (tableExists($pdo,'VENDAS_PAGAMENTO') ? 'VENDAS_PAGAMENTO' : null);
                if($tablePag){
                    $hasParc=columnExists($pdo,$tablePag,'PARCELAS');
                    $vencCol=null;
                    if(columnExists($pdo,$tablePag,'DATA_VENC_PARCELA')) $vencCol='DATA_VENC_PARCELA';
                    elseif(columnExists($pdo,$tablePag,'DATA_VENCIMENTO_PARCELA')) $vencCol='DATA_VENCIMENTO_PARCELA';
                    $hasVenc=$vencCol!==null;
                    $sql="SELECT VALOR".($hasParc?",PARCELAS":"").($hasVenc?",".$vencCol:"") ." FROM {$tablePag} WHERE ID_VENDA=?";
                    $sp=$pdo->prepare($sql);
                    $sp->execute([$id]);
                    $pags=$sp->fetchAll(PDO::FETCH_ASSOC);
                    $stmtRec=$pdo->prepare("INSERT INTO CONTAS_A_RECEBER (ID_VENDA,ID_CLIENTE,VALOR,DATA_VENCIMENTO,STATUS,ID_EMPRESA) VALUES (?,?,?,?,?,?)");
                    foreach($pags as $pg){
                        $qt=max(1,$hasParc?($pg['PARCELAS']??1):1);
                        $vp=round($pg['VALOR']/$qt,2);
                        $vencBase=$hasVenc?($pg[$vencCol]??date('Y-m-d')):date('Y-m-d');
                        for($i=0;$i<$qt;$i++){
                            $venc=date('Y-m-d',strtotime($vencBase." +{$i} month"));
                            $stmtRec->execute([$id,$sale['ID_CLIENTE'],$vp,$venc,'PENDENTE',$sale['ID_EMPRESA']]);
                        }
                    }
                }
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
}
exit;
?>
