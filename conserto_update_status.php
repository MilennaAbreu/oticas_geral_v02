<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = $_POST['status'] ?? '';
if(!$id || !$status){ http_response_code(400); echo json_encode(['success'=>false,'error'=>'Dados incompletos']); exit; }
try{
    $pdo->beginTransaction();
    $st = $pdo->prepare("SELECT SITUACAO FROM CONCERTO_OCULOS WHERE ID=? FOR UPDATE");
    $st->execute([$id]);
    $old = $st->fetchColumn();
    if(!$old){ throw new Exception('Conserto não encontrado'); }

    $pdo->prepare("UPDATE CONCERTO_OCULOS SET SITUACAO=? WHERE ID=?")->execute([$status,$id]);

    $col = consertoItemColumn($pdo);
    $tbl = consertoItemTable($pdo);
    if(!tableExists($pdo,$tbl)){
        throw new Exception("Tabela de itens '$tbl' não encontrada");
    }

    if(!in_array($old,['APROVADO','ENTREGUE']) && $status === 'APROVADO'){
        $it = $pdo->prepare("SELECT ID_PRODUTO, QUANTIDADE FROM `{$tbl}` WHERE `{$col}`=?");
        $it->execute([$id]);
        foreach($it as $r){
            $pdo->prepare("UPDATE PRODUTO SET ESTOQUE_ATUAL=ESTOQUE_ATUAL-? WHERE ID=?")
                ->execute([$r['QUANTIDADE'],$r['ID_PRODUTO']]);
        }
    }

    if(in_array($old,['APROVADO','ENTREGUE']) && !in_array($status,['APROVADO','ENTREGUE'])){
        $it = $pdo->prepare("SELECT ID_PRODUTO, QUANTIDADE FROM `{$tbl}` WHERE `{$col}`=?");
        $it->execute([$id]);
        foreach($it as $r){
            $pdo->prepare("UPDATE PRODUTO SET ESTOQUE_ATUAL=ESTOQUE_ATUAL+? WHERE ID=?")
                ->execute([$r['QUANTIDADE'],$r['ID_PRODUTO']]);
        }
        $pdo->prepare("DELETE FROM `{$tbl}` WHERE `{$col}`=?")->execute([$id]);
    }
    $pdo->commit();
    echo json_encode(['success'=>true]);
}catch(Exception $e){
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
?>
