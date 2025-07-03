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
<div class="container mx-auto">
  <div class="flex justify-between items-center mb-4">
    <h2 class="text-2xl font-semibold">Cadastro de Usuários</h2>
    <button class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" onclick="window.location.href='user_form.php'">Novo Usuário</button>
  </div>
  <table id="userTable" class="display w-full">
    <thead>
        <tr><th>ID</th><th>Nome</th><th>Login</th><th>Permissões</th><th>Empresas</th><th>Ações</th></tr>
    </thead>
    <tbody>
        <?php foreach($users as $u): ?>
        <tr>
            <td class="border-t px-4 py-2"><?= $u['id'] ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($u['nome']) ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($u['username']) ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($u['permissoes']) ?></td>
            <td class="border-t px-4 py-2"><?= htmlspecialchars($u['empresas']) ?></td>
            <td class="table-actions">
                <a href="user_form.php?id=<?= $u['id'] ?>" class="edit" title="Editar"><i class="fas fa-edit"></i></a>
                <?php if($canDelete): ?>
                <a href="user_delete.php?id=<?= $u['id'] ?>" onclick="return confirm('Excluir este usuário?');" class="delete" title="Deletar"><i class="fas fa-trash-alt"></i></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php include 'footer.php'; ?>
