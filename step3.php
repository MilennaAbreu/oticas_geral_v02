<?php
require_once 'config.php';
require_once 'auth.php';
if(!isset($_SESSION['venda']['ID_EMPRESA']) || !isset($_SESSION['venda']['itens']) || !isset($_SESSION['venda']['ID_CLIENTE'])){
    header('Location: step1.php');
    exit;
}

$itens = $_SESSION['venda']['itens'];
$cliente = ['CEP'=>'','RUA'=>'','BAIRRO'=>'','ID_CIDADE'=>'','CONTATO'=>''];
$stmt = $pdo->prepare("SELECT CEP, RUA, BAIRRO, ID_CIDADE, CONTATO FROM CLIENTE WHERE ID=?");
$stmt->execute([$_SESSION['venda']['ID_CLIENTE']]);
$cRow = $stmt->fetch(PDO::FETCH_ASSOC);
if($cRow) $cliente = $cRow;

// CEP da empresa para cálculo de distância
$stmt = $pdo->prepare("SELECT CEP FROM EMPRESA WHERE ID=?");
$stmt->execute([$_SESSION['venda']['ID_EMPRESA']]);
$cepEmpresa = preg_replace('/\D/','', $stmt->fetchColumn() ?: '');

function cepCoords($cep){
    $resp = @file_get_contents("https://cep.awesomeapi.com.br/json/{$cep}");
    if(!$resp) return null;
    $data = json_decode($resp,true);
    return (isset($data['lat']) && isset($data['lng'])) ? [(float)$data['lat'],(float)$data['lng']] : null;
}

function distanciaKm($cep1,$cep2){
    $c1 = cepCoords($cep1); $c2 = cepCoords($cep2);
    if(!$c1 || !$c2) return 0;
    list($lat1,$lon1) = $c1; list($lat2,$lon2) = $c2;
    $lat1 = deg2rad($lat1); $lat2 = deg2rad($lat2);
    $lon1 = deg2rad($lon1); $lon2 = deg2rad($lon2);
    $dlat = $lat2 - $lat1; $dlon = $lon2 - $lon1;
    $a = sin($dlat/2)**2 + cos($lat1)*cos($lat2)*sin($dlon/2)**2;
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return 6371 * $c;
}

// valores default dos campos
$jurosSel   = $_POST['juros_metodo_condicao'] ?? [];
$valoresPag  = $_POST['valores_pagamento'] ?? [];
$parcelasPag = $_POST['parcelas_pagamento'] ?? [];
$vencPag     = $_POST['vencimentos_pagamento'] ?? [];

// dados de juros/metodo/condicao
$jmcDados = $pdo->query(
    "SELECT j.ID, j.ID_METODO_PAGAMENTO AS METODO_ID, j.ID_CONDICAO AS CONDICAO_ID, j.JUROS_MENSAL, c.PARCELAS, m.NOME AS METODO, c.NOME AS CONDICAO
       FROM JUROS_METODO_CONDICAO j
       JOIN METODO_PAGAMENTO m ON j.ID_METODO_PAGAMENTO=m.ID
       JOIN CONDICAO_PAGAMENTO c ON j.ID_CONDICAO=c.ID
     ORDER BY m.NOME,c.NOME"
)->fetchAll(PDO::FETCH_ASSOC);
$jmcMap = [];
foreach($jmcDados as $j){
    $jmcMap[$j['ID']] = $j;
}

$firstJmc = $jurosSel[0] ?? '';
$idMet = isset($jmcMap[$firstJmc]) ? $jmcMap[$firstJmc]['METODO_ID'] : '';
$idCond = isset($jmcMap[$firstJmc]) ? $jmcMap[$firstJmc]['CONDICAO_ID'] : '';
$dataVenda = $_SESSION['venda']['DATA_VENDA'] ?? date('Y-m-d');

