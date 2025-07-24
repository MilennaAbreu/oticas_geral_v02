<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'permissions.php';

$allowed = userCompanies($pdo);
if(!$allowed){ $allowed = []; }

// defaults
$id_cliente = $_POST['id_cliente'] ?? '';
$id_empresa = $_POST['id_empresa'] ?? ($allowed[0] ?? '');
$data_entrada = $_POST['data_entrada'] ?? date('Y-m-d');
$prev_entrega = $_POST['prev_entrega'] ?? date('Y-m-d');
$tempo_previsto = $_POST['tempo_previsto'] ?? '00:30:00';
$valor_hora = $_POST['valor_hora'] ?? '0';
$desconto = $_POST['desconto'] ?? '0';
$id_frete = $_POST['id_frete'] ?? '';
$cep_entrega = $_POST['cep_entrega'] ?? '';
$rua_entrega = $_POST['rua_entrega'] ?? '';
$bairro_entrega = $_POST['bairro_entrega'] ?? '';
$id_cidade = $_POST['id_cidade'] ?? '';
$obs = $_POST['observacao'] ?? '';
$erro = '';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['salvar'])){
    $produtos = $_POST['produto_id'] ?? [];
    $qtds = $_POST['quantidade'] ?? [];
    $items = [];
    if($produtos){
        $in = implode(',', array_fill(0,count($produtos),'?'));
        $st = $pdo->prepare("SELECT ID, VALOR_UNITARIO FROM PRODUTO WHERE ID IN ($in)");
        $st->execute($produtos);
        $map = $st->fetchAll(PDO::FETCH_KEY_PAIR);
        for($i=0;$i<count($produtos);$i++){
            $pid = $produtos[$i];
            $q = (float)$qtds[$i];
            if(!$pid || $q<=0) continue;
            $items[] = ['ID_PRODUTO'=>$pid,'QUANTIDADE'=>$q,'VALOR'=>$map[$pid]??0];
        }
    }
    if(!$id_cliente || !$id_empresa){
        $erro = 'Cliente e empresa são obrigatórios';
    } else {
        $valorProdutos = 0;
        foreach($items as $it){ $valorProdutos += $it['VALOR']*$it['QUANTIDADE']; }
        $freteValor = 0; $distancia = 0; $cepEmpresa='';
        if($id_frete){
            $st = $pdo->prepare("SELECT VALOR FROM FRETE WHERE ID=?");
            $st->execute([$id_frete]);
            $valorKm = (float)$st->fetchColumn();
            $st = $pdo->prepare("SELECT CEP FROM EMPRESA WHERE ID=?");
            $st->execute([$id_empresa]);
            $cepEmpresa = preg_replace('/\D/','',$st->fetchColumn());
            $dest = preg_replace('/\D/','',$cep_entrega);
            if($valorKm && $cepEmpresa && $dest){
                function cepCoords($c){$r=@file_get_contents("https://cep.awesomeapi.com.br/json/{$c}");if(!$r)return null;$d=json_decode($r,true);return isset($d['lat'])?[(float)$d['lat'],(float)$d['lng']]:null;}
                function distanciaKm($c1,$c2){$a=cepCoords($c1); $b=cepCoords($c2);if(!$a||!$b)return 0;list($la1,$lo1)=$a;list($la2,$lo2)=$b;$la1=deg2rad($la1);$la2=deg2rad($la2);$lo1=deg2rad($lo1);$lo2=deg2rad($lo2);$dlat=$la2-$la1;$dlon=$lo2-$lo1;$aa=sin($dlat/2)**2+cos($la1)*cos($la2)*sin($dlon/2)**2;$cc=2*atan2(sqrt($aa),sqrt(1-$aa));return 6371*$cc;}
                $distancia = distanciaKm($cepEmpresa,$dest);
                $freteValor = $distancia * $valorKm;
            }
        }
        $tempoSec = strtotime("1970-01-01 $tempo_previsto UTC")-strtotime('1970-01-01 00:00:00 UTC');
        $valorConserto = ($tempoSec/3600) * (float)str_replace(',','.', $valor_hora);
        $total = $valorProdutos + $valorConserto + $freteValor - (float)str_replace(',','.', $desconto);
        try{
            $pdo->beginTransaction();
            $sql = "INSERT INTO CONCERTO_OCULOS (ID_CLIENTE,ID_USUARIO,ID_EMPRESA,ID_FRETE,DATA_ENTRADA,PREVISAO_ENTREGA,TEMPO_PREVISTO,DESCONTO,CEP_ENTREGA,RUA_ENTREGA,BAIRRO_ENTREGA,ID_CIDADE,SITUACAO,OBSERVACAO,DURACAO_FINAL_SEGUNDOS) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, ?,0)";
            $pdo->prepare($sql)->execute([
                $id_cliente,
                $_SESSION['user'],
                $id_empresa,
                $id_frete ?: null,
                $data_entrada,
                $prev_entrega,
                $tempo_previsto,
                str_replace(',','.', $desconto),
                preg_replace('/\D/','',$cep_entrega),
                $rua_entrega,
                $bairro_entrega,
                $id_cidade ?: null,
                'PENDENTE',
                $obs
            ]);
            $id = $pdo->lastInsertId();
            if($items){
                $stmtI = $pdo->prepare("INSERT INTO CONCERTO_OCULOS_ITENS (ID_CONCERTO,ID_PRODUTO,QUANTIDADE) VALUES (?,?,?)");
                foreach($items as $it){
                    $stmtI->execute([$id,$it['ID_PRODUTO'],$it['QUANTIDADE']]);
                }
            }
            $pdo->commit();
            header('Location: conserto_list.php');
            exit;
        }catch(Exception $e){
            $pdo->rollBack();
            $erro = 'Erro: '.$e->getMessage();
        }
    }
}

