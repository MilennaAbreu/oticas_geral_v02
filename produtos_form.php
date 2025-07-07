<?php
$pageTitle = 'Novo Produto';
include 'header.php';

$id = $_GET['id'] ?? null;
$error = $_GET['erro'] ?? '';
$produto = [
    'NOME'           => '',
    'ID_TIPO'        => '',
    'ID_CATEGORIA'   => '',
    'ID_MARCA'       => '',
    'CODIGO'         => '',
    'UNIDADE_MEDIDA' => 'UN',
    'VALOR_COMPRA'   => '',
    'VALOR_UNITARIO' => '',
    'ESTOQUE_ATUAL'  => '',
    'IMAGEM'         => '',
    'STATUS'         => 'ATIVO',
    'ID_EMPRESA'     => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM PRODUTO WHERE ID = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
}
$categorias = $pdo->query("SELECT * FROM CATEGORIA_PRODUTO")->fetchAll(PDO::FETCH_ASSOC);
$tipos      = $pdo->query("SELECT * FROM TIPO_PRODUTO")->fetchAll(PDO::FETCH_ASSOC);
$marcas     = $pdo->query("SELECT * FROM MARCA_PRODUTO")->fetchAll(PDO::FETCH_ASSOC);
$empresaIds = userCompanies($pdo);
if(!hasRole('ADMINISTRADOR','DIRETORIA') && $empresaIds){
    $in = implode(',', array_fill(0,count($empresaIds),'?'));
    $stmt = $pdo->prepare("SELECT ID, NOME FROM EMPRESA WHERE ID IN ($in) ORDER BY NOME");
    $stmt->execute($empresaIds);
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $empresas = $pdo->query("SELECT ID, NOME FROM EMPRESA ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4"><?= $id ? 'Editar' : 'Novo' ?> Produto</h2>
  <?php if($error==='dup'): ?>
    <p class="text-red-600 mb-2">Já existe produto com este código para a empresa selecionada.</p>
  <?php endif; ?>
  <form method="POST" action="produtos_save.php" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div><label>Nome</label><input name="nome" value="<?= $produto['NOME'] ?>" class="border p-2 w-full rounded" required></div>
    <div>
      <label>Marca</label>
      <div class="flex">
        <select name="id_marca" class="border p-2 w-full rounded">
          <option value="">Selecione</option>
          <?php foreach($marcas as $m): ?>
            <option value="<?= $m['ID'] ?>" <?= $produto['ID_MARCA']==$m['ID'] ? 'selected':'' ?>><?= $m['NOME'] ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" onclick="openModal('modalMarca')" class="ml-2 px-3 py-1 bg-gray-300 rounded">+</button>
      </div>
    </div>
    <div><label>Código</label><input name="codigo" value="<?= $produto['CODIGO'] ?>" class="border p-2 w-full rounded"></div>
    <div><label>Unidade de Medida</label><input name="unidade_medida" value="<?= $produto['UNIDADE_MEDIDA'] ?>" class="border p-2 w-full rounded"></div>
    <div><label>Valor Compra</label><input name="valor_compra" value="<?= $produto['VALOR_COMPRA'] ?>" class="border p-2 w-full rounded"></div>
    <div><label>Valor para Venda</label><input name="valor_unitario" value="<?= $produto['VALOR_UNITARIO'] ?>" class="border p-2 w-full rounded" required></div>
    <div><label>Estoque Atual</label><input name="estoque_atual" value="<?= $produto['ESTOQUE_ATUAL'] ?>" class="border p-2 w-full rounded" required></div>
    <div>
      <label>Status</label>
      <select name="status" class="border p-2 w-full rounded">
        <option value="ATIVO" <?= $produto['STATUS']=='ATIVO' ? 'selected':'' ?>>ATIVO</option>
        <option value="INATIVO" <?= $produto['STATUS']=='INATIVO' ? 'selected':'' ?>>INATIVO</option>
      </select>
    </div>
    <div>
      <label>Categoria</label>
      <div class="flex">
        <select name="id_categoria" class="border p-2 w-full rounded">
          <option value="">Selecione</option>
          <?php foreach($categorias as $c): ?>
            <option value="<?= $c['ID'] ?>" <?= $produto['ID_CATEGORIA']==$c['ID'] ? 'selected':'' ?>><?= $c['NOME'] ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" onclick="openModal('modalCategoria')" class="ml-2 px-3 py-1 bg-gray-300 rounded">+</button>
      </div>
    </div>
    <div>
      <label>Tipo</label>
      <div class="flex">
        <select name="id_tipo" class="border p-2 w-full rounded">
          <option value="">Selecione</option>
          <?php foreach($tipos as $t): ?>
            <option value="<?= $t['ID'] ?>" <?= $produto['ID_TIPO']==$t['ID'] ? 'selected':'' ?>><?= $t['NOME'] ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" onclick="openModal('modalTipo')" class="ml-2 px-3 py-1 bg-gray-300 rounded">+</button>
      </div>
    </div>
    <div>
      <label>Empresa</label>
      <select name="id_empresa" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($empresas as $e): ?>
          <option value="<?= $e['ID'] ?>" <?= $produto['ID_EMPRESA']==$e['ID']?'selected':'' ?>><?= $e['NOME'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label>Imagem</label>
      <input type="file" name="imagem" class="border p-2 w-full rounded">
      <?php if($produto['IMAGEM']): ?>
        <img src="uploads/<?= $produto['IMAGEM'] ?>" width="100" class="mt-2">
      <?php endif; ?>
    </div>
    <div class="md:col-span-2">
      <button class="bg-primary text-white px-4 py-2 rounded">Salvar</button>
    </div>
</form>
<!-- Modais para cadastro rápido -->
<div id="modalCategoria" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
  <div class="bg-white p-4 rounded w-80">
    <h3 class="text-lg mb-2">Nova Categoria</h3>
    <input id="catNome" type="text" class="border p-2 w-full mb-3" />
    <div class="text-right">
      <button type="button" class="mr-2 px-3 py-1" onclick="closeModal('modalCategoria')">Cancelar</button>
      <button type="button" class="bg-primary text-white px-3 py-1 rounded" onclick="saveCategoria()">Salvar</button>
    </div>
  </div>
</div>
<div id="modalTipo" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
  <div class="bg-white p-4 rounded w-80">
    <h3 class="text-lg mb-2">Novo Tipo</h3>
    <input id="tipoNome" type="text" class="border p-2 w-full mb-3" />
    <div class="text-right">
      <button type="button" class="mr-2 px-3 py-1" onclick="closeModal('modalTipo')">Cancelar</button>
      <button type="button" class="bg-primary text-white px-3 py-1 rounded" onclick="saveTipo()">Salvar</button>
    </div>
  </div>
</div>
<div id="modalMarca" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
  <div class="bg-white p-4 rounded w-80">
    <h3 class="text-lg mb-2">Nova Marca</h3>
    <input id="marcaNome" type="text" class="border p-2 w-full mb-3" />
    <div class="text-right">
      <button type="button" class="mr-2 px-3 py-1" onclick="closeModal('modalMarca')">Cancelar</button>
      <button type="button" class="bg-primary text-white px-3 py-1 rounded" onclick="saveMarca()">Salvar</button>
    </div>
  </div>
</div>
</div>
<script>
function openModal(id){
  document.getElementById(id).classList.remove('hidden');
}
function closeModal(id){
  document.getElementById(id).classList.add('hidden');
}
function saveCategoria(){
  const nome = document.getElementById('catNome').value.trim();
  if(!nome) return;
  fetch('categorias_add.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'nome='+encodeURIComponent(nome)
  }).then(r=>r.json()).then(d=>{
    if(d.success){
      const select = document.querySelector('select[name="id_categoria"]');
      const opt = document.createElement('option');
      opt.value = d.id; opt.textContent = d.nome;
      select.appendChild(opt);
      select.value = d.id;
      document.getElementById('catNome').value='';
      closeModal('modalCategoria');
    }
  });
}
function saveTipo(){
  const nome = document.getElementById('tipoNome').value.trim();
  if(!nome) return;
  fetch('tipo_produtos_add.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'nome='+encodeURIComponent(nome)
  }).then(r=>r.json()).then(d=>{
    if(d.success){
      const select = document.querySelector('select[name="id_tipo"]');
      const opt = document.createElement('option');
      opt.value = d.id; opt.textContent = d.nome;
      select.appendChild(opt);
      select.value = d.id;
      document.getElementById('tipoNome').value='';
      closeModal('modalTipo');
    }
  });
}
function saveMarca(){
  const nome = document.getElementById('marcaNome').value.trim();
  if(!nome) return;
  fetch('marcas_add.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'nome='+encodeURIComponent(nome)
  }).then(r=>r.json()).then(d=>{
    if(d.success){
      const select = document.querySelector('select[name="id_marca"]');
      const opt = document.createElement('option');
      opt.value = d.id; opt.textContent = d.nome;
      select.appendChild(opt);
      select.value = d.id;
      document.getElementById('marcaNome').value='';
      closeModal('modalMarca');
    }
  });
}
</script>
<?php include 'footer.php'; ?>
