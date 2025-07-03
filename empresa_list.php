<?php
$pageTitle = 'Empresas';
include 'header.php';
$permissoes = $_SESSION['permissoes'];
$canDelete = strpos($permissoes,'ADMINISTRADOR')!==false||strpos($permissoes,'DIRETOR')!==false||strpos($permissoes,'ADMINISTRATIVO')!==false;
$stmt = $pdo->query("SELECT id,nome,endereco,telefone FROM EMPRESA");
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Empresas</h2>
    <button onclick="window.location.href='empresa_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Nova Empresa</button>
  </div>
  <table id="empresaTable" class="display w-full">
    <thead><tr><th>ID</th><th>Nome</th><th>Endereço</th><th>Telefone</th><th>Ações</th></tr></thead>
    <tbody>
    <?php foreach($empresas as $e): ?>
      <tr>
        <td><?= $e['id'] ?></td>
        <td><?= htmlspecialchars($e['nome']) ?></td>
        <td><?= htmlspecialchars($e['endereco']) ?></td>
        <td><?= htmlspecialchars($e['telefone']) ?></td>
        <td class="table-actions">
          <a href="empresa_form.php?id=<?= $e['id'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <?php if($canDelete): ?>
            <a href="empresa_delete.php?id=<?= $e['id'] ?>" onclick="return confirm('Excluir?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
