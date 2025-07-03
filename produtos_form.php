<?php
$pageTitle = 'Novo Produto';
include 'header.php';

$id = $_GET['id'] ?? null;
$produto = [
    'NOME'           => '',
    'ID_TIPO'        => '',
    'ID_CATEGORIA'   => '',
    'MARCA'          => '',
    'CODIGO'         => '',
    'UNIDADE_MEDIDA' => 'UN',
    'VALOR_UNITARIO' => '',
    'ESTOQUE_ATUAL'  => '',
    'IMAGEM'         => '',
    'STATUS'         => 'ATIVO'
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM PRODUTO WHERE ID = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
}
$categorias = $pdo->query("SELECT * FROM CATEGORIA_PRODUTO")->fetchAll(PDO::FETCH_ASSOC);
$tipos      = $pdo->query("SELECT * FROM TIPO_PRODUTO")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4"><?= $id ? 'Editar' : 'Novo' ?> Produto</h2>
  <form method="POST" action="produtos_save.php" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div><label>Nome</label><input name="nome" value="<?= $produto['NOME'] ?>" class="border p-2 w-full rounded" required></div>
    <div><label>Marca</label><input name="marca" value="<?= $produto['MARCA'] ?>" class="border p-2 w-full rounded"></div>
    <div><label>Código</label><input name="codigo" value="<?= $produto['CODIGO'] ?>" class="border p-2 w-full rounded"></div>
    <div><label>Unidade de Medida</label><input name="unidade_medida" value="<?= $produto['UNIDADE_MEDIDA'] ?>" class="border p-2 w-full rounded"></div>
    <div><label>Valor Unitário</label><input name="valor_unitario" value="<?= $produto['VALOR_UNITARIO'] ?>" class="border p-2 w-full rounded" required></div>
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
        <button type="button" onclick="addCategoria()" class="ml-2 px-3 py-1 bg-gray-300 rounded">+</button>
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
        <button type="button" onclick="addTipo()" class="ml-2 px-3 py-1 bg-gray-300 rounded">+</button>
      </div>
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
</div>
<script>
function addCategoria() {
  let nome = prompt("Nova categoria:");
  if(nome){
    fetch("categorias_add.php", {
      method: "POST", headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: "nome=" + encodeURIComponent(nome)
    }).then(() => location.reload());
  }
}
function addTipo() {
  let nome = prompt("Novo tipo:");
  if(nome){
    fetch("tipo_produtos_add.php", {
      method: "POST", headers: {"Content-Type": "application/x-www-form-urlencoded"},
      body: "nome=" + encodeURIComponent(nome)
    }).then(() => location.reload());
  }
}
</script>
<?php include 'footer.php'; ?>
