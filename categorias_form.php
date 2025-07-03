<?php
$id = $_GET['id'] ?? null;
$pageTitle = $id ? 'Editar Categoria' : 'Nova Categoria';
include 'header.php';
$nome = '';
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
  if($id){
    $stmt = $pdo->prepare("UPDATE CATEGORIA_PRODUTO SET nome=? WHERE id=?");
    $stmt->execute([$nome, $id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO CATEGORIA_PRODUTO (nome) VALUES (?)");
    $stmt->execute([$nome]);
  }
  header('Location: categorias_list.php'); exit;
}
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
  <form method="post" class="space-y-4">
    <div>
      <label class="block mb-1">Nome</label>
      <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div>
      <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id? 'Atualizar':'Salvar' ?></button>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
