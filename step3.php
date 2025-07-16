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

// valores default dos campos
$idCond  = $_POST['id_condicao_pagamento'] ?? '';
$formasPag   = $_POST['formas_pagamento'] ?? [];
$valoresPag  = $_POST['valores_pagamento'] ?? [];
$idMet   = $formasPag[0] ?? '';
$idFrete = $_POST['id_frete'] ?? '';
$dataEntrega = $_POST['data_entrega'] ?? '';
$telContato    = $_POST['telefone_contato'] ?? $cliente['CONTATO'];
$respContato   = $_POST['responsavel_contato'] ?? '';
$cepEntrega    = $_POST['cep_entrega'] ?? $cliente['CEP'];
$ruaEntrega    = $_POST['rua_entrega'] ?? $cliente['RUA'];
$bairroEntrega = $_POST['bairro_entrega'] ?? $cliente['BAIRRO'];
$idCidade      = $_POST['id_cidade'] ?? $cliente['ID_CIDADE'];
$descGeral     = str_replace(',', '.', $_POST['desconto_geral'] ?? '0');
$dataVenc      = trim($_POST['data_vencimento'] ?? '');
if(empty($dataVenc)){
    $dataVenc = date('Y-m-d');
}

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
    if($idFrete){
        $st = $pdo->prepare("SELECT valor FROM FRETE WHERE id=?");
        $st->execute([$idFrete]);
        $freteValor = (float)$st->fetchColumn();
    }

    // juros aplicado conforme método e condição
    $juros = 0;
    $parcelas = 1;
    if($idMet && $idCond){
        $st = $pdo->prepare(
            "SELECT c.PARCELAS, j.JUROS_MENSAL
               FROM CONDICAO_PAGAMENTO c
               LEFT JOIN JUROS_METODO_CONDICAO j
                 ON j.ID_CONDICAO = c.ID
                AND j.ID_METODO_PAGAMENTO = ?
              WHERE c.ID = ?"
        );
        $st->execute([$idMet, $idCond]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if($row){
            $parcelas = (int)$row['PARCELAS'];
            $juros = (float)$row['JUROS_MENSAL'] * $parcelas;
        }
    }

    $valorTotal = $valorVenda - $valorDescItens - $descGeral + $freteValor;
    $valorLiquidoCalc = $valorTotal * (1 - $juros/100);

    $pagamentos = [];
    foreach($formasPag as $i => $metId){
        $val = $valoresPag[$i] ?? '';
        if(!$metId || $val===''){
            $error = 'Informe método e valor para todos os pagamentos';
            break;
        }
        $pagamentos[] = [
            'metodo_id' => $metId,
            'valor' => (float)str_replace(',', '.', $val)
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
        $hasVenc     = columnExists($pdo,'VENDAS','DATA_VENCIMENTO_PARCELA');
        $hasForma    = columnExists($pdo,'VENDAS','FORMA_PAGAMENTO');
        $cols = [
            'ID_CLIENTE','ID_USUARIO','ID_CONDICAO_PAGAMENTO','ID_METODO_PAGAMENTO','JUROS_APLICADO'
        ];
        $vals = [
            $_SESSION['venda']['ID_CLIENTE'],
            $_SESSION['venda']['ID_USUARIO'],
            $idCond,
            $idMet,
            $juros
        ];
        if($hasParc){ $cols[]='NUMERO_PARCELAS'; $vals[]=$parcelas; }
        if($hasVenc){ $cols[]='DATA_VENCIMENTO_PARCELA'; $vals[]=$dataVenc; }
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
        $stmtItem = $pdo->prepare("INSERT INTO ITENS_VENDA (ID_VENDA,ID_PRODUTO,QUANTIDADE,VALOR_UNITARIO,DESCONTO) VALUES (?,?,?,?,?)");
        $stmtEstoque = $pdo->prepare("UPDATE PRODUTO SET ESTOQUE_ATUAL = ESTOQUE_ATUAL - ? WHERE ID = ?");
        foreach($itens as $it){
            $stmtItem->execute([$idVenda,$it['ID_PRODUTO'],$it['QUANTIDADE'],$it['VALOR_UNITARIO'],$it['DESCONTO']]);
            $stmtEstoque->execute([$it['QUANTIDADE'],$it['ID_PRODUTO']]);
        }

        $stmtPag = $pdo->prepare("INSERT INTO VENDAS_PAGAMENTOS (ID_VENDA, ID_METODO_PAGAMENTO, VALOR) VALUES (?,?,?)");
        foreach($pagamentos as $pg){
            $stmtPag->execute([$idVenda,$pg['metodo_id'],$pg['valor']]);
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

$condicoes = $pdo->query("SELECT ID, NOME FROM CONDICAO_PAGAMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$metodos   = $pdo->query("SELECT ID, NOME FROM METODO_PAGAMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$fretes    = $pdo->query("SELECT ID, DESCRICAO, VALOR FROM FRETE ORDER BY DESCRICAO")->fetchAll(PDO::FETCH_ASSOC);
$cidades   = $pdo->query("SELECT ID, CONCAT(NOME,'/',UF) AS NOME FROM CIDADE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);

$valorVenda = 0; $valorDescItens=0; foreach($itens as $it){
    $valorVenda += $it['QUANTIDADE']*$it['VALOR_UNITARIO'];
    $valorDescItens += $it['DESCONTO'];
}
$freteValor = 0;
if($idFrete){
    $st = $pdo->prepare("SELECT VALOR FROM FRETE WHERE ID=?");
    $st->execute([$idFrete]);
    $freteValor = (float)$st->fetchColumn();
}
$juros = 0;
if($idMet && $idCond){
    $st = $pdo->prepare(
        "SELECT c.PARCELAS, j.JUROS_MENSAL
           FROM CONDICAO_PAGAMENTO c
           LEFT JOIN JUROS_METODO_CONDICAO j
             ON j.ID_CONDICAO = c.ID
            AND j.ID_METODO_PAGAMENTO = ?
          WHERE c.ID = ?"
    );
    $st->execute([$idMet, $idCond]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if($row){
        $parc = (int)$row['PARCELAS'];
        $juros = (float)$row['JUROS_MENSAL'] * $parc;
    }
}
$valorTotal = $valorVenda - $valorDescItens - $descGeral + $freteValor;
$valorLiquidoCalc = $valorTotal * (1 - $juros/100);

$pageTitle = 'Pagamento';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Condição e Pagamento</h2>
  <?php if(!empty($error)): ?>
    <p class="text-red-600 mb-2"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="formStep3">
    <div>
      <label class="block mb-1">Condição de Pagamento</label>
      <select name="id_condicao_pagamento" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($condicoes as $c): ?>
          <option value="<?= $c['ID'] ?>" <?= $idCond==$c['ID']?'selected':'' ?>><?= htmlspecialchars($c['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block mb-1">Pagamentos</label>
      <div id="pagamentos" class="space-y-2"></div>
      <button type="button" id="addPagamento" class="mt-2 px-3 py-1 bg-gray-300 rounded">Adicionar pagamento</button>
      <template id="pgTemplate">
        <div class="pagamento flex items-center gap-2 bg-gray-100 p-2 rounded">
          <select name="formas_pagamento[]" class="border p-2 rounded w-56">
            <option value="">Selecione</option>
            <?php foreach($metodos as $m): ?>
              <option value="<?= $m['ID'] ?>"><?= htmlspecialchars($m['NOME']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="valores_pagamento[]" class="border p-2 rounded w-32">
          <button type="button" class="removePagamento text-red-600 px-2">Remover</button>
        </div>
      </template>
    </div>
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
      <input type="text" name="telefone_contato" value="<?= htmlspecialchars($telContato) ?>" class="border p-2 w-full rounded">
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
      <label class="block mb-1">Data Vencimento 1ª Parcela</label>
      <input type="date" name="data_vencimento" value="<?= htmlspecialchars($dataVenc) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Desconto Geral</label>
      <input type="text" name="desconto_geral" value="<?= htmlspecialchars($descGeral) ?>" class="border p-2 w-full rounded" oninput="calcTot()">
    </div>
    <div class="md:col-span-2">
      <p>Valor Bruto: R$ <span id="vVenda"><?= number_format($valorVenda,2,',','.') ?></span></p>
      <p>Desconto Itens: R$ <span id="vDescItens"><?= number_format($valorDescItens,2,',','.') ?></span></p>
      <p>Desconto Geral: R$ <span id="vDescGeral"><?= number_format($descGeral,2,',','.') ?></span></p>
      <p>Frete: R$ <span id="vFrete"><?= number_format($freteValor,2,',','.') ?></span></p>
      <span id="vJuros" class="hidden"><?= number_format($juros,2,',','.') ?></span>
      <p>Valor Total: R$ <span id="vTotal"><?= number_format($valorTotal,2,',','.') ?></span></p>
      <p>Valor Líquido: R$ <span id="vLiquido"><?= number_format($valorLiquidoCalc,2,',','.') ?></span></p>
    </div>
    <div class="md:col-span-2">
      <button name="voltar" class="px-4 py-2 bg-gray-300 rounded mr-2">Voltar</button>
      <button name="finalizar" class="bg-primary text-white px-4 py-2 rounded">Finalizar</button>
  <a href="cancelar_venda.php" class="ml-3 text-red-600">Cancelar</a>
    </div>
  </form>
<script>
const pagamentosDiv=document.getElementById('pagamentos');
const template=document.getElementById('pgTemplate').content.firstElementChild;
function formatValor(inp){
  const v=parseFloat(inp.value.replace(',', '.'));
  if(!isNaN(v)) inp.value=v.toFixed(2).replace('.', ',');
}
function addPagamento(){
  const clone=template.cloneNode(true);
  clone.querySelector('.removePagamento').addEventListener('click',()=>{clone.remove();fetchJuros();});
  clone.querySelector('select').addEventListener('change',fetchJuros);
  const val=clone.querySelector('input[name="valores_pagamento[]"]');
  val.addEventListener('blur',()=>formatValor(val));
  pagamentosDiv.appendChild(clone);
  if(window.jQuery&&jQuery.fn.select2){
    jQuery(clone).find('select').select2({width:'100%'});
  }
}
function fetchJuros(){
  const first=pagamentosDiv.querySelector('select[name="formas_pagamento[]"]');
  const met=first?first.value:'';
  const cond=document.querySelector('[name=id_condicao_pagamento]').value;
  if(!met||!cond){document.getElementById('vJuros').textContent='0';calcTot();return;}
  fetch(`get_juros.php?met=${met}&cond=${cond}`)
    .then(r=>r.json()).then(d=>{document.getElementById('vJuros').textContent=d.juros||0;calcTot();});
}
function calcTot(){
  const bruto=parseFloat(document.getElementById('vVenda').textContent.replace(',', '.'))||0;
  const descItens=parseFloat(document.getElementById('vDescItens').textContent.replace(',', '.'))||0;
  const descGeral=parseFloat(document.querySelector('[name=desconto_geral]').value.replace(',', '.'))||0;
  document.getElementById('vDescGeral').textContent=descGeral.toFixed(2);
  const frete=parseFloat(document.querySelector('[name=id_frete]').selectedOptions[0].dataset.valor||0);
  const juros=parseFloat(document.getElementById('vJuros').textContent)||0;
  document.getElementById('vFrete').textContent=frete.toFixed(2);
  let total=bruto-descItens-descGeral+frete;
  document.getElementById('vTotal').textContent=total.toFixed(2);
  let liquido=total*(1-juros/100);
  document.getElementById('vLiquido').textContent=liquido.toFixed(2);
}
function validatePagamentos(){
  const valores=[...document.querySelectorAll('[name="valores_pagamento[]"]')].map(i=>{
    formatValor(i);
    return parseFloat(i.value.replace(',', '.'))||0;
  });
  const soma=valores.reduce((a,b)=>a+b,0);
  const total=parseFloat(document.getElementById('vTotal').textContent.replace(',', '.'))||0;
  if(Math.abs(soma-total)>0.01){
    alert('A soma dos pagamentos deve ser igual ao Valor Total');
    return false;
  }
  return true;
}
document.getElementById('addPagamento').addEventListener('click',addPagamento);
document.querySelector('[name=id_condicao_pagamento]').addEventListener('change',fetchJuros);
document.querySelector('[name=desconto_geral]').addEventListener('input',calcTot);
document.getElementById('formStep3').addEventListener('submit',e=>{if(!validatePagamentos()) e.preventDefault();});
document.addEventListener('DOMContentLoaded',()=>{addPagamento();fetchJuros();});
</script>
</div>
<?php include 'footer.php'; ?>

