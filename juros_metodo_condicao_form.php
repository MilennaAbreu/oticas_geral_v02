<?php
require_once 'config.php';
require_once 'auth.php';

$id = $_GET['id'] ?? null;
$metodo = $condicao = '';
$juros = '';
if($id){
    $stmt = $pdo->prepare("SELECT ID_METODO_PAGAMENTO, ID_CONDICAO, JUROS_MENSAL FROM JUROS_METODO_CONDICAO WHERE ID=?");
    $stmt->execute([$id]);
    if($row=$stmt->fetch(PDO::FETCH_ASSOC)){
        $metodo = $row['ID_METODO_PAGAMENTO'];
        $condicao = $row['ID_CONDICAO'];
        $juros = number_format($row['JUROS_MENSAL'],2,',','.');
    }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $metodo = $_POST['id_metodo_pagamento'] ?? '';
    $condicao = $_POST['id_condicao'] ?? '';
    $juros = str_replace(',', '.', str_replace('.', '', $_POST['juros_mensal'] ?? '0'));
    if($id){
        $stmt = $pdo->prepare("UPDATE JUROS_METODO_CONDICAO SET ID_METODO_PAGAMENTO=?, ID_CONDICAO=?, JUROS_MENSAL=? WHERE ID=?");
        $stmt->execute([$metodo,$condicao,$juros,$id]);
    }else{
        $stmt = $pdo->prepare("INSERT INTO JUROS_METODO_CONDICAO (ID_METODO_PAGAMENTO, ID_CONDICAO, JUROS_MENSAL) VALUES (?,?,?)");
        $stmt->execute([$metodo,$condicao,$juros]);
        $id = $pdo->lastInsertId();
    }
    header('Location: juros_metodo_condicao_list.php');
    exit();
}
$metodos = $pdo->query("SELECT ID, NOME FROM METODO_PAGAMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$condicoes = $pdo->query("SELECT ID, NOME FROM CONDICAO_PAGAMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle = $id ? 'Editar Juros' : 'Novo Juros';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block mb-1">Método</label>
      <select name="id_metodo_pagamento" class="border-b-2 border-gray-300 px-3 py-2 w-full">
        <option value="">Selecione</option>
        <?php foreach($metodos as $m): ?>
          <option value="<?= $m['ID'] ?>" <?= $metodo==$m['ID']?'selected':'' ?>><?= htmlspecialchars($m['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Condição</label>
      <select name="id_condicao" class="border-b-2 border-gray-300 px-3 py-2 w-full">
        <option value="">Selecione</option>
        <?php foreach($condicoes as $c): ?>
          <option value="<?= $c['ID'] ?>" <?= $condicao==$c['ID']?'selected':'' ?>><?= htmlspecialchars($c['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Juros Mensal (%)</label>
      <input type="text" name="juros_mensal" value="<?= htmlspecialchars($juros) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full" data-mask="money">
    </div>
    <div class="md:col-span-2">
      <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id?'Atualizar':'Salvar' ?></button>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
