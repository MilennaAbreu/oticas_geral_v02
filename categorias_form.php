<?php
require_once 'config.php';
require_once 'auth.php';
$id = $_GET['id'] ?? null;
$nome = '';
$error = '';
if($id){
  $stmt = $pdo->prepare("SELECT id, nome FROM CATEGORIA_PRODUTO WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if($row){
    $nome = $row['nome'];
  }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nome = $_POST['nome'];
  try {
    if($id){
      $stmt = $pdo->prepare("UPDATE CATEGORIA_PRODUTO SET nome=? WHERE id=?");
      $stmt->execute([$nome, $id]);
    } else {
      $stmt = $pdo->prepare("INSERT INTO CATEGORIA_PRODUTO (nome) VALUES (?)");
      $stmt->execute([$nome]);
    }
    header('Location: categorias_list.php');
    exit;
  } catch(PDOException $e){
    if($e->errorInfo[1] == 1062){
      $error = 'Já existe uma categoria com este nome.';
    } else {
      throw $e;
    }
  }
}
$pageTitle = $id ? 'Editar Categoria' : 'Nova Categoria';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
  <?php if($error): ?>
    <p class="text-red-600 mb-2"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block mb-1">Nome</label>
      <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div class="md:col-span-2">
      <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id? 'Atualizar':'Salvar' ?></button>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
