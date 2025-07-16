<?php
require_once 'config.php';
require_once 'auth.php';

if(!isset($_SESSION['venda']['ID_EMPRESA'])){
    header('Location: step1.php');
    exit;
}

$itens = $_SESSION['venda']['itens'] ?? [];

if($_SERVER['REQUEST_METHOD']==='POST'){
    $produtos = $_POST['produto_id'] ?? [];
    $qtds     = $_POST['quantidade'] ?? [];
    $valores  = $_POST['valor_unitario'] ?? [];
    $descontos= $_POST['desconto'] ?? [];
    $itens = [];
    for($i=0; $i<count($produtos); $i++){
        if(!$produtos[$i]) continue;
        $itens[] = [
            'ID_PRODUTO'    => $produtos[$i],
            'QUANTIDADE'    => (float)$qtds[$i],
            'VALOR_UNITARIO'=> (float)str_replace(',', '.', $valores[$i]),
            'DESCONTO'      => (float)str_replace(',', '.', $descontos[$i])
        ];
    }
    $_SESSION['venda']['itens'] = $itens;
    header('Location: step3.php');
    exit;
}

$empresaId = $_SESSION['venda']['ID_EMPRESA'];
$stmtProd = $pdo->prepare("SELECT p.ID,p.NOME,p.CODIGO,p.UNIDADE_MEDIDA,p.ESTOQUE_ATUAL,p.VALOR_UNITARIO,IFNULL(m.NOME,'') AS MARCA
                            FROM PRODUTO p
                            LEFT JOIN MARCA_PRODUTO m ON p.ID_MARCA = m.ID
                            WHERE p.ID_EMPRESA=?
                            ORDER BY p.NOME");
$stmtProd->execute([$empresaId]);
$produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
$prodMap = [];
foreach($produtos as $p){
    $prodMap[$p['ID']] = $p;
}

$pageTitle = 'Itens da Venda';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Itens da Venda</h2>
  <form method="post" id="formItens">
    <table class="min-w-full border">
      <thead>
        <tr class="bg-gray-200">
          <th class="px-2 py-1">Produto</th>
          <th class="px-2 py-1">Unidade</th>
          <th class="px-2 py-1">Estoque</th>
          <th class="px-2 py-1">Qtd</th>
          <th class="px-2 py-1">Valor Unit</th>
          <th class="px-2 py-1">Desconto</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="itemRows">
        <?php foreach($itens as $i): $p = $prodMap[$i['ID_PRODUTO']] ?? null; ?>
        <tr>
          <td>
            <select name="produto_id[]" class="border p-1 rounded w-full md:w-80" style="min-width:16rem" onchange="updateProd(this)">
              <option value="">Selecione</option>
              <?php foreach($produtos as $prod): ?>
                <option value="<?= $prod['ID'] ?>" data-unidade="<?= $prod['UNIDADE_MEDIDA'] ?>" data-estoque="<?= $prod['ESTOQUE_ATUAL'] ?>" data-valor="<?= $prod['VALOR_UNITARIO'] ?>" <?= $i['ID_PRODUTO']==$prod['ID']?'selected':'' ?>><?= htmlspecialchars($prod['NOME'].' - ('.$prod['MARCA'].') - '.$prod['CODIGO']) ?></option>
              <?php endforeach; ?>
            </select>
          </td>
          <td class="unidade"><?= $p['UNIDADE_MEDIDA'] ?? '' ?></td>
          <td class="estoque"><?= $p['ESTOQUE_ATUAL'] ?? '' ?></td>
          <td><input type="number" name="quantidade[]" value="<?= $i['QUANTIDADE'] ?>" class="border p-1 w-full" onchange="calc()"></td>
          <td><input type="text" name="valor_unitario[]" value="<?= $i['VALOR_UNITARIO'] ?>" class="border p-1 w-full" onchange="calc()"></td>
          <td><input type="text" name="desconto[]" value="<?= $i['DESCONTO'] ?>" class="border p-1 w-full" onchange="calc()"></td>
          <td><button type="button" onclick="removeRow(this)" class="text-red-600">-</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button type="button" class="mt-2 px-2 py-1 bg-gray-300 rounded" onclick="addRow()">+</button>
    <div class="mt-4">
      <div>Valor Bruto: <span id="valorBruto">0.00</span></div>
      <div>Desconto: <span id="valorDesc">0.00</span></div>
      <div>Valor Total: <span id="valorTotal">0.00</span></div>
    </div>
    <div class="mt-4">
      <button class="bg-primary text-white px-4 py-2 rounded">Próximo</button>
      <a href="cancelar_venda.php" class="ml-3 text-red-600">Cancelar</a>
    </div>
  </form>
</div>
<script>
const produtos = <?= json_encode($produtos) ?>;
function optionHtml(p){
  return `<option value="${p.ID}" data-unidade="${p.UNIDADE_MEDIDA}" data-estoque="${p.ESTOQUE_ATUAL}" data-valor="${p.VALOR_UNITARIO}">${p.NOME} - (${p.MARCA}) - ${p.CODIGO}</option>`;
}
function addRow(){
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><select name="produto_id[]" class="border p-1 rounded w-full md:w-80" style="min-width:16rem" onchange="updateProd(this)">
    <option value="">Selecione</option>
    ${produtos.map(optionHtml).join('')}
  </select></td>
  <td class="unidade"></td>
  <td class="estoque"></td>
  <td><input type="number" name="quantidade[]" value="1" class="border p-1 w-full" onchange="calc()"></td>
  <td><input type="text" name="valor_unitario[]" value="0" class="border p-1 w-full" onchange="calc()"></td>
  <td><input type="text" name="desconto[]" value="0" class="border p-1 w-full" onchange="calc()"></td>
  <td><button type="button" onclick="removeRow(this)" class="text-red-600">-</button></td>`;
  document.getElementById('itemRows').appendChild(tr);
  $(tr).find('select').select2({width:'100%'});
  updateProd(tr.querySelector('select'));
}
function removeRow(btn){
  btn.parentElement.parentElement.remove();
  calc();
}
function updateProd(sel){
  const opt = sel.options[sel.selectedIndex];
  const tr = sel.closest('tr');
  tr.querySelector('.unidade').textContent = opt.dataset.unidade || '';
  tr.querySelector('.estoque').textContent = opt.dataset.estoque || '';
  const valor = opt.dataset.valor || '';
  if(valor){
    tr.querySelector('[name="valor_unitario[]"]').value = valor;
  }
  calc();
}
function calc(){
  let bruto=0, desc=0;
  document.querySelectorAll('#itemRows tr').forEach(r=>{
    const q=parseFloat(r.querySelector('[name="quantidade[]"]').value)||0;
    const v=parseFloat(r.querySelector('[name="valor_unitario[]"]').value.replace(',','.'))||0;
    const d=parseFloat(r.querySelector('[name="desconto[]"]').value.replace(',','.'))||0;
    bruto += q*v;
    desc += d;
  });
  document.getElementById('valorBruto').textContent=bruto.toFixed(2);
  document.getElementById('valorDesc').textContent=desc.toFixed(2);
  document.getElementById('valorTotal').textContent=(bruto-desc).toFixed(2);
}
document.querySelectorAll('#itemRows select').forEach(s=>updateProd(s));
calc();
</script>
<?php include 'footer.php'; ?>

