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
$juros_aplicado = 0.00;
$sql = "SELECT c.PARCELAS, j.JUROS_MENSAL
          FROM CONDICAO_PAGAMENTO c
          JOIN JUROS_METODO_CONDICAO j
            ON j.ID_CONDICAO = c.ID
           AND j.ID_METODO_PAGAMENTO = ?
         WHERE c.ID = ?";
$st = $pdo->prepare($sql);
$st->execute([$id_metodo, $id_condicao]);
if($row = $st->fetch(PDO::FETCH_ASSOC)){
    $juros_aplicado = (float)$row['JUROS_MENSAL'] * (int)$row['PARCELAS'];
}

$valor_liquido = $valor_total * (1 - $juros_aplicado/100);

if($id){
    $sql = "UPDATE VENDAS SET ID_CLIENTE=?, ID_USUARIO=?, ID_CONDICAO_PAGAMENTO=?, ID_METODO_PAGAMENTO=?, JUROS_APLICADO=?, VALOR_VENDA=?, VALOR_TOTAL=?, VALOR_LIQUIDO=?, ID_FRETE=?, DATA_ENTREGA=?, DESCONTO=?, TELEFONE_CONTATO=?, RESPONSAVEL_CONTATO=?, CEP_ENTREGA=?, RUA_ENTREGA=?, BAIRRO_ENTREGA=?, ID_CIDADE=?, OBSERVACAO=?, STATUS=?, ID_EMPRESA=? WHERE ID=?";
    $pdo->prepare($sql)->execute([
        $id_cliente,$id_usuario,$id_condicao,$id_metodo,$juros_aplicado,$valor_venda,$valor_total,$valor_liquido,$id_frete,$data_entrega,$desconto,$telefone,$responsavel,$cep,$rua,$bairro,$id_cidade,$obs,$status,$id_empresa,$id
    ]);
} else {
    $sql = "INSERT INTO VENDAS (ID_CLIENTE,ID_USUARIO,ID_CONDICAO_PAGAMENTO,ID_METODO_PAGAMENTO,JUROS_APLICADO,VALOR_VENDA,VALOR_TOTAL,VALOR_LIQUIDO,ID_FRETE,DATA_ENTREGA,DESCONTO,TELEFONE_CONTATO,RESPONSAVEL_CONTATO,CEP_ENTREGA,RUA_ENTREGA,BAIRRO_ENTREGA,ID_CIDADE,OBSERVACAO,STATUS,ID_EMPRESA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $pdo->prepare($sql)->execute([
        $id_cliente,$id_usuario,$id_condicao,$id_metodo,$juros_aplicado,$valor_venda,$valor_total,$valor_liquido,$id_frete,$data_entrega,$desconto,$telefone,$responsavel,$cep,$rua,$bairro,$id_cidade,$obs,$status,$id_empresa
    ]);
    $id = $pdo->lastInsertId();
}

header('Location: vendas_list.php');
exit;
?>
