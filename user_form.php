<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'permissions.php';
$id = $_GET['id'] ?? null;
requireRole('ADMINISTRADOR','DIRETORIA');

$nome = $username = $permissoes = '';
$error = '';
$empresas_selected = [];

// Fetch empresas and perfis
$emps = $pdo->query("SELECT id, nome FROM EMPRESA ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$perfis = ['ADMINISTRADOR','DIRETORIA','ADMINISTRATIVO','VENDEDOR'];

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $username = $_POST['username'];
    $passInput = $_POST['password'] ?? '';
    $hashed = $passInput !== '' ? password_hash($passInput, PASSWORD_DEFAULT) : null;
    $permissoes = $_POST['permissoes'];
    $empresas_selected = $_POST['empresas'] ?? [];

    if(hasRole('DIRETORIA') && $permissoes === 'ADMINISTRADOR'){
        $error = 'Diretoria não pode atribuir perfil ADMINISTRADOR';
    } elseif ($id) {
        if ($hashed) {
            $stmt = $pdo->prepare("UPDATE USUARIO SET nome=?, username=?, senha=?, permissoes=? WHERE id=?");
            $stmt->execute([$nome, $username, $hashed, $permissoes, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE USUARIO SET nome=?, username=?, permissoes=? WHERE id=?");
            $stmt->execute([$nome, $username, $permissoes, $id]);
        }
        $pdo->prepare("DELETE FROM USUARIO_EMPRESA WHERE id_usuario=?")->execute([$id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO USUARIO (nome, username, senha, permissoes) VALUES (?,?,?,?)");
        $stmt->execute([$nome, $username, $hashed, $permissoes]);
        $id = $pdo->lastInsertId();
    }

    if (!$error) {
        $ins = $pdo->prepare("INSERT INTO USUARIO_EMPRESA (id_usuario, id_empresa) VALUES (?,?)");
        foreach ($empresas_selected as $e) { $ins->execute([$id, $e]); }
        header('Location: user_list.php');
        exit();
    }
}

// Load existing
if ($id) {
    $stmt = $pdo->prepare("SELECT nome, username, permissoes FROM USUARIO WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nome = $row['nome'];
    $username = $row['username'];
    $permissoes = $row['permissoes'];
    $sel = $pdo->prepare("SELECT id_empresa FROM USUARIO_EMPRESA WHERE id_usuario=?");
    $sel->execute([$id]);
    $empresas_selected = $sel->fetchAll(PDO::FETCH_COLUMN);
}
$pageTitle = $id ? 'Editar Usuário' : 'Novo Usuário';
include 'header.php';
?>
<div class="container mx-auto">
    <?php if($error): ?>
    <p class="text-red-600 mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
    <form id="userForm" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block mb-1">Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Login</label>
            <input type="text" name="username" value="<?= htmlspecialchars($username) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Senha <?= $id ? '(Digite para alterar)' : '' ?></label>
            <input type="password" name="password" <?= $id ? '' : 'required' ?> class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Permissões</label>
            <select name="permissoes" class="border-b-2 border-gray-300 px-3 py-2 w-full" required>
                <?php foreach ($perfis as $p): ?>
                <option value="<?= $p ?>" <?= $p == $permissoes ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block mb-1">Empresas</label>
            <select name="empresas[]" id="empresas" multiple required class="border-b-2 border-gray-300 px-3 py-2 w-full">
                <?php foreach ($emps as $e): ?>
                <option value="<?= $e['id'] ?>" <?= in_array($e['id'], $empresas_selected) ? 'selected' : '' ?>><?= htmlspecialchars($e['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id ? 'Atualizar' : 'Salvar' ?></button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#cidade, #empresas').select2({ width: '100%' });
});
</script>
<?php include 'footer.php'; ?>
