<?php
$pageTitle = 'Clientes';
include 'header.php';
// Fetch clients
$stmt = $pdo->query("SELECT c.id, c.nome, c.cpf, DATE_FORMAT(c.data_nascimento, '%d/%m/%Y') as nascimento, c.cep, c.rua, c.bairro, CONCAT(ci.nome, '/', ci.uf) as cidade, c.contato, c.status
    FROM CLIENTE c
    LEFT JOIN CIDADE ci ON ci.id = c.id_cidade");
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Cadastro de Clientes</h2>
<button class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" onclick="window.location.href='cliente_form.php'">Novo Cliente</button>
<table id="clienteTable" class="display" style="width:100%; margin-top:10px;" class="min-w-full bg-white rounded shadow overflow-hidden">
    <thead><tr>
        <th>ID</th><th>Nome</th><th>CPF</th><th>Nascimento</th><th>CEP</th><th>Cidade</th><th>Contato</th><th>Status</th><th>Ações</th>
    </tr></thead>
    <tbody>
    <?php foreach($clientes as $c): ?>
        <tr>
            <td class="border-t px-4 py-2"><?= $c['id'] ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($c['nome']) ?></td>
            <td class="border-t px-4 py-2"><?= $c['cpf'] ?></td>
            <td class="border-t px-4 py-2"><?= $c['nascimento'] ?></td>
            <td class="border-t px-4 py-2"><?= $c['cep'] ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($c['cidade']) ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($c['contato']) ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($c['status']) ?></td>
            <td class="table-actions">
                <a href="cliente_form.php?id=<?= $c['id'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
                <a href="cliente_delete.php?id=<?= $c['id'] ?>" onclick="return confirm('Excluir este cliente?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php include 'footer.php'; ?>
