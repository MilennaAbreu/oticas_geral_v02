<?php
$pageTitle = 'Condições de Pagamento';
include 'header.php';
$permissoes = $_SESSION['permissoes'];
$canDelete = strpos($permissoes,'ADMINISTRADOR')!==false || strpos($permissoes,'DIRETOR')!==false;
$hasJuros = false;
try {
    $chk = $pdo->query("SHOW COLUMNS FROM CONDICAO_PAGAMENTO LIKE 'juros'");
    $hasJuros = $chk->fetch(PDO::FETCH_ASSOC) ? true : false;
} catch (PDOException $e) {
    $hasJuros = false;
}
if ($hasJuros) {
    $stmt = $pdo->query("SELECT id, nome, juros, condicao FROM CONDICAO_PAGAMENTO");
} else {
    $stmt = $pdo->query("SELECT id, nome, condicao FROM CONDICAO_PAGAMENTO");
}
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$error = isset($_GET['erro']);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Condições de Pagamento</h2>
    <button onclick="window.location.href='condicoes_pagamento_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Nova Condição</button>
  </div>
  <?php if($error): ?>
    <p class="text-red-600 mb-4">Não é possível excluir este registro pois está em uso.</p>
  <?php endif; ?>
  <table id="table" class="display w-full">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <?php if($hasJuros): ?><th>Juros</th><?php endif; ?>
        <th>Condição</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['id']) ?></td>
        <td><?= htmlspecialchars($it['nome']) ?></td>
        <?php if($hasJuros): ?><td><?= number_format($it['juros'],2,',','.') ?>%</td><?php endif; ?>
        <td><?= htmlspecialchars($it['condicao']) ?></td>
        <td class="table-actions">
          <a href="condicoes_pagamento_form.php?id=<?= $it['id'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <?php if($canDelete): ?>
            <a href="condicoes_pagamento_delete.php?id=<?= $it['id'] ?>" onclick="return confirm('Excluir?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
