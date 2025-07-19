<?php
require_once 'config.php';
require_once 'auth.php';
$id = $_GET['id']??null;
$nome=$cnpj=$contato=$cep=$rua=$bairro=$status=''; $id_cidade='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nome=$_POST['nome']; $cnpj=preg_replace('/\D/','',$_POST['cnpj']);
    $contato=$_POST['contato']; $cep=preg_replace('/\D/','',$_POST['cep']);
    $rua=$_POST['rua']; $bairro=$_POST['bairro']; $id_cidade=$_POST['id_cidade'];
    $status=$_POST['status'];
    if($id){
        $stmt=$pdo->prepare("UPDATE FORNECEDOR SET nome=?,cnpj=?,contato=?,cep=?,rua=?,bairro=?,id_cidade=?,status=? WHERE id=?");
        $stmt->execute([$nome,$cnpj,$contato,$cep,$rua,$bairro,$id_cidade,$status,$id]);
    }else{
        $stmt=$pdo->prepare("INSERT INTO FORNECEDOR (nome,cnpj,contato,cep,rua,bairro,id_cidade,status) VALUES(?,?,?,?,?,?,?,?)");
        $stmt->execute([$nome,$cnpj,$contato,$cep,$rua,$bairro,$id_cidade,$status]);
        $id=$pdo->lastInsertId();
    }
    header('Location: fornecedor_list.php');
    exit();
}
if($id){
    $stmt=$pdo->prepare("SELECT nome,cnpj,contato,cep,rua,bairro,id_cidade,status FROM FORNECEDOR WHERE id=?");
    $stmt->execute([$id]); $row=$stmt->fetch(PDO::FETCH_ASSOC);
    if($row){
        $nome=$row['nome'];$cnpj=$row['cnpj'];$contato=$row['contato'];
        $cep=$row['cep'];$rua=$row['rua'];$bairro=$row['bairro'];
        $id_cidade=$row['id_cidade'];$status=$row['status'];
    }
}
$cidades=$pdo->query("SELECT id,CONCAT(nome,'/',uf) as nome FROM CIDADE ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Fornecedor Form';
include 'header.php';
?>
<div class="container mx-auto">
<h2 class="text-2xl font-semibold mb-4"><?= $id?'Editar':'Novo' ?> Fornecedor</h2>
<form id="forneForm" method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="form-group">
        <label class="block mb-1">Nome</label>
        <input class="form-control" type="text" name="nome" value="<?=htmlspecialchars($nome)?>" required>
    </div>
    <div class="form-group">
        <label class="block mb-1">CNPJ</label>
        <input class="form-control" type="text" id="cnpj" name="cnpj" value="<?=$cnpj?>" required data-mask="cnpj">
    </div>
    <div class="form-group">
        <label class="block mb-1">Contato</label>
        <input class="form-control" type="text" name="contato" value="<?=htmlspecialchars($contato)?>" data-mask="telefone">
    </div>
    <div class="form-group">
        <label class="block mb-1">CEP</label>
        <input class="form-control" type="text" id="cep" name="cep" value="<?=$cep?>" required>
    </div>
    <div class="form-group">
        <label class="block mb-1">Rua</label>
        <input class="form-control" type="text" name="rua" value="<?=htmlspecialchars($rua)?>">
    </div>
    <div class="form-group">
        <label class="block mb-1">Bairro</label>
        <input class="form-control" type="text" name="bairro" value="<?=htmlspecialchars($bairro)?>">
    </div>
    <div class="form-group">
        <label class="block mb-1">Cidade</label>
        <select class="form-control" id="cidade" name="id_cidade" required>
        <option value="">Selecione cidade</option><?php foreach($cidades as $ci):?>
        <option value="<?=$ci['id']?>" <?= $ci['id']==$id_cidade?'selected':''?>><?=htmlspecialchars($ci['nome'])?></option><?php endforeach;?>
        </select>
    </div>
    <div class="form-group">
        <label class="block mb-1">Status</label>
        <select class="form-control" name="status">
            <option value="ATIVA" <?= $status=='ATIVA'?'selected':''?>>Ativa</option>
            <option value="INATIVA" <?= $status=='INATIVA'?'selected':''?>>Inativa</option>
        </select>
    </div>
    <div class="md:col-span-2">
        <button class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" type="submit">Salvar</button>
    </div>
</form>
</div>
<script>
$(document).ready(function(){
    $('#cidade').select2({width:'100%'}); $('#cnpj').mask('00.000.000/0000-00'); $('#cep').mask('00.000-000');
});
</script>
<?php include 'footer.php'; ?>
