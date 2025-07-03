<?php
$pageTitle = 'Vendas';
include 'header.php';

$allowed = userCompanies($pdo);
if(!hasRole('ADMINISTRADOR','DIRETORIA') && $allowed){
    $in = implode(',', array_fill(0,count($allowed),'?'));
    $sql = "SELECT v.ID, DATE_FORMAT(v.DATA_VENDA,'%d/%m/%Y') AS DATA_VENDA, c.NOME AS CLIENTE, v.VALOR_TOTAL, v.STATUS FROM VENDAS v LEFT JOIN CLIENTE c ON c.ID=v.ID_CLIENTE WHERE v.ID_EMPRESA IN ($in) ORDER BY v.ID DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($allowed);
} else {
    $stmt = $pdo->query("SELECT v.ID, DATE_FORMAT(v.DATA_VENDA,'%d/%m/%Y') AS DATA_VENDA, c.NOME AS CLIENTE, v.VALOR_TOTAL, v.STATUS FROM VENDAS v LEFT JOIN CLIENTE c ON c.ID=v.ID_CLIENTE ORDER BY v.ID DESC");
}
$vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
$canDelete = hasRole('ADMINISTRADOR');
?>
<h2 class="text-2xl font-semibold mb-4">Cadastro de Vendas</h2>
<button class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" onclick="window.location.href='step1.php'">Nova Venda</button>
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
      <td class="border-t px-4 py-2">
        <select onchange="updateStatus(this, <?= $v['ID'] ?>)" class="border p-1 rounded status-select bg-opacity-20">
          <?php
            $statuses = ['CONCLUÍDA'=>'bg-green-100 text-green-800','CANCELADA'=>'bg-red-100 text-red-800','COTAÇÃO'=>'bg-blue-100 text-blue-800','PENDENTE'=>'bg-yellow-100 text-yellow-800'];
            foreach($statuses as $st=>$class){
              $selected = $v['STATUS']==$st ? 'selected' : '';
              echo "<option value='$st' class='$class' $selected>$st</option>";
            }
          ?>
        </select>
      </td>
      <td class="table-actions">
        <a href="vendas_form.php?id=<?= $v['ID'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
        <?php if($canDelete): ?>
        <a href="vendas_delete.php?id=<?= $v['ID'] ?>" onclick="return confirm('Excluir esta venda?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<script>
function updateStatus(sel,id){
  fetch('vendas_update_status.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`id=${id}&status=${encodeURIComponent(sel.value)}`
  }).then(()=>{
    setColor(sel);
  });
}
function setColor(sel){
  sel.classList.remove('bg-green-100','bg-red-100','bg-blue-100','bg-yellow-100','text-green-800','text-red-800','text-blue-800','text-yellow-800');
  switch(sel.value){
    case 'CONCLUÍDA': sel.classList.add('bg-green-100','text-green-800'); break;
    case 'CANCELADA': sel.classList.add('bg-red-100','text-red-800'); break;
    case 'COTAÇÃO': sel.classList.add('bg-blue-100','text-blue-800'); break;
    default: sel.classList.add('bg-yellow-100','text-yellow-800');
  }
}
document.querySelectorAll('.status-select').forEach(setColor);
</script>
<?php include 'footer.php'; ?>
