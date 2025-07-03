<?php
$pageTitle = 'Vendas';
include 'header.php';

$stmt = $pdo->query("SELECT v.ID, DATE_FORMAT(v.DATA_VENDA,'%d/%m/%Y') AS DATA_VENDA, c.NOME AS CLIENTE, v.VALOR_TOTAL, v.STATUS FROM VENDAS v LEFT JOIN CLIENTE c ON c.ID=v.ID_CLIENTE ORDER BY v.ID DESC");
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2 class="text-2xl font-semibold mb-4">Cadastro de Vendas</h2>
<button class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" onclick="window.location.href='vendas_form.php'">Nova Venda</button>
<table id="vendaTable" class="display" style="width:100%; margin-top:10px;">
  <thead>
    <tr>
      <th>ID</th>
      <th>Data</th>
      <th>Cliente</th>
      <th>Valor Total</th>
      <th>Status</th>
      <th>Ações</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($vendas as $v): ?>
    <tr>
      <td class="border-t px-4 py-2"><?= $v['ID'] ?></td>
      <td class="border-t px-4 py-2"><?= $v['DATA_VENDA'] ?></td>
      <td class="border-t px-4 py-2"><?= htmlspecialchars($v['CLIENTE']) ?></td>
      <td class="border-t px-4 py-2">R$ <?= number_format($v['VALOR_TOTAL'],2,',','.') ?></td>
      <td class="border-t px-4 py-2"><?= htmlspecialchars($v['STATUS']) ?></td>
      <td class="table-actions">
        <a href="vendas_form.php?id=<?= $v['ID'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
        <a href="vendas_delete.php?id=<?= $v['ID'] ?>" onclick="return confirm('Excluir esta venda?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include 'footer.php'; ?>
