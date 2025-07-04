<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_POST['id'] ?? null;
$id_cliente = $_POST['id_cliente'] ?? null;
$id_usuario = $_POST['id_usuario'] ?? null;
$id_condicao = $_POST['id_condicao_pagamento'] ?? null;
$id_metodo   = $_POST['id_metodo_pagamento'] ?? null;
$id_frete    = $_POST['id_frete'] ?? null;
$data_entrega = $_POST['data_entrega'] ?: null;
$valor_venda = str_replace(',', '.', $_POST['valor_venda'] ?? '0');
$desconto    = str_replace(',', '.', $_POST['desconto'] ?? '0');
$valor_total = str_replace(',', '.', $_POST['valor_total'] ?? '0');
$telefone    = $_POST['telefone_contato'] ?? null;
$responsavel = $_POST['responsavel_contato'] ?? null;
$cep         = preg_replace('/\D/', '', $_POST['cep_entrega'] ?? '');
$rua         = $_POST['rua_entrega'] ?? null;
$bairro      = $_POST['bairro_entrega'] ?? null;
$id_cidade   = $_POST['id_cidade'] ?? null;
$obs         = $_POST['observacao'] ?? null;
$status      = $_POST['status'] ?? 'PENDENTE';
$id_empresa  = $_POST['id_empresa'] ?? null;

// busca juros aplicado conforme metodo e condicao
$parcelas = 1;
$st = $pdo->prepare("SELECT PARCELAS FROM CONDICAO_PAGAMENTO WHERE ID=?");
$st->execute([$id_condicao]);
$parcelas = (int)($st->fetchColumn() ?: 1);

$st = $pdo->prepare("SELECT JUROS_MENSAL FROM JUROS_METODO_CONDICAO WHERE ID_METODO_PAGAMENTO=?");
$st->execute([$id_metodo]);
$jurosMes = (float)($st->fetchColumn() ?: 0);

$juros_aplicado = $jurosMes * $parcelas;

$valor_liquido = $valor_total * (1 - $juros_aplicado/100);

$hasLiquido = columnExists($pdo,'VENDAS','VALOR_LIQUIDO');

if($id){
    if($hasLiquido){
        $sql = "UPDATE VENDAS SET ID_CLIENTE=?, ID_USUARIO=?, ID_CONDICAO_PAGAMENTO=?, ID_METODO_PAGAMENTO=?, JUROS_APLICADO=?, VALOR_VENDA=?, VALOR_TOTAL=?, VALOR_LIQUIDO=?, ID_FRETE=?, DATA_ENTREGA=?, DESCONTO=?, TELEFONE_CONTATO=?, RESPONSAVEL_CONTATO=?, CEP_ENTREGA=?, RUA_ENTREGA=?, BAIRRO_ENTREGA=?, ID_CIDADE=?, OBSERVACAO=?, STATUS=?, ID_EMPRESA=? WHERE ID=?";
        $params = [$id_cliente,$id_usuario,$id_condicao,$id_metodo,$juros_aplicado,$valor_venda,$valor_total,$valor_liquido,$id_frete,$data_entrega,$desconto,$telefone,$responsavel,$cep,$rua,$bairro,$id_cidade,$obs,$status,$id_empresa,$id];
    } else {
        $sql = "UPDATE VENDAS SET ID_CLIENTE=?, ID_USUARIO=?, ID_CONDICAO_PAGAMENTO=?, ID_METODO_PAGAMENTO=?, JUROS_APLICADO=?, VALOR_VENDA=?, VALOR_TOTAL=?, ID_FRETE=?, DATA_ENTREGA=?, DESCONTO=?, TELEFONE_CONTATO=?, RESPONSAVEL_CONTATO=?, CEP_ENTREGA=?, RUA_ENTREGA=?, BAIRRO_ENTREGA=?, ID_CIDADE=?, OBSERVACAO=?, STATUS=?, ID_EMPRESA=? WHERE ID=?";
        $params = [$id_cliente,$id_usuario,$id_condicao,$id_metodo,$juros_aplicado,$valor_venda,$valor_total,$id_frete,$data_entrega,$desconto,$telefone,$responsavel,$cep,$rua,$bairro,$id_cidade,$obs,$status,$id_empresa,$id];
    }
    $pdo->prepare($sql)->execute($params);
} else {
    if($hasLiquido){
        $sql = "INSERT INTO VENDAS (ID_CLIENTE,ID_USUARIO,ID_CONDICAO_PAGAMENTO,ID_METODO_PAGAMENTO,JUROS_APLICADO,VALOR_VENDA,VALOR_TOTAL,VALOR_LIQUIDO,ID_FRETE,DATA_ENTREGA,DESCONTO,TELEFONE_CONTATO,RESPONSAVEL_CONTATO,CEP_ENTREGA,RUA_ENTREGA,BAIRRO_ENTREGA,ID_CIDADE,OBSERVACAO,STATUS,ID_EMPRESA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = [$id_cliente,$id_usuario,$id_condicao,$id_metodo,$juros_aplicado,$valor_venda,$valor_total,$valor_liquido,$id_frete,$data_entrega,$desconto,$telefone,$responsavel,$cep,$rua,$bairro,$id_cidade,$obs,$status,$id_empresa];
    } else {
        $sql = "INSERT INTO VENDAS (ID_CLIENTE,ID_USUARIO,ID_CONDICAO_PAGAMENTO,ID_METODO_PAGAMENTO,JUROS_APLICADO,VALOR_VENDA,VALOR_TOTAL,ID_FRETE,DATA_ENTREGA,DESCONTO,TELEFONE_CONTATO,RESPONSAVEL_CONTATO,CEP_ENTREGA,RUA_ENTREGA,BAIRRO_ENTREGA,ID_CIDADE,OBSERVACAO,STATUS,ID_EMPRESA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $params = [$id_cliente,$id_usuario,$id_condicao,$id_metodo,$juros_aplicado,$valor_venda,$valor_total,$id_frete,$data_entrega,$desconto,$telefone,$responsavel,$cep,$rua,$bairro,$id_cidade,$obs,$status,$id_empresa];
    }
    $pdo->prepare($sql)->execute($params);
    $id = $pdo->lastInsertId();
    // gera contas a receber quando nova venda inserida
    $valorParcela = $parcelas ? $valor_liquido / $parcelas : $valor_liquido;
    $stmtCR = $pdo->prepare("INSERT INTO CONTAS_A_RECEBER (ID_VENDA, PARCELA, VALOR, DATA_VENCIMENTO) VALUES (?,?,?,?)");
    for($p=1;$p<=($parcelas?:1);$p++){
        $venc = date('Y-m-d', strtotime("+".($p-1)." month"));
        $stmtCR->execute([$id,$p,$valorParcela,$venc]);
    }
}

header('Location: vendas_list.php');
exit;
?>
