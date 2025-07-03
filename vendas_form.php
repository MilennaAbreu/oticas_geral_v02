<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'permissions.php';

$id = $_GET['id'] ?? null;
$sale = [
    'ID_CLIENTE' => '',
    'ID_USUARIO' => '',
    'ID_CONDICAO_PAGAMENTO' => '',
    'ID_METODO_PAGAMENTO' => '',
    'ID_FRETE' => '',
    'DATA_ENTREGA' => '',
    'VALOR_VENDA' => '',
    'DESCONTO' => '0.00',
    'VALOR_TOTAL' => '',
    'TELEFONE_CONTATO' => '',
    'RESPONSAVEL_CONTATO' => '',
    'CEP_ENTREGA' => '',
    'RUA_ENTREGA' => '',
    'BAIRRO_ENTREGA' => '',
    'ID_CIDADE' => '',
    'OBSERVACAO' => '',
    'STATUS' => 'PENDENTE',
    'ID_EMPRESA' => ''
];

if($id){
    $stmt = $pdo->prepare("SELECT * FROM VENDAS WHERE ID=?");
    $stmt->execute([$id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
}

$clientes = $pdo->query("SELECT ID, NOME FROM CLIENTE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$usuarios = $pdo->query("SELECT ID, NOME FROM USUARIO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$condicoes = $pdo->query("SELECT ID, NOME FROM CONDICAO_PAGAMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$metodos = $pdo->query("SELECT ID, NOME FROM METODO_PAGAMENTO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$fretes = $pdo->query("SELECT ID, DESCRICAO FROM FRETE ORDER BY DESCRICAO")->fetchAll(PDO::FETCH_ASSOC);
$cidades = $pdo->query("SELECT ID, CONCAT(NOME,'/',UF) AS NOME FROM CIDADE ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$empresaIds = userCompanies($pdo);
if(!hasRole('ADMINISTRADOR','DIRETORIA') && $empresaIds){
    $in = implode(',', array_fill(0,count($empresaIds),'?'));
    $stmt = $pdo->prepare("SELECT ID, NOME_FANTASIA FROM EMPRESA WHERE ID IN ($in) ORDER BY NOME_FANTASIA");
    $stmt->execute($empresaIds);
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $empresas = $pdo->query("SELECT ID, NOME_FANTASIA FROM EMPRESA ORDER BY NOME_FANTASIA")->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = $id ? 'Editar Venda' : 'Nova Venda';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
  <form method="post" action="vendas_save.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div>
      <label class="block mb-1">Cliente</label>
      <select name="id_cliente" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($clientes as $c): ?>
          <option value="<?= $c['ID'] ?>" <?= $sale['ID_CLIENTE']==$c['ID']?'selected':'' ?>><?= htmlspecialchars($c['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Vendedor</label>
      <select name="id_usuario" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($usuarios as $u): ?>
          <option value="<?= $u['ID'] ?>" <?= $sale['ID_USUARIO']==$u['ID']?'selected':'' ?>><?= htmlspecialchars($u['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Condição Pagamento</label>
      <select name="id_condicao_pagamento" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($condicoes as $c): ?>
          <option value="<?= $c['ID'] ?>" <?= $sale['ID_CONDICAO_PAGAMENTO']==$c['ID']?'selected':'' ?>><?= htmlspecialchars($c['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Método Pagamento</label>
      <select name="id_metodo_pagamento" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($metodos as $m): ?>
          <option value="<?= $m['ID'] ?>" <?= $sale['ID_METODO_PAGAMENTO']==$m['ID']?'selected':'' ?>><?= htmlspecialchars($m['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Frete</label>
      <select name="id_frete" class="border p-2 w-full rounded">
        <option value="">Selecione</option>
        <?php foreach($fretes as $f): ?>
          <option value="<?= $f['ID'] ?>" <?= $sale['ID_FRETE']==$f['ID']?'selected':'' ?>><?= htmlspecialchars($f['DESCRICAO']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Data Entrega</label>
      <input type="date" name="data_entrega" value="<?= $sale['DATA_ENTREGA'] ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Valor Venda</label>
      <input type="text" name="valor_venda" value="<?= $sale['VALOR_VENDA'] ?>" class="border p-2 w-full rounded" required>
    </div>
    <div>
      <label class="block mb-1">Desconto</label>
      <input type="text" name="desconto" value="<?= $sale['DESCONTO'] ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Valor Total</label>
      <input type="text" name="valor_total" value="<?= $sale['VALOR_TOTAL'] ?>" class="border p-2 w-full rounded" required>
    </div>
    <div>
      <label class="block mb-1">Telefone Contato</label>
      <input type="text" name="telefone_contato" value="<?= htmlspecialchars($sale['TELEFONE_CONTATO']) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Responsável Contato</label>
      <input type="text" name="responsavel_contato" value="<?= htmlspecialchars($sale['RESPONSAVEL_CONTATO']) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">CEP Entrega</label>
      <input type="text" name="cep_entrega" value="<?= $sale['CEP_ENTREGA'] ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Rua Entrega</label>
      <input type="text" name="rua_entrega" value="<?= htmlspecialchars($sale['RUA_ENTREGA']) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Bairro Entrega</label>
      <input type="text" name="bairro_entrega" value="<?= htmlspecialchars($sale['BAIRRO_ENTREGA']) ?>" class="border p-2 w-full rounded">
    </div>
    <div>
      <label class="block mb-1">Cidade</label>
      <select name="id_cidade" class="border p-2 w-full rounded">
        <option value="">Selecione</option>
        <?php foreach($cidades as $c): ?>
          <option value="<?= $c['ID'] ?>" <?= $sale['ID_CIDADE']==$c['ID']?'selected':'' ?>><?= htmlspecialchars($c['NOME']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <label class="block mb-1">Observação</label>
      <textarea name="observacao" class="border p-2 w-full rounded" rows="3"><?= htmlspecialchars($sale['OBSERVACAO']) ?></textarea>
    </div>
    <div>
      <label class="block mb-1">Status</label>
      <select name="status" class="border p-2 w-full rounded">
        <option value="CONCLUÍDA" <?= $sale['STATUS']=='CONCLUÍDA'?'selected':'' ?>>Concluída</option>
        <option value="CANCELADA" <?= $sale['STATUS']=='CANCELADA'?'selected':'' ?>>Cancelada</option>
        <option value="COTAÇÃO" <?= $sale['STATUS']=='COTAÇÃO'?'selected':'' ?>>Cotação</option>
        <option value="PENDENTE" <?= $sale['STATUS']=='PENDENTE'?'selected':'' ?>>Pendente</option>
      </select>
    </div>
    <div>
      <label class="block mb-1">Empresa</label>
      <select name="id_empresa" class="border p-2 w-full rounded" required>
        <option value="">Selecione</option>
        <?php foreach($empresas as $e): ?>
          <option value="<?= $e['ID'] ?>" <?= $sale['ID_EMPRESA']==$e['ID']?'selected':'' ?>><?= htmlspecialchars($e['NOME_FANTASIA']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="md:col-span-2">
      <button class="bg-primary text-white px-4 py-2 rounded" type="submit">Salvar</button>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>