if($allowed){
    $in=implode(',',array_fill(0,count($allowed),'?'));
    $st=$pdo->prepare("SELECT ID,NOME,CEP FROM EMPRESA WHERE ID IN ($in) ORDER BY NOME");
    $st->execute($allowed);
    $empresas=$st->fetchAll(PDO::FETCH_ASSOC);
}else{
    $empresas=$pdo->query("SELECT ID,NOME,CEP FROM EMPRESA ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
}
$clientes = $pdo->query("SELECT ID,NOME FROM CLIENTE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$fretes = $pdo->query("SELECT ID,DESCRICAO,VALOR FROM FRETE ORDER BY DESCRICAO")->fetchAll(PDO::FETCH_ASSOC);
$produtos = $pdo->query("SELECT p.ID, p.NOME, p.VALOR_UNITARIO FROM PRODUTO p JOIN CATEGORIA_PRODUTO c ON p.ID_CATEGORIA=c.ID WHERE UPPER(c.NOME) IN ('PECA','PEÇAS','PEÇA','PECAS') ORDER BY p.NOME")->fetchAll(PDO::FETCH_ASSOC);
$cidades = $pdo->query("SELECT ID, CONCAT(NOME,'/',UF) AS NOME FROM CIDADE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);

$cliAddr=['CEP'=>'','RUA'=>'','BAIRRO'=>'','ID_CIDADE'=>''];
if($id_cliente){
    $st=$pdo->prepare("SELECT CEP,RUA,BAIRRO,ID_CIDADE FROM CLIENTE WHERE ID=?");
    $st->execute([$id_cliente]);
    $r=$st->fetch(PDO::FETCH_ASSOC);
    if($r) $cliAddr=$r;
}
$cep_entrega = $_POST['cep_entrega'] ?? $cliAddr['CEP'];
$rua_entrega = $_POST['rua_entrega'] ?? $cliAddr['RUA'];
$bairro_entrega = $_POST['bairro_entrega'] ?? $cliAddr['BAIRRO'];
$id_cidade = $_POST['id_cidade'] ?? $cliAddr['ID_CIDADE'];
$pageTitle='Novo Concerto';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Novo Concerto</h2>
  <?php if($erro): ?><p class="text-red-600 mb-2"><?= htmlspecialchars($erro) ?></p><?php endif; ?>
  <form method="post" id="concertoForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block mb-1">Cliente</label>
      <select name="id_cliente" class="border p-2 rounded w-full" required>
        <option value="">Selecione</option>
        <?php foreach($clientes as $c): ?>
          <option value="<?= $c['ID'] ?>" <?= $id_cliente==$c['ID']?'selected':'' ?>><?= htmlspecialchars($c['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Empresa</label>
      <select name="id_empresa" class="border p-2 rounded w-full" required onchange="updateFrete()">
        <option value="">Selecione</option>
        <?php foreach($empresas as $e): ?>
          <option value="<?= $e['ID'] ?>" <?= $id_empresa==$e['ID']?'selected':'' ?>><?= htmlspecialchars($e['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Data Entrada</label>
      <input type="date" name="data_entrada" value="<?= htmlspecialchars($data_entrada) ?>" class="border p-2 rounded w-full" required>
    </div>
    <div>
      <label class="block mb-1">Previsão Entrega</label>
      <input type="date" name="prev_entrega" value="<?= htmlspecialchars($prev_entrega) ?>" class="border p-2 rounded w-full" required>
    </div>
    <div>
      <label class="block mb-1">Tempo Previsto (hh:mm:ss)</label>
      <input type="text" name="tempo_previsto" value="<?= htmlspecialchars($tempo_previsto) ?>" class="border p-2 rounded w-full" required>
    </div>
    <div>
      <label class="block mb-1">Valor Hora</label>
      <input type="number" step="0.01" name="valor_hora" value="<?= htmlspecialchars($valor_hora) ?>" class="border p-2 rounded w-full" oninput="calcTot()">
    </div>
    <div>
      <label class="block mb-1">Desconto</label>
      <input type="number" step="0.01" name="desconto" value="<?= htmlspecialchars($desconto) ?>" class="border p-2 rounded w-full" oninput="calcTot()">
    </div>
    <div>
      <label class="block mb-1">Frete</label>
      <select name="id_frete" class="border p-2 rounded w-full" onchange="updateFrete()">
        <option value="">Selecione</option>
        <?php foreach($fretes as $f): ?>
          <option value="<?= $f['ID'] ?>" data-valor="<?= $f['VALOR'] ?>" <?= $id_frete==$f['ID']?'selected':'' ?>><?= htmlspecialchars($f['DESCRICAO']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">CEP Entrega</label>
      <input type="text" name="cep_entrega" value="<?= htmlspecialchars($cep_entrega) ?>" class="border p-2 rounded w-full" data-mask="cep" onblur="updateFrete()">
    </div>
    <div>
      <label class="block mb-1">Rua Entrega</label>
      <input type="text" name="rua_entrega" value="<?= htmlspecialchars($rua_entrega) ?>" class="border p-2 rounded w-full">
    </div>
    <div>
      <label class="block mb-1">Bairro Entrega</label>
      <input type="text" name="bairro_entrega" value="<?= htmlspecialchars($bairro_entrega) ?>" class="border p-2 rounded w-full">
    </div>
    <div>
      <label class="block mb-1">Cidade</label>
      <select name="id_cidade" class="border p-2 rounded w-full">
        <option value="">Selecione</option>
        <?php foreach($cidades as $ci): ?>
          <option value="<?= $ci['ID'] ?>" <?= $id_cidade==$ci['ID']?'selected':'' ?>><?= htmlspecialchars($ci['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block mb-1">Observação</label>
      <textarea name="observacao" class="border p-2 rounded w-full" rows="3"><?= htmlspecialchars($obs) ?></textarea>
    </div>
    <div class="md:col-span-2">
      <label class="block mb-1">Produtos Utilizados</label>
      <table class="min-w-full border mb-2">
        <thead><tr class="bg-gray-200"><th class="px-2">Produto</th><th class="px-2">Qtd</th><th></th></tr></thead>
        <tbody id="itemRows"></tbody>
      </table>
      <button type="button" class="px-2 py-1 bg-gray-300 rounded" onclick="addRow()">Adicionar Item</button>
      <template id="rowTpl">
        <tr class="itemRow"><td>
          <select name="produto_id[]" class="border p-1 rounded w-64">
            <option value="">Selecione</option>
            <?php foreach($produtos as $p): ?>
              <option value="<?= $p['ID'] ?>" data-valor="<?= $p['VALOR_UNITARIO'] ?>"><?= htmlspecialchars($p['NOME']) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td><input type="number" name="quantidade[]" value="1" class="border p-1 w-20" onchange="calcTot()"></td>
        <td><button type="button" class="text-red-600" onclick="this.closest('tr').remove();calcTot();">-</button></td></tr>
      </template>
    </div>
    <div class="md:col-span-2 mt-4">
      <p>Valor Produtos: R$ <span id="vProd">0,00</span></p>
      <p>Valor Conserto: R$ <span id="vCons">0,00</span></p>
      <p>Valor Frete: R$ <span id="vFrete">0,00</span></p>
      <p>Desconto: R$ <span id="vDesc">0,00</span></p>
      <p class="text-lg font-bold text-green-800">Valor Total: R$ <span id="vTot">0,00</span></p>
    </div>
    <div class="md:col-span-2">
      <button name="salvar" class="bg-primary text-white px-4 py-2 rounded">Concluir</button>
      <a href="conserto_list.php" class="ml-3 text-red-600">Cancelar</a>
    </div>
  </form>
</div>
<script>
const produtos = <?= json_encode($produtos) ?>;
const empresaCep = <?= json_encode(array_column($empresas,'CEP','ID')) ?>;
const tpl = document.getElementById('rowTpl').content.firstElementChild;
function addRow(){
  const c = tpl.cloneNode(true);
  document.getElementById('itemRows').appendChild(c);
}
function tempoParaHoras(t){
  const [h,m,s] = t.split(':').map(x=>parseInt(x)||0); return h + m/60 + s/3600;
}
let freteAtual=0;
function updateFrete(){
  const cep = document.querySelector('[name=cep_entrega]').value.replace(/\D/g,'');
  const sel = document.querySelector('[name=id_frete]');
  const val = sel.selectedOptions[0]?.dataset.valor||0;
  const emp = document.querySelector('[name=id_empresa]').value;
  const orig = empresaCep[emp]||'';
  if(!cep || !sel.value || !orig){ freteAtual=0; calcTot(); return; }
  fetch(`calcula_distancia.php?orig=${orig}&dest=${cep}&valor=${val}`)
    .then(r=>r.json()).then(d=>{ freteAtual=parseFloat(d.valor)||0; calcTot(); });
}
function calcTot(){
  let vProd=0; document.querySelectorAll('#itemRows tr').forEach(r=>{const sel=r.querySelector('select'); const val=parseFloat(sel.selectedOptions[0]?.dataset.valor||0); const q=parseFloat(r.querySelector('input').value)||0; vProd+=val*q;});
  document.getElementById('vProd').textContent=vProd.toFixed(2);
  const tempo=document.querySelector('[name=tempo_previsto]').value||'0:0:0';
  const vHora=parseFloat(document.querySelector('[name=valor_hora]').value)||0;
  const vCons=tempoParaHoras(tempo)*vHora; document.getElementById('vCons').textContent=vCons.toFixed(2);
  document.getElementById('vFrete').textContent=freteAtual.toFixed(2);
  const desc=parseFloat(document.querySelector('[name=desconto]').value)||0; document.getElementById('vDesc').textContent=desc.toFixed(2);
  const total=vProd+vCons+freteAtual-desc; document.getElementById('vTot').textContent=total.toFixed(2);
}
addRow();
calcTot();
updateFrete();
</script>
<?php include 'footer.php'; ?>