// detect possible column names for metodo de pagamento nas tabelas
$metColVenda = null;
foreach(['ID_METODO_PAGAMENTO','ID_METODO','METODO_ID','ID_METODO_PAG','ID_METODO_PAGTO'] as $c){
    if(columnExists($pdo,'VENDAS',$c)){ $metColVenda = $c; break; }
}
$metColJuros = 'ID_METODO_PAGAMENTO';
foreach(['ID_METODO_PAGAMENTO','ID_METODO','METODO_ID','ID_METODO_PAG','ID_METODO_PAGTO'] as $c){
    if(columnExists($pdo,'JUROS_METODO_CONDICAO',$c)){ $metColJuros = $c; break; }
}
$idFrete = $_POST['id_frete'] ?? '';
$dataEntrega = $_POST['data_entrega'] ?? '';
$telContato    = $_POST['telefone_contato'] ?? $cliente['CONTATO'];
$respContato   = $_POST['responsavel_contato'] ?? '';
$cepEntrega    = $_POST['cep_entrega'] ?? $cliente['CEP'];
$ruaEntrega    = $_POST['rua_entrega'] ?? $cliente['RUA'];
$bairroEntrega = $_POST['bairro_entrega'] ?? $cliente['BAIRRO'];
$idCidade      = $_POST['id_cidade'] ?? $cliente['ID_CIDADE'];
$descGeral     = str_replace(',', '.', $_POST['desconto_geral'] ?? '0');
$distanciaCalc = 0;

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['finalizar'])){
    $idFrete = $idFrete ?: null;
    $dataEntrega = $dataEntrega ?: null;
    $cepEntrega = preg_replace('/\D/','', $cepEntrega ?: '');

    // calcula valores
    $valorVenda = 0; $valorDescItens = 0;
    foreach($itens as $it){
        $valorVenda += $it['QUANTIDADE'] * $it['VALOR_UNITARIO'];
        $valorDescItens  += $it['DESCONTO'];
    }
    $freteValor = 0;
    $distanciaCalc = 0;
    if($idFrete){
        $st = $pdo->prepare("SELECT valor FROM FRETE WHERE id=?");
        $st->execute([$idFrete]);
        $valorKm = (float)$st->fetchColumn();
        if($cepEmpresa && $cepEntrega){
            $distanciaCalc = distanciaKm($cepEmpresa, $cepEntrega);
            $freteValor = $valorKm * $distanciaCalc;
        }
    }

    // juros aplicado conforme cada método e condição
    $valorTotal = 0; $valorDescItens = 0;
    foreach($itens as $it){
        $valorTotal += $it['QUANTIDADE'] * $it['VALOR_UNITARIO'];
        $valorDescItens += $it['DESCONTO'];
    }
    $valorTotal = $valorVenda - $valorDescItens - $descGeral + $freteValor;
    $valorLiquidoCalc = $valorTotal;
    $parcelas = 1;
    $jurosValor = 0;
    foreach($jurosSel as $i => $jmcId){
        $val  = isset($valoresPag[$i]) ? (float)str_replace(',', '.', $valoresPag[$i]) : 0;
        $par  = isset($parcelasPag[$i]) ? (int)$parcelasPag[$i] : 1;
        $jurosLinha = 0;
        if(isset($jmcMap[$jmcId])){
            $jurosLinha = (float)$jmcMap[$jmcId]['JUROS_MENSAL'] * $par;
        }
        $parcelas = max($parcelas,$par);
        $jurosValor += $val * ($jurosLinha/100);
    }
    $juros = $valorTotal>0 ? ($jurosValor/$valorTotal)*100 : 0;

    $pagamentos = [];
    foreach($jurosSel as $i => $jmcId){
        $val = $valoresPag[$i] ?? '';
        $par = $parcelasPag[$i] ?? '1';
        $venc = $vencPag[$i] ?? '';
        if(!$jmcId || $val==='' || !$venc){
            $error = 'Informe método, valor e vencimento para todos os pagamentos';
            break;
        }
        $metId = isset($jmcMap[$jmcId]) ? $jmcMap[$jmcId]['METODO_ID'] : null;
        $cond  = isset($jmcMap[$jmcId]) ? $jmcMap[$jmcId]['CONDICAO_ID'] : null;
        $pagamentos[] = [
            'metodo_id' => $metId,
            'condicao_id' => $cond,
            'valor' => (float)str_replace(',', '.', $val),
            'parcelas' => (int)$par ?: 1,
            'vencimento' => $venc
        ];
    }
    $somaPag = array_sum(array_column($pagamentos,'valor'));
    if(empty($error) && round($somaPag,2) != round($valorTotal,2)){
        $error = 'A soma dos pagamentos deve ser igual ao Valor Total';
    }

    if(empty($error)){

    $pdo->beginTransaction();
    try {
        $hasLiquido  = columnExists($pdo,'VENDAS','VALOR_LIQUIDO');
        $hasParc     = columnExists($pdo,'VENDAS','NUMERO_PARCELAS');
        $hasForma    = columnExists($pdo,'VENDAS','FORMA_PAGAMENTO');
        $hasDataVenda= columnExists($pdo,'VENDAS','DATA_VENDA');
        $cols = ['ID_CLIENTE','ID_USUARIO','ID_CONDICAO_PAGAMENTO'];
        $vals = [
            $_SESSION['venda']['ID_CLIENTE'],
            $_SESSION['venda']['ID_USUARIO'],
            $idCond
        ];
        if($hasDataVenda){
            $cols[]='DATA_VENDA';
            $vals[]=$dataVenda;
        }
        if($metColVenda){
            $cols[] = $metColVenda;
            $vals[] = $idMet;
        }
        $cols[] = 'JUROS_APLICADO';
        $vals[] = $juros;
        if($hasParc){ $cols[]='NUMERO_PARCELAS'; $vals[]=$parcelas; }
        if($hasForma){ $cols[]='FORMA_PAGAMENTO'; $vals[]=$parcelas>1?'PARCELADO':'À VISTA'; }
        $cols = array_merge($cols,['VALOR_VENDA','VALOR_TOTAL']);
        $vals = array_merge($vals,[$valorVenda,$valorTotal]);
        if($hasLiquido){ $cols[]='VALOR_LIQUIDO'; $vals[]=$valorLiquidoCalc; }
        $cols = array_merge($cols,[
            'ID_FRETE','DATA_ENTREGA','DESCONTO','TELEFONE_CONTATO','RESPONSAVEL_CONTATO',
            'CEP_ENTREGA','RUA_ENTREGA','BAIRRO_ENTREGA','ID_CIDADE','OBSERVACAO','STATUS','ID_EMPRESA'
        ]);
        $vals = array_merge($vals,[
            $idFrete,$dataEntrega,$descGeral,$telContato,$respContato,$cepEntrega,$ruaEntrega,
            $bairroEntrega,$idCidade,null,'PENDENTE',$_SESSION['venda']['ID_EMPRESA']
        ]);
        $place = implode(',', array_fill(0,count($cols),'?'));
        $sql = "INSERT INTO VENDAS (".implode(',', $cols).") VALUES ($place)";
        $pdo->prepare($sql)->execute($vals);
        $idVenda = $pdo->lastInsertId();
        if(!$idVenda){
            throw new Exception('falha ao inserir venda');
        }
        $colsItem = ['ID_VENDA','ID_PRODUTO','QUANTIDADE','VALOR_UNITARIO','DESCONTO'];
        $placeItem = '?,?,?,?,?';
        foreach(['DIOPTRIA','DNP','ALTURA','OBSERVACAO'] as $c){
            if(columnExists($pdo,'ITENS_VENDA',$c)){ $colsItem[]=$c; $placeItem .= ',?'; }
        }
        $stmtItem = $pdo->prepare("INSERT INTO ITENS_VENDA (".implode(',', $colsItem).") VALUES ($placeItem)");
        foreach($itens as $it){
            $valsItem = [$idVenda,$it['ID_PRODUTO'],$it['QUANTIDADE'],$it['VALOR_UNITARIO'],$it['DESCONTO']];
            foreach(['DIOPTRIA','DNP','ALTURA','OBSERVACAO'] as $c){
                if(in_array($c,$colsItem)) $valsItem[] = $it[$c] ?? '';
            }
            $stmtItem->execute($valsItem);
        }

        $tablePag = tableExists($pdo,'VENDAS_PAGAMENTOS')
            ? 'VENDAS_PAGAMENTOS'
            : (tableExists($pdo,'VENDAS_PAGAMENTO') ? 'VENDAS_PAGAMENTO' : null);
        if(!$tablePag){
            throw new Exception('Tabela de pagamento não encontrada');
        }
        $colMetodoPg = 'ID_METODO_PAGAMENTO';
        if(!columnExists($pdo,$tablePag,$colMetodoPg)){
            foreach(['ID_METODO','METODO_ID','ID_METODO_PAG','ID_METODO_PAGTO'] as $alt){
                if(columnExists($pdo,$tablePag,$alt)){
                    $colMetodoPg = $alt;
                    break;
                }
            }
        }
        $hasParcPg = columnExists($pdo,$tablePag,'PARCELAS');
        $vencCol = null;
        if(columnExists($pdo,$tablePag,'DATA_VENC_PARCELA')) $vencCol = 'DATA_VENC_PARCELA';
        elseif(columnExists($pdo,$tablePag,'DATA_VENCIMENTO_PARCELA')) $vencCol = 'DATA_VENCIMENTO_PARCELA';
        $hasVencPg = $vencCol !== null;
        $colsPg = "ID_VENDA, {$colMetodoPg}, VALOR";
        $placePg = '?,?,?';
        if($hasParcPg){ $colsPg .= ', PARCELAS'; $placePg .= ',?'; }
        if($hasVencPg){ $colsPg .= ', '.$vencCol; $placePg .= ',?'; }
        $stmtPag = $pdo->prepare("INSERT INTO {$tablePag} ($colsPg) VALUES ($placePg)");
        foreach($pagamentos as $pg){
            $valsPg = [$idVenda,$pg['metodo_id'],$pg['valor']];
            if($hasParcPg) $valsPg[] = $pg['parcelas'];
            if($hasVencPg) $valsPg[] = $pg['vencimento'];
            $stmtPag->execute($valsPg);
        }

        if(tableExists($pdo,'FIDELIDADE_CLIENTE')){
            $pts = $valorTotal >= 500 ? 100 : 0;
            if($pts > 0){
                $stmtF = $pdo->prepare('INSERT INTO FIDELIDADE_CLIENTE (ID_CLIENTE,PONTOS,ULTIMA_ATUALIZACAO) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE PONTOS=PONTOS+VALUES(PONTOS), ULTIMA_ATUALIZACAO=NOW()');
                $stmtF->execute([$_SESSION['venda']['ID_CLIENTE'],$pts]);
            }
        }
        $pdo->commit();
        unset($_SESSION['venda']);
        header('Location: vendas_list.php');
        exit;
    } catch(Exception $e){
        $pdo->rollBack();
        $error = 'Erro ao salvar venda: ' . $e->getMessage();
    }
}
}

