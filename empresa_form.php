<?php
$id = $_GET['id'] ?? null;
$pageTitle = $id ? 'Editar Empresa' : 'Nova Empresa';
include 'header.php';

// Initialize
$nome = $cnpj = $cep = $endereco = $telefone = $status = '';
$id_cidade = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cnpj = preg_replace('/\D/', '', $_POST['cnpj']);
    $cep = preg_replace('/\D/', '', $_POST['cep']);
    $endereco = $_POST['endereco'];
    $id_cidade = $_POST['id_cidade'];
    $telefone = $_POST['telefone'];
    $status = $_POST['status'];
    if ($id) {
        $stmt = $pdo->prepare("UPDATE EMPRESA SET nome=?, cnpj=?, cep=?, endereco=?, id_cidade=?, telefone=?, status=? WHERE id=?");
        $stmt->execute([$nome, $cnpj, $cep, $endereco, $id_cidade, $telefone, $status, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO EMPRESA (nome, cnpj, cep, endereco, id_cidade, telefone, status) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$nome, $cnpj, $cep, $endereco, $id_cidade, $telefone, $status]);
        $id = $pdo->lastInsertId();
    }
    header('Location: empresa_list.php');
    exit();
}

// Load existing
if ($id) {
    $stmt = $pdo->prepare("SELECT nome, cnpj, cep, endereco, id_cidade, telefone, status FROM EMPRESA WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $nome = $row['nome'];
        $cnpj = $row['cnpj'];
        $cep = $row['cep'];
        $endereco = $row['endereco'];
        $id_cidade = $row['id_cidade'];
        $telefone = $row['telefone'];
        $status = $row['status'];
    }
}

// Fetch cities
$cidades = $pdo->query("SELECT id, CONCAT(nome,'/',uf) AS nome FROM CIDADE ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container mx-auto">
    <h2 class="text-2xl font-semibold mb-4"><?= htmlspecialchars($pageTitle) ?></h2>
    <form method="post" id="empresaForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block mb-1">Nome</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">CNPJ</label>
            <input type="text" name="cnpj" id="cnpj" value="<?= htmlspecialchars($cnpj) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">CEP</label>
            <input type="text" name="cep" id="cep" value="<?= htmlspecialchars($cep) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Endereço</label>
            <input type="text" name="endereco" id="endereco" value="<?= htmlspecialchars($endereco) ?>" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Cidade</label>
            <select name="id_cidade" id="cidade" required class="border-b-2 border-gray-300 px-3 py-2 w-full">
                <option value="">Selecione cidade</option>
                <?php foreach($cidades as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id'] == $id_cidade ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block mb-1">Telefone</label>
            <input type="text" name="telefone" value="<?= htmlspecialchars($telefone) ?>" class="border-b-2 border-gray-300 px-3 py-2 w-full">
        </div>
        <div>
            <label class="block mb-1">Status</label>
            <select name="status" class="border-b-2 border-gray-300 px-3 py-2 w-full">
                <option value="ativo" <?= $status == 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= $status == 'inativo' ? 'selected' : '' ?>>Inativo</option>
            </select>
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition"><?= $id ? 'Atualizar' : 'Salvar' ?></button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cepField = document.getElementById('cep');
    const enderecoField = document.getElementById('endereco');
    $('#cidade').select2({ width: '100%' });
    $('#cnpj').mask('00.000.000/0000-00');
    $('#cep').mask('00.000-000');
    cepField.addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length === 8) {
            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                .then(res => res.json())
                .then(data => {
                    if (!data.erro) enderecoField.value = data.logradouro + ', ' + data.bairro + ', ' + data.localidade + '/' + data.uf;
                });
        }
    });
});
</script>
<?php include 'footer.php'; ?>
