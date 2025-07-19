<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_GET['id'] ?? null;

if ($id) {
    $pdo->beginTransaction();
    try {
        // devolve estoque e remove itens da venda
        if (tableExists($pdo, 'ITENS_VENDA')) {
            $stmtItens = $pdo->prepare("SELECT ID_PRODUTO, QUANTIDADE FROM ITENS_VENDA WHERE ID_VENDA=?");
            $stmtItens->execute([$id]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
            if ($itens) {
                $upEst = $pdo->prepare("UPDATE PRODUTO SET ESTOQUE_ATUAL = ESTOQUE_ATUAL + ? WHERE ID=?");
                foreach ($itens as $it) {
                    $upEst->execute([$it['QUANTIDADE'], $it['ID_PRODUTO']]);
                }
            }
            $pdo->prepare("DELETE FROM ITENS_VENDA WHERE ID_VENDA=?")->execute([$id]);
        }

        // remove formas de pagamento vinculadas
        $tablePag = tableExists($pdo, 'VENDAS_PAGAMENTOS')
            ? 'VENDAS_PAGAMENTOS'
            : (tableExists($pdo, 'VENDAS_PAGAMENTO') ? 'VENDAS_PAGAMENTO' : null);
        if ($tablePag) {
            $pdo->prepare("DELETE FROM {$tablePag} WHERE ID_VENDA=?")->execute([$id]);
        }

        // remove contas a receber geradas
        if (tableExists($pdo, 'CONTAS_A_RECEBER')) {
            $pdo->prepare("DELETE FROM CONTAS_A_RECEBER WHERE ID_VENDA=?")->execute([$id]);
        }

        // por fim, exclui a venda
        $pdo->prepare("DELETE FROM VENDAS WHERE ID=?")->execute([$id]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
    }
}

header('Location: vendas_list.php');
exit;
?>