if(isset($_POST['voltar'])){
    header('Location: step2.php');
    exit;
}

$fretes    = $pdo->query("SELECT ID, DESCRICAO, VALOR FROM FRETE ORDER BY DESCRICAO")->fetchAll(PDO::FETCH_ASSOC);
$cidades   = $pdo->query("SELECT ID, CONCAT(NOME,'/',UF) AS NOME FROM CIDADE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);

$valorVenda = 0; $valorDescItens=0; foreach($itens as $it){
    $valorVenda += $it['QUANTIDADE']*$it['VALOR_UNITARIO'];
    $valorDescItens += $it['DESCONTO'];
}
$freteValor = 0;
$distanciaCalc = 0;
if($idFrete){
    $st = $pdo->prepare("SELECT VALOR FROM FRETE WHERE ID=?");
    $st->execute([$idFrete]);
    $valorKm = (float)$st->fetchColumn();
    if($cepEmpresa && $cepEntrega){
        $distanciaCalc = distanciaKm($cepEmpresa, preg_replace('/\D/','', $cepEntrega));
        $freteValor = $valorKm * $distanciaCalc;
    }
}
$juros = 0;
$parcelas = 1;
$valorTotal = $valorVenda - $valorDescItens - $descGeral + $freteValor;
$valorLiquidoCalc = $valorTotal;
$jurosValor = 0;
foreach($jurosSel as $i=>$jmcId){
    $val  = isset($valoresPag[$i]) ? (float)str_replace(',', '.', $valoresPag[$i]) : 0;
    $par  = isset($parcelasPag[$i]) ? (int)$parcelasPag[$i] : 1;
    $jurosLinha = 0;
    if(isset($jmcMap[$jmcId])){
        $jurosLinha = (float)$jmcMap[$jmcId]['JUROS_MENSAL'] * $par;
    }
    $parcelas = max($parcelas,$par);
    $jurosValor += $val*($jurosLinha/100);
}
$juros = $valorTotal>0 ? ($jurosValor/$valorTotal)*100 : 0;

$pageTitle = 'Pagamento';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Condição e Pagamento</h2>
  <?php if(!empty($error)): ?>
    <p class="text-red-600 mb-2"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="post" class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="formStep3">
    <div>
      <label class="block mb-1">Frete</label>
      <select name="id_frete" class="border p-2 w-full rounded" onchange="calcTot()">
        <option value="" data-valor="0">Selecione</option>
        <?php foreach($fretes as $f): ?>
          <option value="<?= $f['ID'] ?>" data-valor="<?= $f['VALOR'] ?>" <?= $idFrete==$f['ID']?'selected':'' ?>><?= htmlspecialchars($f['DESCRICAO']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Data de Entrega</label>
      <input type="date" name="data_entrega" value="<?= htmlspecialchars($dataEntrega) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Telefone Contato</label>
      <input type="text" name="telefone_contato" value="<?= htmlspecialchars($telContato) ?>" class="border p-2 w-full rounded" data-mask="telefone">
    </div>
    <div>
      <label class="block mb-1">Responsável Contato</label>
      <input type="text" name="responsavel_contato" value="<?= htmlspecialchars($respContato) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">CEP Entrega</label>
      <input type="text" name="cep_entrega" value="<?= htmlspecialchars($cepEntrega) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Rua Entrega</label>
      <input type="text" name="rua_entrega" value="<?= htmlspecialchars($ruaEntrega) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Bairro Entrega</label>
      <input type="text" name="bairro_entrega" value="<?= htmlspecialchars($bairroEntrega) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Cidade</label>
      <select name="id_cidade" class="border p-2 w-full rounded">
        <option value="">Selecione</option>
        <?php foreach($cidades as $ci): ?>
          <option value="<?= $ci['ID'] ?>" <?= $idCidade==$ci['ID']?'selected':'' ?>><?= htmlspecialchars($ci['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
    <div>
      <label class="block mb-1">Desconto Geral</label>
      <input type="text" name="desconto_geral" value="<?= htmlspecialchars($descGeral) ?>" class="border p-2 w-full rounded" oninput="calcTot()">
    </div>
    <div class="md:col-span-2">
      <p>Valor Bruto: R$ <span id="vVenda"><?= number_format($valorVenda,2,',','.') ?></span></p>
      <p>Desconto Itens: R$ <span id="vDescItens"><?= number_format($valorDescItens,2,',','.') ?></span></p>
      <p>Desconto Geral: R$ <span id="vDescGeral"><?= number_format($descGeral,2,',','.') ?></span></p>
      <p>Distância: <span id="distanciaKm"><?= number_format($distanciaCalc,2,',','.') ?></span> km</p>
      <p>Frete: R$ <span id="vFrete"><?= number_format($freteValor,2,',','.') ?></span></p>
      <span id="vJuros" class="hidden"><?= number_format($juros,2,',','.') ?></span>
      <p class="hidden">Valor Total: R$ <span id="vTotal"><?= number_format($valorTotal,2,',','.') ?></span></p>
      <p class="text-lg font-bold text-green-800">Valor Líquido: R$ <span id="vLiquido"><?= number_format($valorLiquidoCalc,2,',','.') ?></span></p>
    </div>
    <div class="md:col-span-2 mt-4 mb-8">
      <label class="block mb-1">Pagamentos</label>
      <div class="flex items-center gap-2 font-semibold mb-1">
        <div class="w-72">Método/Condição</div>
        <div class="w-32">Valor</div>
        <div class="w-20">Parcelas</div>
        <div class="w-36">Vencimento</div>
      </div>
      <div id="pagamentos" class="space-y-2"></div>
      <div class="mt-2 text-right">Total pagamentos: R$ <span id="totPagamentos">0,00</span></div>
      <button type="button" id="addPagamento" class="mt-2 px-3 py-1 bg-gray-300 rounded">Adicionar pagamento</button>
      <template id="pgTemplate">
        <div class="pagamento flex items-center gap-2 bg-gray-100 p-2 rounded">
          <select name="juros_metodo_condicao[]" class="border p-2 rounded w-72">
            <option value="">Selecione</option>
            <?php foreach($jmcDados as $j): ?>
              <option value="<?= $j['ID'] ?>" data-met="<?= $j['METODO_ID'] ?>" data-juros="<?= $j['JUROS_MENSAL'] ?>" data-parcelas="<?= $j['PARCELAS'] ?>">
                <?= htmlspecialchars($j['METODO'].' - '.$j['CONDICAO']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="valores_pagamento[]" class="border p-2 rounded w-32 money" data-mask="money">
          <input type="number" name="parcelas_pagamento[]" value="1" min="1" class="border p-2 rounded w-20">
          <input type="date" name="vencimentos_pagamento[]" class="border p-2 rounded w-36">
          <button type="button" class="removePagamento text-red-600 px-2">Remover</button>
        </div>
      </template>
    </div>
    <div class="md:col-span-2">
      <button name="voltar" class="px-4 py-2 bg-gray-300 rounded mr-2">Voltar</button>
      <button name="finalizar" class="bg-primary text-white px-4 py-2 rounded">Finalizar</button>
  <a href="cancelar_venda.php" class="ml-3 text-red-600">Cancelar</a>
    </div>
  </form>
<script>
const cepOrigem = '<?= $cepEmpresa ?>';
let freteAtual = parseFloat("<?= number_format($freteValor,2,'.','.') ?>");
const pagamentosDiv=document.getElementById('pagamentos');
const template=document.getElementById('pgTemplate').content.firstElementChild;
function formatValor(inp){
  const v=parseFloat(inp.value.replace(',', '.'));
  if(!isNaN(v)) inp.value=v.toFixed(2).replace('.', ',');
}
function addPagamento(){
  const clone=template.cloneNode(true);
  clone.querySelector('.removePagamento').addEventListener('click',()=>{
    if(window.jQuery&&jQuery.fn.select2){
      jQuery(clone).find('select').select2('destroy');
    }
    clone.remove();
    fetchJuros();
    updatePagamentoTotal();
  });
  const valInput = clone.querySelector('input[name="valores_pagamento[]"]');
  valInput.addEventListener('blur',fetchJuros);
  valInput.addEventListener('input',updatePagamentoTotal);
  const val=clone.querySelector('input[name="valores_pagamento[]"]');
  val.addEventListener('blur',()=>formatValor(val));
  if(window.jQuery&&jQuery.fn.mask){
    jQuery(val).mask('#.##0,00',{reverse:true});
  }
  const parcInput = clone.querySelector('input[name="parcelas_pagamento[]"]');
  const sel = clone.querySelector('select[name="juros_metodo_condicao[]"]');
  sel.addEventListener('change',()=>{
    const opt = sel.selectedOptions[0];
    const parc = opt ? opt.dataset.parcelas : '';
    if(parc) parcInput.value = parc;
    fetchJuros();
  });
  parcInput.addEventListener('change',fetchJuros);
  const vencInput = clone.querySelector('input[name="vencimentos_pagamento[]"]');
  vencInput.value = new Date().toISOString().slice(0,10);
  vencInput.addEventListener('change',updatePagamentoTotal);
  pagamentosDiv.appendChild(clone);
  if(window.jQuery&&jQuery.fn.select2){
    jQuery(clone).find('select').select2({width:'100%'});
  }
  updatePagamentoTotal();
}
function fetchJuros(){
  const rows=[...pagamentosDiv.querySelectorAll('.pagamento')];
  const promises=rows.map(r=>{
    const sel=r.querySelector('select[name="juros_metodo_condicao[]"]');
    const juros=parseFloat(sel.selectedOptions[0]?.dataset.juros||'0');
    const v=parseFloat((r.querySelector('input[name="valores_pagamento[]"]').value||'').replace(',', '.'))||0;
    const p=parseInt(r.querySelector('input[name="parcelas_pagamento[]"]').value)||1;
    if(!sel.value) return Promise.resolve(0);
    return Promise.resolve(v*((juros||0)*p/100));
  });
  Promise.all(promises).then(vals=>{
    const jurosValor=vals.reduce((a,b)=>a+b,0);
    const tot=parseFloat(document.getElementById('vTotal').textContent.replace(',', '.'))||0;
    const perc=tot? (jurosValor*100/tot) : 0;
    document.getElementById('vJuros').textContent=perc.toFixed(2);
    calcTot();
    updatePagamentoTotal();
  });
}
function updateFrete(){
  const cep=document.querySelector('[name=cep_entrega]').value.replace(/\D/g,'');
  const sel=document.querySelector('[name=id_frete]');
  const val=sel.selectedOptions[0].dataset.valor||0;
  if(!sel.value||!cep||!cepOrigem){
    freteAtual=0;
    document.getElementById('distanciaKm').textContent='0';
    calcTot();
    return;
  }
  fetch(`calcula_distancia.php?orig=${cepOrigem}&dest=${cep}&valor=${val}`)
    .then(r=>r.json())
    .then(d=>{
      freteAtual=d.valor?parseFloat(d.valor):0;
      document.getElementById('distanciaKm').textContent=d.distancia?parseFloat(d.distancia).toFixed(2):'0';
      calcTot();
    });
}
function calcTot(){
  const bruto=parseFloat(document.getElementById('vVenda').textContent.replace(',', '.'))||0;
  const descItens=parseFloat(document.getElementById('vDescItens').textContent.replace(',', '.'))||0;
  const descGeral=parseFloat(document.querySelector('[name=desconto_geral]').value.replace(',', '.'))||0;
  document.getElementById('vDescGeral').textContent=descGeral.toFixed(2);
  document.getElementById('vFrete').textContent=freteAtual.toFixed(2);
  let total=bruto-descItens-descGeral+freteAtual;
  document.getElementById('vTotal').textContent=total.toFixed(2);
  const liquido=total;
  document.getElementById('vLiquido').textContent=liquido.toFixed(2);
}

function updatePagamentoTotal(){
  const vals=[...document.querySelectorAll('[name="valores_pagamento[]"]')]
    .map(i=>parseFloat(i.value.replace(',', '.'))||0);
  const soma=vals.reduce((a,b)=>a+b,0);
  const span=document.getElementById('totPagamentos');
  if(span) span.textContent=soma.toFixed(2).replace('.', ',');
}
function validatePagamentos(){
  const valores=[...document.querySelectorAll("[name=valores_pagamento[]]")].map(i=>{formatValor(i);return parseFloat(i.value.replace(",", "."))||0;});
  const parcelas=[...document.querySelectorAll("[name=parcelas_pagamento[]]")].map(i=>parseInt(i.value)||1);
  const conds=[...document.querySelectorAll("[name=juros_metodo_condicao[]]")].map(i=>i.value);
  const vencs=[...document.querySelectorAll('[name=vencimentos_pagamento[]]')].map(i=>i.value);
  const soma=valores.reduce((a,b)=>a+b,0);
  const total=parseFloat(document.getElementById("vTotal").textContent.replace(",", "."))||0;
  if(Math.abs(soma-total)>0.01){
    alert("A soma dos pagamentos deve ser igual ao Valor Total");
    return false;
  }
  if(parcelas.some(p=>p<1) || conds.some(c=>!c) || vencs.some(v=>!v)){
    alert("Quantidade de parcelas, condição ou vencimento inválido");
    return false;
  }
  return true;
}
document.getElementById('addPagamento').addEventListener('click',addPagamento);
document.querySelector('[name=desconto_geral]').addEventListener('input',calcTot);
document.querySelector('[name=id_frete]').addEventListener('change',updateFrete);
document.querySelector('[name=cep_entrega]').addEventListener('blur',updateFrete);
document.getElementById('formStep3').addEventListener('submit',e=>{if(!validatePagamentos()) e.preventDefault();});
document.addEventListener('DOMContentLoaded',()=>{addPagamento();fetchJuros();updateFrete();updatePagamentoTotal();});
</script>
</div>
<?php include 'footer.php'; ?>

