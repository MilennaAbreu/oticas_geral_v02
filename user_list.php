<?php
$pageTitle = 'Usuários';
include 'header.php';

// User list logic
$permissoes = $_SESSION['permissoes'];
$canDelete = strpos($permissoes, 'ADMINISTRADOR') !== false || strpos($permissoes, 'DIRETOR') !== false;
try {
    $stmt = $pdo->query("SELECT u.id, u.nome, u.username, u.permissoes, 
        GROUP_CONCAT(e.nome SEPARATOR ', ') AS empresas
        FROM USUARIO u
        LEFT JOIN USUARIO_EMPRESA ue ON ue.id_usuario = u.id
        LEFT JOIN EMPRESA e ON e.id = ue.id_empresa
        GROUP BY u.id, u.nome, u.username, u.permissoes");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stmt = $pdo->query("SELECT id, nome, username, permissoes FROM USUARIO");
    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['empresas'] = '';
        $users[] = $row;
    }
}
?>
<h2>Cadastro de Usuários</h2>
<button class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" onclick="window.location.href='user_form.php'">Novo Usuário</button>
<table id="userTable" class="display" style="width:100%; margin-top:10px;" class="min-w-full bg-white rounded shadow overflow-hidden">
    <thead>
        <tr><th class="bg-secondary text-white px-4 py-2">ID</th><th class="bg-secondary text-white px-4 py-2">Nome</th><th class="bg-secondary text-white px-4 py-2">Login</th><th class="bg-secondary text-white px-4 py-2">Permissões</th><th class="bg-secondary text-white px-4 py-2">Empresas</th><th class="bg-secondary text-white px-4 py-2">Ações</th></tr>
    </thead>
    <tbody>
        <?php foreach($users as $u): ?>
        <tr>
            <td class="border-t px-4 py-2"><?= $u['id'] ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($u['nome']) ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($u['permissoes']) ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($u['empresas']) ?></td>
            <td>
                <a href="user_form.php?id=<?= $u['id'] ?>"><i class="fas fa-edit"></i></a>
                <?php if($canDelete): ?>
                <a href="user_delete.php?id=<?= $u['id'] ?>" onclick="return confirm('Excluir este usuário?');"><i class="fas fa-trash-alt"></i></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<script>
$(document).ready(function() {
    $('#userTable').DataTable({ paging: true, searching: true, info: true });
});
</script>
<?php include 'footer.php'; ?>
