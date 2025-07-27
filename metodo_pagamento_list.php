<?php
$pageTitle = 'Métodos de Pagamento';
include 'header.php';
$permissoes = $_SESSION['permissoes'];
$canDelete = hasRole('ADMINISTRADOR','DIRETORIA');
$stmt = $pdo->query("SELECT ID, NOME FROM METODO_PAGAMENTO");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$error = isset($_GET['erro']);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Métodos de Pagamento</h2>
    <button onclick="window.location.href='metodo_pagamento_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Novo Método</button>
  </div>
  <?php if($error): ?>
    <p class="text-red-600 mb-4">Não é possível excluir este registro pois está em uso.</p>
  <?php endif; ?>
  <table id="table" class="display w-full">
    <thead>
      <tr>
        <th>Ações</th>
        <th>ID</th>
        <th>Nome</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td class="table-actions">
          <a href="metodo_pagamento_form.php?id=<?= $it['ID'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <?php if($canDelete): ?>
            <a href="metodo_pagamento_delete.php?id=<?= $it['ID'] ?>" onclick="return confirm('Excluir?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($it['ID']) ?></td>
        <td><?= htmlspecialchars($it['NOME']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
