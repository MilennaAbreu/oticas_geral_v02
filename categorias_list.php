<?php
$pageTitle = 'Categorias';
include 'header.php';
$permissoes = $_SESSION['permissoes'];
$canDelete = strpos($permissoes,'ADMINISTRADOR')!==false || strpos($permissoes,'DIRETOR')!==false;
$stmt = $pdo->query("SELECT id, nome FROM CATEGORIAS");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Categorias</h2>
    <button onclick="window.location.href='categorias_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Novo Categorias</button>
  </div>
  <table id="table" class="display w-full">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['id']) ?></td>
        <td><?= htmlspecialchars($it['nome']) ?></td>
        <td>
          <a href="categorias_form.php?id=<?= $it['id'] ?>" class="text-accent hover:underline">Editar</a>
          <?php if($canDelete): ?>
            <a href="categorias_delete.php?id=<?= $it['id'] ?>" onclick="return confirm('Excluir?');" class="text-red-600 hover:underline ml-2">Deletar</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
