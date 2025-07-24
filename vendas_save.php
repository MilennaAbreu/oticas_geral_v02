<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_POST['id'] ?? null;
$id_cliente = $_POST['id_cliente'] ?? null;
$id_usuario = $_POST['id_usuario'] ?? null;
$formasPag = $_POST['formas_pagamento'] ?? [];
$valoresPag = $_POST['valores_pagamento'] ?? [];
$id_metodo = $formasPag[0] ?? null;
$id_frete    = $_POST['id_frete'] ?? null;
$data_entrega = $_POST['data_entrega'] ?: null;
$data_venda   = $_POST['data_venda'] ?: null;
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

// monta pagamentos e calcula juros
$pagamentos = [];
$valor_liquido_calc = $valor_total;
$pagamentos = [];
foreach($formasPag as $i=>$met){
    $val = isset($valoresPag[$i]) ? (float)str_replace(',', '.', $valoresPag[$i]) : 0;
    if(!$met || $val===''){
        http_response_code(400);
        exit('Pagamento incompleto');
    }
    if($i==0) $id_metodo = $met;
    $pagamentos[] = ['metodo_id'=>$met,'valor'=>$val];
}
$somaPag = array_sum(array_column($pagamentos,'valor'));
if(round($somaPag,2) != round($valor_total,2)){
    http_response_code(400); exit('Soma dos pagamentos difere do total');
}
$juros_aplicado = 0;
$valor_liquido = $valor_total;

$hasLiquido = columnExists($pdo,'VENDAS','VALOR_LIQUIDO');
$hasDataVenda = columnExists($pdo,'VENDAS','DATA_VENDA');
$hasFormaPag = columnExists($pdo,"VENDAS","FORMA_PAGAMENTO");

// monta dinamicamente colunas e valores
$cols=['ID_CLIENTE','ID_USUARIO','ID_METODO_PAGAMENTO','JUROS_APLICADO'];
$vals=[$id_cliente,$id_usuario,$id_metodo,$juros_aplicado];
if($hasDataVenda){$cols[]='DATA_VENDA';$vals[]=$data_venda;}
if($hasFormaPag){$cols[]='FORMA_PAGAMENTO';$vals[]='À VISTA';}
$cols=array_merge($cols,['VALOR_VENDA','VALOR_TOTAL']);
$vals=array_merge($vals,[$valor_venda,$valor_total]);
if($hasLiquido){$cols[]='VALOR_LIQUIDO';$vals[]=$valor_liquido;}
$cols=array_merge($cols,['ID_FRETE','DATA_ENTREGA','DESCONTO','TELEFONE_CONTATO','RESPONSAVEL_CONTATO','CEP_ENTREGA','RUA_ENTREGA','BAIRRO_ENTREGA','ID_CIDADE','OBSERVACAO','STATUS','ID_EMPRESA']);
$vals=array_merge($vals,[$id_frete,$data_entrega,$desconto,$telefone,$responsavel,$cep,$rua,$bairro,$id_cidade,$obs,$status,$id_empresa]);

$pdo->beginTransaction();
try{
    if($id){
        $set=implode('=?, ',$cols).'=?';
        $vals[]=$id;
        $sql="UPDATE VENDAS SET $set WHERE ID=?";
        $pdo->prepare($sql)->execute($vals);
        // remove pagamentos antigos
        $tablePag = tableExists($pdo,'VENDAS_PAGAMENTOS') ? 'VENDAS_PAGAMENTOS' : (tableExists($pdo,'VENDAS_PAGAMENTO') ? 'VENDAS_PAGAMENTO' : null);
        if($tablePag){
            $pdo->prepare("DELETE FROM {$tablePag} WHERE ID_VENDA=?")->execute([$id]);
        }
    }else{
        $place=implode(',',array_fill(0,count($cols),'?'));
        $sql="INSERT INTO VENDAS (".implode(',', $cols).") VALUES ($place)";
        $pdo->prepare($sql)->execute($vals);
        $id=$pdo->lastInsertId();
        if(!$id) throw new Exception('Falha ao inserir venda');
        $tablePag = tableExists($pdo,'VENDAS_PAGAMENTOS') ? 'VENDAS_PAGAMENTOS' : (tableExists($pdo,'VENDAS_PAGAMENTO') ? 'VENDAS_PAGAMENTO' : null);
    }

    if(!$tablePag){
        throw new Exception('Tabela de pagamentos não encontrada');
    }
    $colMetodoPg = 'ID_METODO_PAGAMENTO';
    if(!columnExists($pdo,$tablePag,$colMetodoPg)){
        foreach(['ID_METODO','METODO_ID','ID_METODO_PAG','ID_METODO_PAGTO'] as $c){
            if(columnExists($pdo,$tablePag,$c)){ $colMetodoPg = $c; break; }
        }
    }
    $colsPg = "ID_VENDA, {$colMetodoPg}, VALOR";
    $placePg = '?,?,?';
    $stmtPg = $pdo->prepare("INSERT INTO {$tablePag} ($colsPg) VALUES ($placePg)");
    foreach($pagamentos as $pg){
        $valsPg = [$id,$pg['metodo_id'],$pg['valor']];
        $stmtPg->execute($valsPg);
    }

    if(tableExists($pdo,'CONTAS_A_RECEBER')){
        $pdo->prepare("DELETE FROM CONTAS_A_RECEBER WHERE ID_VENDA=?")->execute([$id]);
        if($status === 'CONCLUÍDA'){
            $stmtRec = $pdo->prepare("INSERT INTO CONTAS_A_RECEBER (ID_VENDA, ID_CLIENTE, VALOR, DATA_VENCIMENTO, STATUS, ID_EMPRESA) VALUES (?,?,?,?,?,?)");
            foreach($pagamentos as $pg){
                $stmtRec->execute([$id,$id_cliente,$pg['valor'],date('Y-m-d'),'PENDENTE',$id_empresa]);
            }
        }
    }
    if(tableExists($pdo,'FIDELIDADE_CLIENTE')){
        $pts = $valor_total >= 500 ? 100 : 0;
        if($pts > 0){
            $stmtF = $pdo->prepare('INSERT INTO FIDELIDADE_CLIENTE (ID_CLIENTE,PONTOS,ULTIMA_ATUALIZACAO) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE PONTOS=PONTOS+VALUES(PONTOS), ULTIMA_ATUALIZACAO=NOW()');
            $stmtF->execute([$id_cliente,$pts]);
        }
    }
    $pdo->commit();
    header('Location: vendas_list.php');
    exit;
}catch(Exception $e){
    $pdo->rollBack();
    echo 'Erro: '.$e->getMessage();
}
?>
