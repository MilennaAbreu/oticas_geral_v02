<?php
$pageTitle='Fornecedor Form';
include 'header.php';
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
    header('Location: fornecedor_list.php'); exit();
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
?>
<h2><?= $id?'Editar':'Novo' ?> Fornecedor</h2>
<form id="forneForm" method="post">
    <label>Nome</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" name="nome" value="<?=htmlspecialchars($nome)?>" required>
    <label>CNPJ</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" id="cnpj" name="cnpj" value="<?=$cnpj?>" required>
    <label>Contato</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" name="contato" value="<?=htmlspecialchars($contato)?>">
    <label>CEP</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" id="cep" name="cep" value="<?=$cep?>" required>
    <label>Rua</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" name="rua" value="<?=htmlspecialchars($rua)?>">
    <label>Bairro</label><input class="border-b-2 border-gray-300 px-3 py-2 w-full" type="text" name="bairro" value="<?=htmlspecialchars($bairro)?>">
    <label>Cidade</label><select class="border-b-2 border-gray-300 px-3 py-2 w-full" id="cidade" name="id_cidade" required>
        <option value="">Selecione cidade</option><?php foreach($cidades as $ci):?>
        <option value="<?=$ci['id']?>" <?= $ci['id']==$id_cidade?'selected':''?>><?=htmlspecialchars($ci['nome'])?></option><?php endforeach;?>
    </select>
    <label>Status</label><select class="border-b-2 border-gray-300 px-3 py-2 w-full" name="status">
        <option value="ATIVA" <?= $status=='ATIVA'?'selected':''?>>Ativa</option>
        <option value="INATIVA" <?= $status=='INATIVA'?'selected':''?>>Inativa</option>
    </select>
    <button class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition" type="submit">Salvar</button>
</form>
<script>
$(document).ready(function(){
    $('#cidade').select2({width:'100%'}); $('#cnpj').mask('00.000.000/0000-00'); $('#cep').mask('00.000-000');
});
</script>
<?php include 'footer.php'; ?>