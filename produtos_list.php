<?php
$pageTitle = 'Produtos';
include 'header.php';

$permissoes = $_SESSION['permissoes'];
$canDelete  = strpos($permissoes,'ADMINISTRADOR')!==false || strpos($permissoes,'DIRETOR')!==false;

$sql = "SELECT p.ID,
               p.NOME,
               c.NOME AS CATEGORIA,
               t.NOME AS TIPO,
               p.MARCA,
               p.CODIGO,
               p.UNIDADE_MEDIDA,
               p.VALOR_UNITARIO,
               p.ESTOQUE_ATUAL,
               p.STATUS,
               p.IMAGEM
        FROM PRODUTO p
        LEFT JOIN CATEGORIA_PRODUTO c ON p.ID_CATEGORIA = c.ID
        LEFT JOIN TIPO_PRODUTO t      ON p.ID_TIPO      = t.ID";

$items = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Produtos</h2>
    <button onclick="window.location.href='produtos_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Novo Produto</button>
  </div>
  <table id="produtosTable" class="display w-full">
    <thead>
      <tr><th>ID</th><th>Nome</th><th>Categoria</th><th>Tipo</th><th>Marca</th><th>Código</th><th>Un. Med.</th><th>Valor Unitário</th><th>Estoque</th><th>Status</th><th>Imagem</th><th>Ações</th></tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= $it['ID'] ?></td>
        <td><?= $it['NOME'] ?></td>
        <td><?= $it['CATEGORIA'] ?></td>
        <td><?= $it['TIPO'] ?></td>
        <td><?= $it['MARCA'] ?></td>
        <td><?= $it['CODIGO'] ?></td>
        <td><?= $it['UNIDADE_MEDIDA'] ?></td>
        <td><?= number_format($it['VALOR_UNITARIO'],2,',','.') ?></td>
        <td><?= $it['ESTOQUE_ATUAL'] ?></td>
        <td><?= $it['STATUS'] ?></td>
        <td><?php if($it['IMAGEM']): ?><img src="uploads/<?= $it['IMAGEM'] ?>" width="50"><?php endif; ?></td>
        <td>
          <a href="produtos_form.php?id=<?= $it['ID'] ?>" class="text-accent hover:underline">Editar</a>
          <?php if($canDelete): ?>
            <a href="produtos_delete.php?id=<?= $it['ID'] ?>" onclick="return confirm('Excluir?');" class="text-red-600 hover:underline ml-2">Deletar</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
