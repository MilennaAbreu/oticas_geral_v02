<?php
require_once 'config.php';
require_once 'auth.php';

$id             = $_POST['id'] ?? null;
$nome           = $_POST['nome'] ?? '';
$id_tipo        = $_POST['id_tipo'] ?: null;
$id_categoria   = $_POST['id_categoria'] ?: null;
$marca          = $_POST['marca'] ?? null;
$codigo         = $_POST['codigo'] ?? null;
$unidade        = $_POST['unidade_medida'] ?? 'UN';
$valor_unitario = str_replace(',', '.', $_POST['valor_unitario'] ?? '0');
$estoque        = $_POST['estoque_atual'] ?? 0;
$status         = $_POST['status'] ?? 'ATIVO';

$imagem = null;
if(!empty($_FILES['imagem']['name'])){
    $dir = 'uploads';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
    $imagem = uniqid('prod_').'.'.$ext;
    move_uploaded_file($_FILES['imagem']['tmp_name'], "$dir/$imagem");
}

if($id){
    if(!$imagem){
        $stmt = $pdo->prepare("SELECT IMAGEM FROM PRODUTO WHERE ID=?");
        $stmt->execute([$id]);
        $imagem = $stmt->fetchColumn();
    }
    $sql = "UPDATE PRODUTO SET NOME=?, ID_TIPO=?, ID_CATEGORIA=?, MARCA=?, CODIGO=?, UNIDADE_MEDIDA=?, VALOR_UNITARIO=?, ESTOQUE_ATUAL=?, STATUS=?, IMAGEM=? WHERE ID=?";
    $pdo->prepare($sql)->execute([$nome,$id_tipo,$id_categoria,$marca,$codigo,$unidade,$valor_unitario,$estoque,$status,$imagem,$id]);
} else {
    $sql = "INSERT INTO PRODUTO (NOME, ID_TIPO, ID_CATEGORIA, MARCA, CODIGO, UNIDADE_MEDIDA, VALOR_UNITARIO, ESTOQUE_ATUAL, STATUS, IMAGEM) VALUES (?,?,?,?,?,?,?,?,?,?)";
    $pdo->prepare($sql)->execute([$nome,$id_tipo,$id_categoria,$marca,$codigo,$unidade,$valor_unitario,$estoque,$status,$imagem]);
}

header('Location: produtos_list.php');
exit;
