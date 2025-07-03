<?php
$pageTitle = 'Cliente Form';
include 'header.php';
$id = $_GET['id'] ?? null;
$nome = $cpf = $nascimento = $cep = $rua = $bairro = $contato = $status = '';
$id_cidade = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nome = $_POST['nome'];
    $cpf = preg_replace('/\D/','',$_POST['cpf']);
    $nascimento = $_POST['nascimento'];
    $cep = preg_replace('/\D/','',$_POST['cep']);
    $rua = $_POST['rua'];
    $bairro = $_POST['bairro'];
    $id_cidade = $_POST['id_cidade'];
    $contato = $_POST['contato'];
    $status = $_POST['status'];
    if($id){
        $stmt = $pdo->prepare("UPDATE CLIENTE SET nome=?, cpf=?, data_nascimento=?, cep=?, rua=?, bairro=?, id_cidade=?, contato=?, status=? WHERE id=?");
        $stmt->execute([$nome,$cpf,$nascimento,$cep,$rua,$bairro,$id_cidade,$contato,$status,$id]);
    }else{
        $stmt = $pdo->prepare("INSERT INTO CLIENTE (nome,cpf,data_nascimento,cep,rua,bairro,id_cidade,contato,status) VALUES(?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$nome,$cpf,$nascimento,$cep,$rua,$bairro,$id_cidade,$contato,$status]);
        $id = $pdo->lastInsertId();
    }
    header('Location: cliente_list.php'); exit();
}
if($id){
    $stmt = $pdo->prepare("SELECT nome,cpf,DATE_FORMAT(data_nascimento,'%Y-%m-%d') as nascimento,cep,rua,bairro,id_cidade,contato,status FROM CLIENTE WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row){
        $nome=$row['nome'];$cpf=$row['cpf'];$nascimento=$row['nascimento'];
        $cep=$row['cep'];$rua=$row['rua'];$bairro=$row['bairro'];
        $id_cidade=$row['id_cidade'];$contato=$row['contato'];$status=$row['status'];
    }
}
$cidades = $pdo->query("SELECT id, CONCAT(nome,'/',uf) as nome FROM CIDADE ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2><?= $id?'Editar':'Novo' ?> Cliente</h2>
<form id="clienteForm" method="post">
    <label>Nome</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" required>
    <label>CPF</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" id="cpf" name="cpf" value="<?= htmlspecialchars($cpf) ?>" required>
    <label>Data Nascimento</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="date" name="nascimento" value="<?= $nascimento ?>">
    <label>CEP</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" id="cep" name="cep" value="<?= htmlspecialchars($cep) ?>" required>
    <label>Rua</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" name="rua" value="<?= htmlspecialchars($rua) ?>">
    <label>Bairro</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" name="bairro" value="<?= htmlspecialchars($bairro) ?>">
    <label>Cidade</label><select class="border-b-2 border-gray-300 px-3 py-2 w-full" id="cidade" name="id_cidade" required>
        <option value="">Selecione cidade</option>
        <?php foreach($cidades as $ci): ?>
        <option value="<?= $ci['id'] ?>" <?= $ci['id']==$id_cidade?'selected':'' ?>><?= htmlspecialchars($ci['nome']) ?></option>
        <?php endforeach; ?>
    </select>
    <label>Contato</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" name="contato" value="<?= htmlspecialchars($contato) ?>">
    <label>Status</label><select class="border-b-2 border-gray-300 px-3 py-2 w-full" name="status">
        <option value="ATIVO" <?= $status=='ATIVO'?'selected':'' ?>>Ativo</option>
        <option value="INATIVA" <?= $status=='INATIVA'?'selected':'' ?>>Inativo</option>
    </select>
    <button class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" type="submit">Salvar</button>
</form>
<script>
$(document).ready(function(){
    $('#cidade').select2({width:'100%'});
    $('#cpf').mask('000.000.000-00');
    $('#cep').mask('00.000-000');
});
</script>
<?php include 'footer.php'; ?>
