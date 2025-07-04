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
$data_vencimento = trim($_POST['data_vencimento'] ?? '');
if(empty($data_vencimento)){
    $data_vencimento = date('Y-m-d');
}
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
$juros_aplicado = 0;
if($id_metodo && $id_condicao){
    $st = $pdo->prepare(
        "SELECT c.PARCELAS, j.JUROS_MENSAL
           FROM CONDICAO_PAGAMENTO c
           LEFT JOIN JUROS_METODO_CONDICAO j
             ON j.ID_CONDICAO = c.ID
            AND j.ID_METODO_PAGAMENTO = ?
          WHERE c.ID = ?"
    );
    $st->execute([$id_metodo, $id_condicao]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if($row){
        $parcelas = (int)$row['PARCELAS'];
        $juros_aplicado = (float)$row['JUROS_MENSAL'] * $parcelas;
    }
}

$valor_liquido = $valor_total * (1 - $juros_aplicado/100);

$hasLiquido = columnExists($pdo,'VENDAS','VALOR_LIQUIDO');
$hasVencParc = columnExists($pdo,"VENDAS","DATA_VENCIMENTO_PARCELA");
$hasParcelas = columnExists($pdo,"VENDAS","NUMERO_PARCELAS");
$hasFormaPag = columnExists($pdo,"VENDAS","FORMA_PAGAMENTO");

// monta dinamicamente colunas e valores
$cols=['ID_CLIENTE','ID_USUARIO','ID_CONDICAO_PAGAMENTO','ID_METODO_PAGAMENTO','JUROS_APLICADO'];
$vals=[$id_cliente,$id_usuario,$id_condicao,$id_metodo,$juros_aplicado];
if($hasParcelas){$cols[]='NUMERO_PARCELAS';$vals[]=$parcelas;}
if($hasVencParc){$cols[]='DATA_VENCIMENTO_PARCELA';$vals[]=$data_vencimento;}
if($hasFormaPag){$cols[]='FORMA_PAGAMENTO';$vals[]=$parcelas>1?'PARCELADO':'À VISTA';}
$cols=array_merge($cols,['VALOR_VENDA','VALOR_TOTAL']);
$vals=array_merge($vals,[$valor_venda,$valor_total]);
if($hasLiquido){$cols[]='VALOR_LIQUIDO';$vals[]=$valor_liquido;}
$cols=array_merge($cols,['ID_FRETE','DATA_ENTREGA','DESCONTO','TELEFONE_CONTATO','RESPONSAVEL_CONTATO','CEP_ENTREGA','RUA_ENTREGA','BAIRRO_ENTREGA','ID_CIDADE','OBSERVACAO','STATUS','ID_EMPRESA']);
$vals=array_merge($vals,[$id_frete,$data_entrega,$desconto,$telefone,$responsavel,$cep,$rua,$bairro,$id_cidade,$obs,$status,$id_empresa]);

if($id){
    $set=implode('=?, ',$cols).'=?';
    $vals[]=$id;
    $sql="UPDATE VENDAS SET $set WHERE ID=?";
    $pdo->prepare($sql)->execute($vals);
}else{
    $place=implode(',',array_fill(0,count($cols),'?'));
    $sql="INSERT INTO VENDAS (".implode(',', $cols).") VALUES ($place)";
    $pdo->prepare($sql)->execute($vals);
    $id=$pdo->lastInsertId();
    $valorParcela=$parcelas? $valor_liquido/$parcelas:$valor_liquido;
    $stmtCR=$pdo->prepare("INSERT INTO CONTAS_A_RECEBER (ID_VENDA, ID_CLIENTE, VALOR, DATA_VENCIMENTO, STATUS, ID_EMPRESA) VALUES (?,?,?,?,?,?)");
    for($p=1;$p<=($parcelas ?: 1);$p++){
        $ts=strtotime($data_vencimento.' +'.($p-1).' month');
        $venc=$ts?date('Y-m-d',$ts):$data_vencimento;
        $stmtCR->execute([
            $id,
            $id_cliente,
            $valorParcela,
            $venc,
            'PENDENTE',
            $id_empresa
        ]);
    }
}
}

header('Location: vendas_list.php');
exit;
?>
