<?php
$pageTitle = 'Juros por Método e Condição';
include 'header.php';
$permissoes = $_SESSION['permissoes'];
$canDelete = hasRole('ADMINISTRADOR','DIRETORIA');
$items = $pdo->query("SELECT j.ID, m.NOME AS METODO, c.NOME AS CONDICAO, j.JUROS_MENSAL FROM JUROS_METODO_CONDICAO j JOIN METODO_PAGAMENTO m ON j.ID_METODO_PAGAMENTO=m.ID JOIN CONDICAO_PAGAMENTO c ON j.ID_CONDICAO=c.ID ORDER BY m.NOME,c.NOME")->fetchAll(PDO::FETCH_ASSOC);
$error = isset($_GET['erro']);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Juros por Método x Condição</h2>
    <button onclick="window.location.href='juros_metodo_condicao_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Novo</button>
  </div>
  <?php if($error): ?>
    <p class="text-red-600 mb-4">Não é possível excluir este registro pois está em uso.</p>
  <?php endif; ?>
  <table id="table" class="display w-full">
    <thead>
      <tr>
        <th>Ações</th>
        <th>ID</th>
        <th>Método</th>
        <th>Condição</th>
        <th>Juros %/mês</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td class="table-actions">
          <a href="juros_metodo_condicao_form.php?id=<?= $it['ID'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <?php if($canDelete): ?>
            <a href="juros_metodo_condicao_delete.php?id=<?= $it['ID'] ?>" onclick="return confirm('Excluir?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
          <?php endif; ?>
        </td>
        <td><?= $it['ID'] ?></td>
        <td><?= htmlspecialchars($it['METODO']) ?></td>
        <td><?= htmlspecialchars($it['CONDICAO']) ?></td>
        <td><?= number_format($it['JUROS_MENSAL'],2,',','.') ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
