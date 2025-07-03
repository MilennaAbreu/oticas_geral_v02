<?php
require_once 'config.php';
require_once 'auth.php';
$id = $_GET['id'] ?? null;
$nome = $juros = $condicao = '';
$error = '';
if($id){
  $stmt = $pdo->prepare("SELECT nome, juros, condicao FROM CONDICAO_PAGAMENTO WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if($row){
    $nome = $row['nome'];
    $juros = number_format($row['juros'],2,',','.');
    $condicao = $row['condicao'];
  }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nome = $_POST['nome'];
  $juros = str_replace(',','.',str_replace('.','',$_POST['juros']));
  $condicao = $_POST['condicao'];
  if($id){
    $stmt = $pdo->prepare("UPDATE CONDICAO_PAGAMENTO SET nome=?, juros=?, condicao=? WHERE id=?");
    $stmt->execute([$nome,$juros,$condicao,$id]);
  }else{
    $stmt = $pdo->prepare("INSERT INTO CONDICAO_PAGAMENTO (nome,juros,condicao) VALUES (?,?,?)");
    $stmt->execute([$nome,$juros,$condicao]);
    $id = $pdo->lastInsertId();
  }
  header('Location: condicoes_pagamento_list.php');
  exit();
}
$pageTitle = $id ? 'Editar Condição de Pagamento' : 'Nova Condição de Pagamento';
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
    <div>
      <label class="block mb-1">Juros (%)</label>
      <input type="text" name="juros" value="<?= htmlspecialchars($juros) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div>
      <label class="block mb-1">Condição</label>
      <input type="text" name="condicao" value="<?= htmlspecialchars($condicao) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <div class="md:col-span-2">
      <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id? 'Atualizar':'Salvar' ?></button>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
