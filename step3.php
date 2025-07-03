<?php
require_once 'config.php';
require_once 'auth.php';

if(!isset($_SESSION['venda']['ID_EMPRESA']) || !isset($_SESSION['venda']['itens'])){
    header('Location: step1.php');
    exit;
}

$itens = $_SESSION['venda']['itens'];

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['finalizar'])){
    $idCond  = $_POST['id_condicao_pagamento'];
    $idMet   = $_POST['id_metodo_pagamento'];
    $idFrete = $_POST['id_frete'] ?? null;
    $dataEntrega = $_POST['data_entrega'] ?: null;

    // calcula valores
    $valorVenda = 0; $valorDesc = 0;
    foreach($itens as $it){
        $valorVenda += $it['QUANTIDADE'] * $it['VALOR_UNITARIO'];
        $valorDesc  += $it['DESCONTO'];
    }
    $valorTotal = $valorVenda - $valorDesc;

    $stmt = $pdo->prepare("SELECT juros FROM JUROS_METODO_CONDICAO WHERE ID_METODO_PAGAMENTO=? AND ID_CONDICAO_PAGAMENTO=?");
    $stmt->execute([$idMet, $idCond]);
    $juros = $stmt->fetchColumn();
    if($juros === false) $juros = 0;
    $valorLiquido = $valorTotal * (1 - $juros/100);

    $pdo->beginTransaction();
    try {
        $sql = "INSERT INTO VENDAS (ID_CLIENTE,ID_USUARIO,ID_CONDICAO_PAGAMENTO,ID_METODO_PAGAMENTO,JUROS_APLICADO,VALOR_VENDA,VALOR_LIQUIDO,VALOR_TOTAL,ID_FRETE,DATA_ENTREGA,STATUS,ID_EMPRESA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([
            $_SESSION['venda']['ID_CLIENTE'],
            $_SESSION['venda']['ID_USUARIO'],
            $idCond,
            $idMet,
            $juros,
            $valorVenda,
            $valorLiquido,
            $valorTotal,
            $idFrete,
            $dataEntrega,
            'PENDENTE',
            $_SESSION['venda']['ID_EMPRESA']
        ]);
        $idVenda = $pdo->lastInsertId();
        $stmtItem = $pdo->prepare("INSERT INTO ITENS_VENDA (ID_VENDA,ID_PRODUTO,QUANTIDADE,VALOR_UNITARIO,DESCONTO) VALUES (?,?,?,?,?)");
        foreach($itens as $it){
            $stmtItem->execute([$idVenda,$it['ID_PRODUTO'],$it['QUANTIDADE'],$it['VALOR_UNITARIO'],$it['DESCONTO']]);
        }
        $pdo->commit();
        unset($_SESSION['venda']);
        header('Location: vendas_list.php');
        exit;
    } catch(Exception $e){
        $pdo->rollBack();
        $error = 'Erro ao salvar venda';
    }
}

if(isset($_POST['voltar'])){
    header('Location: step2.php');
    exit;
}

$condicoes = $pdo->query("SELECT ID, NOME FROM CONDICAO_PAGAMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$metodos   = $pdo->query("SELECT ID, NOME FROM METODO_PAGAMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$fretes    = $pdo->query("SELECT ID, DESCRICAO FROM FRETE ORDER BY DESCRICAO")->fetchAll(PDO::FETCH_ASSOC);

$valorVenda = 0; $valorDesc=0; foreach($itens as $it){ $valorVenda += $it['QUANTIDADE']*$it['VALOR_UNITARIO']; $valorDesc += $it['DESCONTO']; }
$valorTotal = $valorVenda - $valorDesc;

$pageTitle = 'Pagamento';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Condição e Pagamento</h2>
  <?php if(!empty($error)): ?>
    <p class="text-red-600 mb-2"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
      <label class="block mb-1">Condição de Pagamento</label>
      <select name="id_condicao_pagamento" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($condicoes as $c): ?>
          <option value="<?= $c['ID'] ?>"><?= htmlspecialchars($c['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Método de Pagamento</label>
      <select name="id_metodo_pagamento" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($metodos as $m): ?>
          <option value="<?= $m['ID'] ?>"><?= htmlspecialchars($m['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Frete</label>
      <select name="id_frete" class="border p-2 w-full rounded">
        <option value="">Selecione</option>
        <?php foreach($fretes as $f): ?>
          <option value="<?= $f['ID'] ?>"><?= htmlspecialchars($f['DESCRICAO']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Data de Entrega</label>
      <input type="date" name="data_entrega" class="border p-2 w-full rounded">
    </div>
    <div class="md:col-span-2">
      <p>Valor Venda: R$ <?= number_format($valorVenda,2,',','.') ?></p>
      <p>Desconto: R$ <?= number_format($valorDesc,2,',','.') ?></p>
      <p>Valor Total: R$ <?= number_format($valorTotal,2,',','.') ?></p>
    </div>
    <div class="md:col-span-2">
      <button name="voltar" class="px-4 py-2 bg-gray-300 rounded mr-2">Voltar</button>
      <button name="finalizar" class="bg-primary text-white px-4 py-2 rounded">Finalizar</button>
      <a href="cancelar_venda.php" class="ml-3 text-red-600">Cancelar</a>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>

