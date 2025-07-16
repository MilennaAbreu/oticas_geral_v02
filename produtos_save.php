<?php
require_once 'config.php';
require_once 'auth.php';

$id             = $_POST['id'] ?? null;
$nome           = $_POST['nome'] ?? '';
$id_tipo        = $_POST['id_tipo'] ?: null;
$id_categoria   = $_POST['id_categoria'] ?: null;
$id_marca       = $_POST['id_marca'] ?: null;
$codigo         = isset($_POST['codigo']) ? trim($_POST['codigo']) : null;
$codigo         = $codigo === '' ? null : $codigo;
$unidade        = $_POST['unidade_medida'] ?? 'UN';
$valor_unitario = str_replace(',', '.', $_POST['valor_unitario'] ?? '0');
$valor_compra  = str_replace(',', '.', $_POST['valor_compra'] ?? '0');
$estoque        = $_POST['estoque_atual'] ?? 0;
$status         = $_POST['status'] ?? 'ATIVO';
$id_empresa     = $_POST['id_empresa'] ?: null;

// verifica duplicidade de codigo por empresa, desconsiderando codigos vazios
if ($id_empresa !== null && $codigo !== null) {
    $sql = "SELECT ID FROM PRODUTO WHERE CODIGO=? AND ID_EMPRESA=?" . ($id ? " AND ID<>?" : "");
    $params = [$codigo, $id_empresa];
    if ($id) $params[] = $id;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetch()) {
        header('Location: produtos_form.php?erro=dup' . ($id ? "&id=$id" : ''));
        exit;
    }
}

$imagem = null;
if(!empty($_FILES['imagem']['name'])){
    $dir = 'uploads';
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
    $imagem = uniqid('prod_').'.'.$ext;
    move_uploaded_file($_FILES['imagem']['tmp_name'], "$dir/$imagem");
}

try {
if($id){
    if(!$imagem){
        $stmt = $pdo->prepare("SELECT IMAGEM FROM PRODUTO WHERE ID=?");
        $stmt->execute([$id]);
        $imagem = $stmt->fetchColumn();
    }
    $sql = "UPDATE PRODUTO SET NOME=?, ID_TIPO=?, ID_CATEGORIA=?, ID_MARCA=?, CODIGO=?, UNIDADE_MEDIDA=?, VALOR_COMPRA=?, VALOR_UNITARIO=?, ESTOQUE_ATUAL=?, STATUS=?, IMAGEM=?, ID_EMPRESA=? WHERE ID=?";
    $pdo->prepare($sql)->execute([$nome,$id_tipo,$id_categoria,$id_marca,$codigo,$unidade,$valor_compra,$valor_unitario,$estoque,$status,$imagem,$id_empresa,$id]);
} else {
    $sql = "INSERT INTO PRODUTO (NOME, ID_TIPO, ID_CATEGORIA, ID_MARCA, CODIGO, UNIDADE_MEDIDA, VALOR_COMPRA, VALOR_UNITARIO, ESTOQUE_ATUAL, STATUS, IMAGEM, ID_EMPRESA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
    $pdo->prepare($sql)->execute([$nome,$id_tipo,$id_categoria,$id_marca,$codigo,$unidade,$valor_compra,$valor_unitario,$estoque,$status,$imagem,$id_empresa]);
}

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        header('Location: produtos_form.php?erro=dup' . ($id ? "&id=$id" : ''));
        exit;
    }
    throw $e;
}

header('Location: produtos_list.php');
exit;
