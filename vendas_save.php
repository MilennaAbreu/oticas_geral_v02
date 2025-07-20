<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_POST['id'] ?? null;
$id_cliente = $_POST['id_cliente'] ?? null;
$id_usuario = $_POST['id_usuario'] ?? null;
$id_condicao = $_POST['id_condicao_pagamento'] ?? null;
$jurosSel = $_POST['juros_metodo_condicao'] ?? [];
$valoresPag = $_POST['valores_pagamento'] ?? [];
$parcelasPag = $_POST['parcelas_pagamento'] ?? [];
$firstJmc = $jurosSel[0] ?? null;
$id_metodo = null;
$jmcDados = $pdo->query(
    "SELECT j.ID, j.ID_METODO_PAGAMENTO AS METODO_ID, j.ID_CONDICAO AS CONDICAO_ID, j.JUROS_MENSAL,
            m.NOME AS METODO, c.NOME AS CONDICAO
       FROM JUROS_METODO_CONDICAO j
       JOIN METODO_PAGAMENTO m ON j.ID_METODO_PAGAMENTO=m.ID
       JOIN CONDICAO_PAGAMENTO c ON j.ID_CONDICAO=c.ID"
)->fetchAll(PDO::FETCH_ASSOC);
$jmcMap = [];
foreach($jmcDados as $j){
    $jmcMap[$j['ID']] = $j;
    if($firstJmc && $j['ID'] == $firstJmc){
        $id_metodo = $j['METODO_ID'];
        $id_condicao = $j['CONDICAO_ID'];
    }
}
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

// monta pagamentos e calcula juros
$pagamentos = [];
$valor_liquido_calc = 0;
$parcelas = 1;
foreach($jurosSel as $i=>$jmcId){
    $val = isset($valoresPag[$i]) ? (float)str_replace(',', '.', $valoresPag[$i]) : 0;
    $par = isset($parcelasPag[$i]) ? (int)$parcelasPag[$i] : 1;
    if(!$jmcId || $val===''){
        http_response_code(400);
        exit('Pagamento incompleto');
    }
    $dados = $jmcMap[$jmcId] ?? null;
    if($dados){
        if($i==0){ $id_metodo = $dados['METODO_ID']; $id_condicao = $dados['CONDICAO_ID']; }
        $jurosLinha = (float)$dados['JUROS_MENSAL'] * $par;
    } else { $jurosLinha = 0; }
    $valor_liquido_calc += $val * (1 - $jurosLinha/100);
    $parcelas = max($parcelas,$par);
    $pagamentos[] = ['metodo_id'=>$dados['METODO_ID'] ?? null,'valor'=>$val,'parcelas'=>$par];
}
$somaPag = array_sum(array_column($pagamentos,'valor'));
if(round($somaPag,2) != round($valor_total,2)){
    http_response_code(400); exit('Soma dos pagamentos difere do total');
}
$juros_aplicado = $valor_total>0 ? (1 - $valor_liquido_calc/$valor_total)*100 : 0;
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
        if(tableExists($pdo,'CONTAS_A_RECEBER')){
            $pdo->prepare("DELETE FROM CONTAS_A_RECEBER WHERE ID_VENDA=?")->execute([$id]);
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
    $hasParcPg = columnExists($pdo,$tablePag,'PARCELAS');
    $colsPg = "ID_VENDA, {$colMetodoPg}, VALOR".($hasParcPg? ', PARCELAS':'');
    $placePg = $hasParcPg ? '?,?,?,?' : '?,?,?';
    $stmtPg = $pdo->prepare("INSERT INTO {$tablePag} ($colsPg) VALUES ($placePg)");
    foreach($pagamentos as $pg){
        $valsPg = [$id,$pg['metodo_id'],$pg['valor']];
        if($hasParcPg) $valsPg[]=$pg['parcelas'];
        $stmtPg->execute($valsPg);
    }

    if(tableExists($pdo,'CONTAS_A_RECEBER')){
        $stmtRec = $pdo->prepare("INSERT INTO CONTAS_A_RECEBER (ID_VENDA, ID_CLIENTE, VALOR, DATA_VENCIMENTO, STATUS, ID_EMPRESA) VALUES (?,?,?,?,?,?)");
        $parcelasMapa=[];
        foreach($pagamentos as $pg){
            $qt=max(1,$pg['parcelas']);
            $vp=round($pg['valor']/$qt,2);
            for($i=0;$i<$qt;$i++){$parcelasMapa[$i]=($parcelasMapa[$i]??0)+$vp;}
        }
        foreach($parcelasMapa as $i=>$valP){
            $venc=date('Y-m-d',strtotime("$data_vencimento +{$i} month"));
            $stmtRec->execute([$id,$id_cliente,$valP,$venc,'PENDENTE',$id_empresa]);
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
