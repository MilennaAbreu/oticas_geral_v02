<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'permissions.php';
$id = $_GET['id'] ?? null;
requireRole('ADMINISTRADOR','DIRETORIA');

$nome = $username = $permissoes = '';
$cpf = $nascimento = $admissao = '';
$status = 'ATIVO';
$cep = $rua = $bairro = $id_cidade = $contato = '';
$error = '';
$empresas_selected = [];

// Fetch empresas and perfis
$emps = $pdo->query("SELECT id, nome FROM EMPRESA ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$perfis = ['ADMINISTRADOR','DIRETORIA','ADMINISTRATIVO','VENDEDOR'];
$cidades = $pdo->query("SELECT id, CONCAT(nome,'/',uf) AS nome FROM CIDADE ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome       = $_POST['nome'] ?? '';
    $username   = $_POST['username'] ?? '';
    $passInput  = $_POST['password'] ?? '';
    $hashed     = $passInput !== '' ? password_hash($passInput, PASSWORD_DEFAULT) : null;
    $cpf        = preg_replace('/\D/','', $_POST['cpf'] ?? '');
    $nascimento = $_POST['nascimento'] ?? '';
    $admissao   = $_POST['admissao'] ?? '';
    $permissoes = $_POST['permissoes'] ?? '';
    $status     = $_POST['status'] ?? 'ATIVO';
    $cep        = preg_replace('/\D/','', $_POST['cep'] ?? '');
    $rua        = $_POST['rua'] ?? '';
    $bairro     = $_POST['bairro'] ?? '';
    $id_cidade  = $_POST['id_cidade'] ?? '';
    $contato    = $_POST['contato'] ?? '';
    $empresas_selected = $_POST['empresas'] ?? [];

    if(hasRole('DIRETORIA') && $permissoes === 'ADMINISTRADOR'){
        $error = 'Diretoria não pode atribuir perfil ADMINISTRADOR';
    } else {
        $dup = $pdo->prepare('SELECT id FROM USUARIO WHERE username=? AND id<>?');
        $dup->execute([$username, $id ?? 0]);
        if($dup->fetch()) $error = 'Login já utilizado por outro usuário';
        if(!$error){
            $dup = $pdo->prepare('SELECT id FROM USUARIO WHERE cpf=? AND id<>?');
            $dup->execute([$cpf, $id ?? 0]);
            if($dup->fetch()) $error = 'CPF já cadastrado para outro usuário';
        }
        try {
            if ($id) {
                if ($hashed) {
                    $stmt = $pdo->prepare("UPDATE USUARIO SET nome=?, username=?, senha=?, cpf=?, data_nascimento=?, data_admissao=?, permissoes=?, status=?, cep=?, rua=?, bairro=?, id_cidade=?, contato=?, data_atualizacao=NOW() WHERE id=?");
                    $stmt->execute([$nome,$username,$hashed,$cpf,$nascimento,$admissao,$permissoes,$status,$cep,$rua,$bairro,$id_cidade,$contato,$id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE USUARIO SET nome=?, username=?, cpf=?, data_nascimento=?, data_admissao=?, permissoes=?, status=?, cep=?, rua=?, bairro=?, id_cidade=?, contato=?, data_atualizacao=NOW() WHERE id=?");
                    $stmt->execute([$nome,$username,$cpf,$nascimento,$admissao,$permissoes,$status,$cep,$rua,$bairro,$id_cidade,$contato,$id]);
                }
                $pdo->prepare("DELETE FROM USUARIO_EMPRESA WHERE USUARIO_ID=?")->execute([$id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO USUARIO (nome, username, senha, cpf, data_nascimento, data_admissao, permissoes, status, cep, rua, bairro, id_cidade, contato, data_criacao) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())");
                $stmt->execute([$nome,$username,$hashed,$cpf,$nascimento,$admissao,$permissoes,$status,$cep,$rua,$bairro,$id_cidade,$contato]);
                $id = $pdo->lastInsertId();
            }

            if (!$error) {
                $ins = $pdo->prepare("INSERT INTO USUARIO_EMPRESA (USUARIO_ID, EMPRESA_ID) VALUES (?,?)");
                foreach ($empresas_selected as $e) { $ins->execute([$id, $e]); }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao salvar usuário: ' . $e->getMessage();
        }

        if (!$error) {
            header('Location: user_list.php');
            exit();
        }
    }
}

// Load existing
if ($id) {
    $stmt = $pdo->prepare("SELECT nome, username, cpf, DATE_FORMAT(data_nascimento,'%Y-%m-%d') as nasc, DATE_FORMAT(data_admissao,'%Y-%m-%d') as adm, permissoes, status, cep, rua, bairro, id_cidade, contato FROM USUARIO WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $nome = $row['nome'];
    $username = $row['username'];
    $cpf = $row['cpf'];
    $nascimento = $row['nasc'];
    $admissao = $row['adm'];
    $permissoes = $row['permissoes'];
    $status = $row['status'];
    $cep = $row['cep'];
    $rua = $row['rua'];
    $bairro = $row['bairro'];
    $id_cidade = $row['id_cidade'];
    $contato = $row['contato'];
    $sel = $pdo->prepare("SELECT EMPRESA_ID FROM USUARIO_EMPRESA WHERE USUARIO_ID=?");
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
            <label class="block mb-1">CPF</label>
            <input type="text" name="cpf" value="<?= htmlspecialchars($cpf) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full" required>
        </div>
        <div>
            <label class="block mb-1">Data Nascimento</label>
            <input type="date" name="nascimento" value="<?= $nascimento ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Data Admissão</label>
            <input type="date" name="admissao" value="<?= $admissao ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
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
            <label class="block mb-1">Status</label>
            <select name="status" class="border-b-2 border-gray-300 px-3 py-2 w-full">
                <option value="ATIVO" <?= $status=='ATIVO'?'selected':'' ?>>Ativo</option>
                <option value="INATIVO" <?= $status=='INATIVO'?'selected':'' ?>>Inativo</option>
            </select>
        </div>
        <div>
            <label class="block mb-1">CEP</label>
            <input type="text" name="cep" value="<?= htmlspecialchars($cep) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full" required>
        </div>
        <div>
            <label class="block mb-1">Rua</label>
            <input type="text" name="rua" value="<?= htmlspecialchars($rua) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Bairro</label>
            <input type="text" name="bairro" value="<?= htmlspecialchars($bairro) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Cidade</label>
            <select name="id_cidade" id="cidade" class="border-b-2 border-gray-300 px-3 py-2 w-full" required>
                <option value="">Selecione cidade</option>
                <?php foreach($cidades as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id']==$id_cidade?'selected':'' ?>><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block mb-1">Contato</label>
            <input type="text" name="contato" value="<?= htmlspecialchars($contato) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
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
