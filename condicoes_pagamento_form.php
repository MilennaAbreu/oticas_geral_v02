<?php
require_once 'config.php';
require_once 'auth.php';
$id = $_GET['id'] ?? null;
$nome = $juros = $condicao = '';
$error = '';
$hasJuros = false;
$hasCondicao = false;
try {
  $chk = $pdo->query("SHOW COLUMNS FROM CONDICAO_PAGAMENTO LIKE 'juros'");
  $hasJuros = $chk->fetch(PDO::FETCH_ASSOC) ? true : false;
} catch (PDOException $e) {
  $hasJuros = false;
}
try {
  $chk = $pdo->query("SHOW COLUMNS FROM CONDICAO_PAGAMENTO LIKE 'condicao'");
  $hasCondicao = $chk->fetch(PDO::FETCH_ASSOC) ? true : false;
} catch (PDOException $e) {
  $hasCondicao = false;
}
if($id){
  $cols = ['nome'];
  if($hasJuros) $cols[] = 'juros';
  if($hasCondicao) $cols[] = 'condicao';
  $stmt = $pdo->prepare("SELECT " . implode(',', $cols) . " FROM CONDICAO_PAGAMENTO WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if($row){
    $nome = $row['nome'];
    if($hasJuros && isset($row['juros'])) $juros = number_format($row['juros'],2,',','.');
    if($hasCondicao && isset($row['condicao'])) $condicao = $row['condicao'];
  }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $nome = $_POST['nome'];
  $juros = str_replace(',','.',str_replace('.','',$_POST['juros'] ?? '0'));
  if($hasCondicao) $condicao = $_POST['condicao'];
  if($id){
    $fields = ['nome=?'];
    $values = [$nome];
    if($hasJuros){
      $fields[] = 'juros=?';
      $values[] = $juros;
    }
    if($hasCondicao){
      $fields[] = 'condicao=?';
      $values[] = $condicao;
    }
    $values[] = $id;
    $stmt = $pdo->prepare("UPDATE CONDICAO_PAGAMENTO SET " . implode(',', $fields) . " WHERE id=?");
    $stmt->execute($values);
  }else{
    $cols = ['nome'];
    $place = ['?'];
    $values = [$nome];
    if($hasJuros){
      $cols[] = 'juros';
      $place[] = '?';
      $values[] = $juros;
    }
    if($hasCondicao){
      $cols[] = 'condicao';
      $place[] = '?';
      $values[] = $condicao;
    }
    $stmt = $pdo->prepare("INSERT INTO CONDICAO_PAGAMENTO (" . implode(',', $cols) . ") VALUES (" . implode(',', $place) . ")");
    $stmt->execute($values);
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
    <?php if($hasJuros): ?>
    <div>
      <label class="block mb-1">Juros (%)</label>
      <input type="text" name="juros" value="<?= htmlspecialchars($juros) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <?php endif; ?>
    <?php if($hasCondicao): ?>
    <div>
      <label class="block mb-1">Condição</label>
      <input type="text" name="condicao" value="<?= htmlspecialchars($condicao) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
    </div>
    <?php endif; ?>
    <div class="md:col-span-2">
      <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id? 'Atualizar':'Salvar' ?></button>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
