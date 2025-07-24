<?php
require_once 'config.php';
require_once 'auth.php';

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

header('Content-Type: application/json');
if(!$id || !in_array($action,['start','pause','stop'])){
    echo json_encode(['success'=>false,'error'=>'Parametros invalidos']);
    exit;
}

if(!tableExists($pdo,'CONCERTO_OCULOS') || !tableExists($pdo,'CONCERTO_OCULOS_TEMPO')){
    echo json_encode(['success'=>false,'error'=>'Tabelas não encontradas']);
    exit;
}

try{
    $pdo->beginTransaction();
    $stmt=$pdo->prepare("SELECT DURACAO_FINAL_SEGUNDOS FROM CONCERTO_OCULOS WHERE ID=? FOR UPDATE");
    $stmt->execute([$id]);
    $total=(int)$stmt->fetchColumn();
    if($stmt->rowCount()==0){ throw new Exception('Registro não encontrado'); }

    if($action==='start'){
        $st=$pdo->prepare("SELECT 1 FROM CONCERTO_OCULOS_TEMPO WHERE ID_CONCERTO=? AND FIM IS NULL");
        $st->execute([$id]);
        if($st->fetch()){ throw new Exception('Já em execução'); }
        $pdo->prepare("INSERT INTO CONCERTO_OCULOS_TEMPO (ID_CONCERTO,INICIO) VALUES (?,NOW())")->execute([$id]);
    }else{
        $st=$pdo->prepare("SELECT ID,INICIO FROM CONCERTO_OCULOS_TEMPO WHERE ID_CONCERTO=? AND FIM IS NULL ORDER BY ID DESC LIMIT 1");
        $st->execute([$id]);
        $curr=$st->fetch(PDO::FETCH_ASSOC);
        if(!$curr) throw new Exception('Não iniciado');
        $pdo->prepare("UPDATE CONCERTO_OCULOS_TEMPO SET FIM=NOW() WHERE ID=?")->execute([$curr['ID']]);
        $diff=time()-strtotime($curr['INICIO']);
        $total+=$diff;
        $pdo->prepare("UPDATE CONCERTO_OCULOS SET DURACAO_FINAL_SEGUNDOS=? WHERE ID=?")->execute([$total,$id]);
        if($action==='stop'){
            $pdo->prepare("UPDATE CONCERTO_OCULOS SET SITUACAO='FINALIZADO' WHERE ID=?")->execute([$id]);
        }
    }
    $pdo->commit();
    echo json_encode(['success'=>true,'total'=>$total]);
} catch(Exception $e){
    $pdo->rollBack();
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
