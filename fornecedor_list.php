<?php
$pageTitle = 'Fornecedores';
include 'header.php';

$stmt = $pdo->query("SELECT f.id, f.nome, f.cnpj, f.contato, f.cep, f.rua, f.bairro, CONCAT(ci.nome,'/',ci.uf) as cidade, f.status FROM FORNECEDOR f LEFT JOIN CIDADE ci ON ci.id=f.id_cidade");
$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Fornecedores</h2>
    <button class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" onclick="window.location.href='fornecedor_form.php'">Novo Fornecedor</button>
  </div>
  <table id="forneTable" class="display w-full">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>CNPJ</th>
        <th>Contato</th>
        <th>Cidade</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($fornecedores as $f): ?>
      <tr>
        <td class="border-t px-4 py-2"><?= $f['id'] ?></td>
        <td class="border-t px-4 py-2"><?= htmlspecialchars($f['nome']) ?></td>
        <td class="border-t px-4 py-2"><?= $f['cnpj'] ?></td>
        <td class="border-t px-4 py-2"><?= htmlspecialchars($f['contato']) ?></td>
        <td class="border-t px-4 py-2"><?= htmlspecialchars($f['cidade']) ?></td>
        <td class="border-t px-4 py-2"><?= htmlspecialchars($f['status']) ?></td>
        <td class="table-actions">
          <a href="fornecedor_form.php?id=<?= $f['id'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <a href="fornecedor_delete.php?id=<?= $f['id'] ?>" onclick="return confirm('Excluir este fornecedor?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
