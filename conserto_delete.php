<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_GET['id'] ?? null;
if($id){
    $pdo->beginTransaction();
    try{
        $tbl = consertoItemTable($pdo);
        $col = consertoItemColumn($pdo);
        if(tableExists($pdo,$tbl)){
            $st = $pdo->prepare("SELECT ID_PRODUTO, QUANTIDADE FROM `{$tbl}` WHERE `{$col}`=?");
            $st->execute([$id]);
            $items = $st->fetchAll(PDO::FETCH_ASSOC);
            foreach($items as $it){
                $pdo->prepare("UPDATE PRODUTO SET ESTOQUE_ATUAL=ESTOQUE_ATUAL+? WHERE ID=?")
                    ->execute([$it['QUANTIDADE'],$it['ID_PRODUTO']]);
            }
            $pdo->prepare("DELETE FROM `{$tbl}` WHERE `{$col}`=?")->execute([$id]);
        }
        if(tableExists($pdo,'CONCERTO_OCULOS_TEMPO')){
            $pdo->prepare("DELETE FROM CONCERTO_OCULOS_TEMPO WHERE ID_CONCERTO=?")
                ->execute([$id]);
        }
        $pdo->prepare("DELETE FROM CONCERTO_OCULOS WHERE ID=?")->execute([$id]);
        $pdo->commit();
    }catch(Exception $e){
        $pdo->rollBack();
    }
}
header('Location: conserto_list.php');
exit;
?>
