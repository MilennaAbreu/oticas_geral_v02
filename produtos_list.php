<?php
$pageTitle = 'Produtos';
include 'header.php';

$permissoes = $_SESSION['permissoes'];
$canDelete  = hasRole('ADMINISTRADOR','DIRETORIA');

$sql = "SELECT p.ID,
               p.NOME,
               c.NOME AS CATEGORIA,
               t.NOME AS TIPO,
               m.NOME AS MARCA,
               p.CODIGO,
               p.UNIDADE_MEDIDA,
               p.VALOR_COMPRA,
               p.VALOR_UNITARIO,
               p.ESTOQUE_ATUAL,
               p.STATUS,
               p.IMAGEM,
               e.NOME AS EMPRESA
        FROM PRODUTO p
        LEFT JOIN CATEGORIA_PRODUTO c ON p.ID_CATEGORIA = c.ID
        LEFT JOIN TIPO_PRODUTO t      ON p.ID_TIPO      = t.ID
        LEFT JOIN MARCA_PRODUTO m     ON p.ID_MARCA     = m.ID
        LEFT JOIN EMPRESA e           ON p.ID_EMPRESA   = e.ID";

$items = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$error = isset($_GET['erro']);
?>
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Produtos</h2>
    <button onclick="window.location.href='produtos_form.php'" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Novo Produto</button>
  </div>
  <?php if($error): ?>
    <p class="text-red-600 mb-4">Não é possível excluir este registro pois está em uso.</p>
  <?php endif; ?>
  <table id="produtosTable" class="display w-full">
    <thead>
      <tr><th>Ações</th><th>ID</th><th>Nome</th><th>Categoria</th><th>Tipo</th><th>Marca</th><th>Código</th><th>Un. Med.</th><th>Valor Compra</th><th>Valor Venda</th><th>Estoque</th><th>Empresa</th><th>Status</th><th>Imagem</th></tr>
    </thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td class="table-actions">
          <a href="produtos_form.php?id=<?= $it['ID'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
          <a href="produtos_copy.php?id=<?= $it['ID'] ?>" class="edit" title="Copiar"><i class="fas fa-copy"></i></a>
          <?php if($canDelete): ?>
            <a href="produtos_delete.php?id=<?= $it['ID'] ?>" onclick="return confirm('Excluir?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
          <?php endif; ?>
        </td>
        <td><?= $it['ID'] ?></td>
        <td><?= $it['NOME'] ?></td>
        <td><?= $it['CATEGORIA'] ?></td>
        <td><?= $it['TIPO'] ?></td>
        <td><?= $it['MARCA'] ?></td>
        <td><?= $it['CODIGO'] ?></td>
        <td><?= $it['UNIDADE_MEDIDA'] ?></td>
        <td><?= number_format($it['VALOR_COMPRA'],2,',','.') ?></td>
        <td><?= number_format($it['VALOR_UNITARIO'],2,',','.') ?></td>
        <td><?= $it['ESTOQUE_ATUAL'] ?></td>
        <td><?= $it['EMPRESA'] ?></td>
        <td><?= $it['STATUS'] ?></td>
        <td><?php if($it['IMAGEM']): ?><img src="uploads/<?= $it['IMAGEM'] ?>" width="50"><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
