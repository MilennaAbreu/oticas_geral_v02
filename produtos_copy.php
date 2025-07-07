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
    if($dest){
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM PRODUTO WHERE CODIGO=? AND ID_EMPRESA=?");
        $stmt->execute([$produto['CODIGO'],$dest]);
        if(!$stmt->fetchColumn()){
            $sql = "INSERT INTO PRODUTO (NOME,ID_TIPO,ID_CATEGORIA,ID_MARCA,CODIGO,UNIDADE_MEDIDA,VALOR_COMPRA,VALOR_UNITARIO,ESTOQUE_ATUAL,STATUS,IMAGEM,ID_EMPRESA) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
            $pdo->prepare($sql)->execute([
                $produto['NOME'],$produto['ID_TIPO'],$produto['ID_CATEGORIA'],$produto['ID_MARCA'],$produto['CODIGO'],$produto['UNIDADE_MEDIDA'],$produto['VALOR_COMPRA'],$produto['VALOR_UNITARIO'],$produto['ESTOQUE_ATUAL'],$produto['STATUS'],$produto['IMAGEM'],$dest
            ]);
        }
    }
    header('Location: produtos_list.php');
    exit;
}

$pageTitle = 'Copiar Produto';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Copiar Produto: <?= htmlspecialchars($produto['NOME']) ?></h2>
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
    <button class="bg-primary text-white px-4 py-2 rounded" type="submit">Copiar</button>
  </form>
</div>
<?php include 'footer.php'; ?>
