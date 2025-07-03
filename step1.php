<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'permissions.php';

$allowed = userCompanies($pdo);
if(!$allowed){
    $allowed = [];
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $empresa = $_POST['id_empresa'] ?? null;
    $cliente = $_POST['id_cliente'] ?? null;
    if($empresa && $cliente){
        $_SESSION['venda'] = [];
        $_SESSION['venda']['ID_EMPRESA'] = $empresa;
        $_SESSION['venda']['ID_CLIENTE'] = $cliente;
        $_SESSION['venda']['ID_USUARIO'] = $_SESSION['user'];
        header('Location: step2.php');
        exit;
    }
}

if(count($allowed) === 1){
    $defaultEmpresa = $allowed[0];
} else {
    $defaultEmpresa = '';
}

if($allowed){
    $in = implode(',', array_fill(0,count($allowed),'?'));
    $stmt = $pdo->prepare("SELECT ID, NOME FROM EMPRESA WHERE ID IN ($in) ORDER BY NOME");
    $stmt->execute($allowed);
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $empresas = $pdo->query("SELECT ID, NOME FROM EMPRESA ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
}

$clientes = $pdo->query("SELECT ID, CPF, NOME FROM CLIENTE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = 'Empresa e Cliente';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Dados Iniciais da Venda</h2>
  <form method="post" class="space-y-4">
    <div>
      <label class="block mb-1">Empresa</label>
      <select name="id_empresa" class="border p-2 rounded w-full" required>
        <option value="">Selecione</option>
        <?php foreach($empresas as $e): ?>
          <option value="<?= $e['ID'] ?>" <?= isset($defaultEmpresa)&&$defaultEmpresa==$e['ID']?'selected':'' ?>><?= htmlspecialchars($e['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Cliente</label>
      <div class="flex">
        <select name="id_cliente" id="clienteSelect" class="border p-2 rounded w-full" required>
          <option value="">Selecione</option>
          <?php foreach($clientes as $c): ?>
            <option value="<?= $c['ID'] ?>"><?= htmlspecialchars($c['CPF'].' - '.$c['NOME']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" onclick="openModal('modalCliente')" class="ml-2 px-3 py-1 bg-gray-300 rounded">+</button>
      </div>
    </div>
    <div>
      <button class="bg-primary text-white px-4 py-2 rounded">Próximo</button>
      <a href="cancelar_venda.php" class="ml-3 text-red-600">Cancelar</a>
    </div>
  </form>

  <div id="modalCliente" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-4 rounded w-80">
      <h3 class="text-lg mb-2">Novo Cliente</h3>
      <input id="cliNome" type="text" class="border p-2 w-full mb-2" placeholder="Nome" />
      <input id="cliCpf" type="text" class="border p-2 w-full mb-3" placeholder="CPF" />
      <div class="text-right">
        <button type="button" class="mr-2 px-3 py-1" onclick="closeModal('modalCliente')">Cancelar</button>
        <button type="button" class="bg-primary text-white px-3 py-1 rounded" onclick="saveCliente()">Salvar</button>
      </div>
    </div>
  </div>
</div>
<script>
function openModal(id){document.getElementById(id).classList.remove('hidden');}
function closeModal(id){document.getElementById(id).classList.add('hidden');}
function saveCliente(){
  const nome=document.getElementById('cliNome').value.trim();
  const cpf=document.getElementById('cliCpf').value.replace(/\D/g,'');
  if(!nome||!cpf) return;
  fetch('clientes_add.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'nome='+encodeURIComponent(nome)+'&cpf='+encodeURIComponent(cpf)})
  .then(r=>r.json()).then(d=>{if(d.success){const sel=document.getElementById('clienteSelect');const opt=document.createElement('option');opt.value=d.id;opt.textContent=d.nome;sel.appendChild(opt);sel.value=d.id;closeModal('modalCliente');}});
}
</script>
<?php include 'footer.php'; ?>

