<?php
$pageTitle = 'Frete';
include 'header.php';
$permissoes = $_SESSION['permissoes'];
$canDelete = hasRole('ADMINISTRADOR','DIRETORIA');
$stmt = $pdo->query("SELECT id, descricao, valor, status FROM FRETE");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$error = isset($_GET['erro']);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Fretes</h2>
    <button onclick="window.location.href='frete_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Novo Frete</button>
  </div>
  <?php if($error): ?>
    <p class="text-red-600 mb-4">Não é possível excluir este registro pois está em uso.</p>
  <?php endif; ?>
  <table id="table" class="display w-full">
    <thead>
      <tr>
        <th>ID</th>
        <th>Descrição</th>
        <th>Valor</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['id']) ?></td>
        <td><?= htmlspecialchars($it['descricao']) ?></td>
        <td><?= number_format($it['valor'],2,',','.') ?></td>
        <td><?= htmlspecialchars($it['status']) ?></td>
        <td class="table-actions">
          <a href="frete_form.php?id=<?= $it['id'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <?php if($canDelete): ?>
            <a href="frete_delete.php?id=<?= $it['id'] ?>" onclick="return confirm('Excluir?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
