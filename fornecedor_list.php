<?php
$pageTitle='Fornecedores';
include 'header.php';
$stmt = $pdo->query("SELECT f.id, f.nome, f.cnpj, f.contato, f.cep, f.rua, f.bairro, CONCAT(ci.nome,'/',ci.uf) as cidade, f.status 
    FROM FORNECEDOR f LEFT JOIN CIDADE ci ON ci.id=f.id_cidade");
$fornecedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Cadastro de Fornecedores</h2>
<button class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" onclick="window.location.href='fornecedor_form.php'">Novo Fornecedor</button>
<table id="forneTable" class="display" style="width:100%;margin-top:10px;" class="min-w-full bg-white rounded shadow overflow-hidden">
<thead><tr><th class="bg-secondary text-white px-4 py-2">ID</th><th class="bg-secondary text-white px-4 py-2">Nome</th><th class="bg-secondary text-white px-4 py-2">CNPJ</th><th class="bg-secondary text-white px-4 py-2">Contato</th><th class="bg-secondary text-white px-4 py-2">Cidade</th><th class="bg-secondary text-white px-4 py-2">Status</th><th class="bg-secondary text-white px-4 py-2">Ações</th></tr></thead>
<tbody>
<?php foreach($fornecedores as $f): ?>
<tr>
<td class="border-t px-4 py-2"><?=$f['id']?></td>
<td class="border-t px-4 py-2"><?=htmlspecialchars($f['nome'])?></td>
<td class="border-t px-4 py-2"><?=$f['cnpj']?></td>
<td class="border-t px-4 py-2"><?=htmlspecialchars($f['contato'])?></td>
<td class="border-t px-4 py-2"><?=htmlspecialchars($f['cidade'])?></td>
<td class="border-t px-4 py-2"><?=htmlspecialchars($f['status'])?></td>
<td>
<a href="fornecedor_form.php?id=<?=$f['id']?>"><i class="fas fa-edit"></i></a>
<a href="fornecedor_delete.php?id=<?=$f['id']?>" onclick="return confirm('Excluir este fornecedor?');"><i class="fas fa-trash-alt"></i></a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<script>$(document).ready(()=>$('#forneTable').DataTable());</script>
<?php include 'footer.php'; ?>