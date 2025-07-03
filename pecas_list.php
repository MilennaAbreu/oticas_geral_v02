<?php
$pageTitle = 'Pecas';
include 'header.php';
$permissoes = $_SESSION['permissoes'];
$canDelete = strpos($permissoes,'ADMINISTRADOR')!==false || strpos($permissoes,'DIRETOR')!==false;
$stmt = $pdo->query("SELECT id, nome, descricao, preco FROM PECAS");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Pecas</h2>
    <button onclick="window.location.href='pecas_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Novo Pecas</button>
  </div>
  <table id="table" class="display w-full">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Descrição</th>
        <th>Preço</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['id']) ?></td>
        <td><?= htmlspecialchars($it['nome']) ?></td>
        <td><?= htmlspecialchars($it['descricao']) ?></td>
        <td><?= htmlspecialchars($it['preco']) ?></td>
        <td class="table-actions">
          <a href="pecas_form.php?id=<?= $it['id'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <?php if($canDelete): ?>
            <a href="pecas_delete.php?id=<?= $it['id'] ?>" onclick="return confirm('Excluir?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
