<?php
require 'config.php';
require 'auth.php';
$id = $_GET['id'] ?? null;
$nome = '';
$descricao = '';
$preco = '';
if($id){
  $stmt = $pdo->prepare("SELECT id, nome, descricao, preco FROM PECAS WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if($row){
    $nome = $row['nome'];
    $descricao = $row['descricao'];
    $preco = $row['preco'];
  }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nome = $_POST['nome'];
  $descricao = $_POST['descricao'];
  $preco = $_POST['preco'];
  if($id){
    $stmt = $pdo->prepare("UPDATE PECAS SET nome=?, descricao=?, preco=? WHERE id=?");
    $stmt->execute([$nome, $descricao, $preco, $id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO PECAS (nome, descricao, preco) VALUES (?,?,?)");
    $stmt->execute([$nome, $descricao, $preco]);
  }
  header('Location: pecas_list.php');
  exit;
}
$pageTitle = $id ? 'Editar Pecas' : 'Novo Pecas';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block mb-1">Nome</label>
      <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div>
      <label class="block mb-1">Descrição</label>
      <input type="text" name="descricao" value="<?= htmlspecialchars($descricao) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div>
      <label class="block mb-1">Preço</label>
      <input type="text" name="preco" value="<?= htmlspecialchars($preco) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div class="md:col-span-2">
      <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id? 'Atualizar':'Salvar' ?></button>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
