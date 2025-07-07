<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'permissions.php';

$id = $_GET['id'] ?? null;
if(!$id){
    header('Location: produtos_list.php');
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM PRODUTO WHERE ID=?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$produto){
    header('Location: produtos_list.php');
    exit;
}

$empresaIds = userCompanies($pdo);
if(!hasRole('ADMINISTRADOR','DIRETORIA') && $empresaIds){
    $in = implode(',', array_fill(0,count($empresaIds),'?'));
    $st = $pdo->prepare("SELECT ID,NOME FROM EMPRESA WHERE ID IN ($in) ORDER BY NOME");
    $st->execute($empresaIds);
    $empresas = $st->fetchAll(PDO::FETCH_ASSOC);
} else {
    $empresas = $pdo->query("SELECT ID,NOME FROM EMPRESA ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $dest = $_POST['id_empresa'] ?? null;
    $estoque = $_POST['estoque_atual'] ?? $produto['ESTOQUE_ATUAL'];
    $codigo = trim($produto['CODIGO']);
    if($dest){
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM PRODUTO WHERE CODIGO=? AND ID_EMPRESA=?");
        $stmt->execute([$codigo, $dest]);
        $debugSql  = "SELECT COUNT(*) FROM PRODUTO WHERE CODIGO='" . addslashes($codigo) . "' AND ID_EMPRESA=" . intval($dest);
        $crossSql  = "SELECT COUNT(*) FROM PRODUTO WHERE CODIGO='" . addslashes($codigo) . "'";
        if($stmt->fetchColumn()){
            $erro = 'Produto já cadastrado nessa empresa. SQL: <code>' . htmlspecialchars($debugSql, ENT_NOQUOTES) . '</code>';
        } else {
            try{
                $sql = "INSERT INTO PRODUTO (NOME,ID_TIPO,ID_CATEGORIA,ID_MARCA,CODIGO,UNIDADE_MEDIDA,VALOR_COMPRA,VALOR_UNITARIO,ESTOQUE_ATUAL,STATUS,IMAGEM,ID_EMPRESA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                $pdo->prepare($sql)->execute([
                    $produto['NOME'],
                    $produto['ID_TIPO'],
                    $produto['ID_CATEGORIA'],
                    $produto['ID_MARCA'],
                    $codigo,
                    $produto['UNIDADE_MEDIDA'],
                    $produto['VALOR_COMPRA'],
                    $produto['VALOR_UNITARIO'],
                    $estoque,
                    $produto['STATUS'],
                    $produto['IMAGEM'],
                    $dest
                ]);
                header('Location: produtos_list.php?msg=copiado');
                exit;
            }catch(PDOException $ex){
                if($ex->getCode()==='23000'){
                    // double-check if codigo existe em alguma empresa
                    $chk = $pdo->prepare('SELECT COUNT(*) FROM PRODUTO WHERE CODIGO=?');
                    $chk->execute([$codigo]);
                    if($chk->fetchColumn()){
                        $erro = 'Já existe produto com este código cadastrado em outra empresa. SQL: <code>' . htmlspecialchars($crossSql, ENT_NOQUOTES) . '</code>';
                    } else {
                        $erro = 'Já existe produto com este código para a empresa selecionada. SQL: <code>' . htmlspecialchars($debugSql, ENT_NOQUOTES) . '</code>';
                    }
                }else{
                    $erro = htmlspecialchars($ex->getMessage()) . ' SQL: <code>' . htmlspecialchars($debugSql, ENT_NOQUOTES) . '</code>';
                }
            }
        }
    }
}

$pageTitle = 'Copiar Produto';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Copiar Produto: <?= htmlspecialchars($produto['NOME']) ?></h2>
  <?php if(!empty($erro)): ?>
    <div class="bg-red-100 text-red-700 p-2 mb-2 rounded">
      <?= htmlspecialchars($erro) ?>
    </div>
  <?php endif; ?>
  <form method="post" class="space-y-4">
    <div>
      <label class="block mb-1">Empresa Destino</label>
      <select name="id_empresa" class="border p-2 rounded w-full" required>
        <option value="">Selecione</option>
        <?php foreach($empresas as $e): if($e['ID']!=$produto['ID_EMPRESA']): ?>
        <option value="<?= $e['ID'] ?>"><?= htmlspecialchars($e['NOME']) ?></option>
        <?php endif; endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block mb-1">Estoque Atual</label>
      <input type="number" name="estoque_atual" value="<?= htmlspecialchars($produto['ESTOQUE_ATUAL']) ?>" class="border p-2 rounded w-full" min="0">
    </div>
    <button class="bg-primary text-white px-4 py-2 rounded" type="submit">Copiar</button>
  </form>
</div>
<?php include 'footer.php'; ?>
