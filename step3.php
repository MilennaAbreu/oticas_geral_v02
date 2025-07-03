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
$idMet   = $_POST['id_metodo_pagamento'] ?? '';
$idFrete = $_POST['id_frete'] ?? '';
$dataEntrega = $_POST['data_entrega'] ?? '';
$dataVenc    = $_POST['data_vencimento'] ?? '';
$descontoGeral = isset($_POST['desconto_geral']) ? str_replace(',', '.', $_POST['desconto_geral']) : '0';
$telContato    = $_POST['telefone_contato'] ?? $cliente['CONTATO'];
$respContato   = $_POST['responsavel_contato'] ?? '';
$cepEntrega    = $_POST['cep_entrega'] ?? $cliente['CEP'];
$ruaEntrega    = $_POST['rua_entrega'] ?? $cliente['RUA'];
$bairroEntrega = $_POST['bairro_entrega'] ?? $cliente['BAIRRO'];
$idCidade      = $_POST['id_cidade'] ?? $cliente['ID_CIDADE'];

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['finalizar'])){
    $idFrete = $idFrete ?: null;
    $dataEntrega = $dataEntrega ?: null;
    $dataVenc = $dataVenc ?: null;
    $descontoGeral = (float)str_replace(',', '.', $descontoGeral ?: '0');
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

    // descobrir coluna de juros
    $jurosCol = null;
    try{
        $cols = $pdo->query("SHOW COLUMNS FROM JUROS_METODO_CONDICAO")->fetchAll(PDO::FETCH_COLUMN);
        foreach($cols as $c){ if(strtolower($c)=='juros' || strtolower($c)=='juros_aplicado'){ $jurosCol=$c; break; } }
    }catch(Exception $e){ $jurosCol=null; }
    $juros = 0;
    if($jurosCol){
        $stmt = $pdo->prepare("SELECT `{$jurosCol}` FROM JUROS_METODO_CONDICAO WHERE ID_METODO_PAGAMENTO=? AND ID_CONDICAO_PAGAMENTO=?");
        $stmt->execute([$idMet,$idCond]);
        $val = $stmt->fetchColumn();
        if($val!==false) $juros = (float)$val;
    }

    $valorBase = $valorVenda - $valorDescItens - $descontoGeral + $freteValor;
    $valorTotal = $valorBase + ($valorBase * $juros/100);

    $pdo->beginTransaction();
    try {
        $sql = "INSERT INTO VENDAS (ID_CLIENTE,ID_USUARIO,ID_CONDICAO_PAGAMENTO,ID_METODO_PAGAMENTO,JUROS_APLICADO,VALOR_VENDA,VALOR_TOTAL,ID_FRETE,DATA_VENCIMENTO_PARCELA,DATA_ENTREGA,DESCONTO,TELEFONE_CONTATO,RESPONSAVEL_CONTATO,CEP_ENTREGA,RUA_ENTREGA,BAIRRO_ENTREGA,ID_CIDADE,OBSERVACAO,STATUS,ID_EMPRESA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([
            $_SESSION['venda']['ID_CLIENTE'],
            $_SESSION['venda']['ID_USUARIO'],
            $idCond,
            $idMet,
            $juros,
            $valorVenda,
            $valorTotal,
            $idFrete,
            $dataVenc,
            $dataEntrega,
            $descontoGeral,
            $telContato,
            $respContato,
            $cepEntrega,
            $ruaEntrega,
            $bairroEntrega,
            $idCidade,
            null,
            'PENDENTE',
            $_SESSION['venda']['ID_EMPRESA']
        ]);
        $idVenda = $pdo->lastInsertId();
        if(!$idVenda){
            throw new Exception('falha ao inserir venda');
        }
        $stmtItem = $pdo->prepare("INSERT INTO ITENS_VENDA (ID_VENDA,ID_PRODUTO,QUANTIDADE,VALOR_UNITARIO,DESCONTO) VALUES (?,?,?,?,?)");
        foreach($itens as $it){
            $stmtItem->execute([$idVenda,$it['ID_PRODUTO'],$it['QUANTIDADE'],$it['VALOR_UNITARIO'],$it['DESCONTO']]);
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
    try{
        $cols = $pdo->query("SHOW COLUMNS FROM JUROS_METODO_CONDICAO")->fetchAll(PDO::FETCH_COLUMN);
        $jCol = null;
        foreach($cols as $c){ if(strtolower($c)=='juros' || strtolower($c)=='juros_aplicado'){ $jCol=$c; break; } }
        if($jCol){
            $st = $pdo->prepare("SELECT `{$jCol}` FROM JUROS_METODO_CONDICAO WHERE ID_METODO_PAGAMENTO=? AND ID_CONDICAO_PAGAMENTO=?");
            $st->execute([$idMet,$idCond]);
            $val = $st->fetchColumn();
            if($val!==false) $juros=(float)$val;
        }
    }catch(Exception $e){ $juros=0; }
}
$valorBase = $valorVenda - $valorDescItens - (float)$descontoGeral + $freteValor;
$valorLiquidoCalc = $valorBase + ($valorBase * $juros/100);

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
    <div>
      <label class="block mb-1">Método de Pagamento</label>
      <select name="id_metodo_pagamento" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($metodos as $m): ?>
          <option value="<?= $m['ID'] ?>" <?= $idMet==$m['ID']?'selected':'' ?>><?= htmlspecialchars($m['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
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
      <label class="block mb-1">Data Vencimento Parcela</label>
      <input type="date" name="data_vencimento" value="<?= htmlspecialchars($dataVenc) ?>" class="border p-2 w-full rounded" required>
    </div>
    <div>
      <label class="block mb-1">Desconto Geral</label>
      <input type="text" name="desconto_geral" id="descontoGeral" value="<?= htmlspecialchars($descontoGeral) ?>" class="border p-2 w-full rounded" onchange="calcTot()">
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
    <div class="md:col-span-2">
      <p>Valor Venda: R$ <span id="vVenda"><?= number_format($valorVenda,2,',','.') ?></span></p>
      <p>Desconto Itens: R$ <span id="vDescItens"><?= number_format($valorDescItens,2,',','.') ?></span></p>
      <p>Desconto Geral: R$ <span id="vDescGeral"><?= number_format($descontoGeral,2,',','.') ?></span></p>
      <p>Frete: R$ <span id="vFrete"><?= number_format($freteValor,2,',','.') ?></span></p>
      <p>Juros (%): <span id="vJuros"><?= number_format($juros,2,',','.') ?></span></p>
      <p>Valor Total: R$ <span id="vTotal"><?= number_format($valorBase,2,',','.') ?></span></p>
      <p>Valor Líquido: R$ <span id="vLiquido"><?= number_format($valorLiquidoCalc,2,',','.') ?></span></p>
    </div>
    <div class="md:col-span-2">
      <button name="voltar" class="px-4 py-2 bg-gray-300 rounded mr-2">Voltar</button>
      <button name="finalizar" class="bg-primary text-white px-4 py-2 rounded">Finalizar</button>
  <a href="cancelar_venda.php" class="ml-3 text-red-600">Cancelar</a>
    </div>
  </form>
</div>
<script>
function fetchJuros(){
  const met=document.querySelector('[name=id_metodo_pagamento]').value;
  const cond=document.querySelector('[name=id_condicao_pagamento]').value;
  if(!met||!cond){document.getElementById('vJuros').textContent='0';calcTot();return;}
  fetch(`get_juros.php?met=${met}&cond=${cond}`)
    .then(r=>r.json()).then(d=>{document.getElementById('vJuros').textContent=d.juros||0;calcTot();});
}
function calcTot(){
  const bruto=parseFloat(document.getElementById('vVenda').textContent.replace(',','.'))||0;
  const descItens=parseFloat(document.getElementById('vDescItens').textContent.replace(',','.'))||0;
  const descGeral=parseFloat(document.getElementById('descontoGeral').value.replace(',','.'))||0;
  const freteSel=document.querySelector('[name=id_frete]');
  const frete=parseFloat(freteSel.selectedOptions[0].dataset.valor||0);
  const juros=parseFloat(document.getElementById('vJuros').textContent)||0;
  document.getElementById('vDescGeral').textContent=descGeral.toFixed(2);
  document.getElementById('vFrete').textContent=frete.toFixed(2);
  let total=bruto-descItens-descGeral+frete;
  document.getElementById('vTotal').textContent=total.toFixed(2);
  let liquido=total+(total*juros/100);
  document.getElementById('vLiquido').textContent=liquido.toFixed(2);
}
document.querySelector('[name=id_metodo_pagamento]').addEventListener('change',fetchJuros);
document.querySelector('[name=id_condicao_pagamento]').addEventListener('change',fetchJuros);
document.addEventListener('DOMContentLoaded',fetchJuros);
</script>
<?php include 'footer.php'; ?>

