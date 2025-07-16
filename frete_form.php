<?php
require_once 'config.php';
require_once 'auth.php';
$id = $_GET['id'] ?? null;
$descricao = $valor = $status = '';
$error = '';
if($id){
  $stmt = $pdo->prepare("SELECT descricao, valor, status FROM FRETE WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if($row){
    $descricao = $row['descricao'];
    $valor = number_format($row['valor'],2,',','.');
    $status = $row['status'];
  }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $descricao = $_POST['descricao'];
  $valor = str_replace(',','.',str_replace('.','',$_POST['valor']));
  $status = $_POST['status'];
  if($id){
    $stmt = $pdo->prepare("UPDATE FRETE SET descricao=?, valor=?, status=? WHERE id=?");
    $stmt->execute([$descricao,$valor,$status,$id]);
  }else{
    $stmt = $pdo->prepare("INSERT INTO FRETE (descricao,valor,status) VALUES (?,?,?)");
    $stmt->execute([$descricao,$valor,$status]);
    $id = $pdo->lastInsertId();
  }
  header('Location: frete_list.php');
  exit();
}
$pageTitle = $id ? 'Editar Frete' : 'Novo Frete';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
  <?php if($error): ?>
    <p class="text-red-600 mb-2"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block mb-1">Descrição</label>
      <input type="text" name="descricao" value="<?= htmlspecialchars($descricao) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div>
      <label class="block mb-1">Valor</label>
      <input type="text" name="valor" value="<?= htmlspecialchars($valor) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div>
      <label class="block mb-1">Status</label>
      <select name="status" class="border-b-2 border-gray-300 px-3 py-2 w-full">
        <option value="ATIVO" <?= $status=='ATIVO'?'selected':'' ?>>Ativo</option>
        <option value="INATIVO" <?= $status=='INATIVO'?'selected':'' ?>>Inativo</option>
      </select>
    </div>
    <div class="md:col-span-2">
      <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id? 'Atualizar':'Salvar' ?></button>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
