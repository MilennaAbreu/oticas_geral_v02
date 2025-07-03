<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'permissions.php';

$allowed = userCompanies($pdo);
if(!$allowed){
    $allowed = [];
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $empresa = $_POST['id_empresa'] ?? null;
    if($empresa){
        $_SESSION['venda'] = $_SESSION['venda'] ?? [];
        $_SESSION['venda']['ID_EMPRESA'] = $empresa;
        header('Location: step2.php');
        exit;
    }
}

if(count($allowed) === 1){
    $_SESSION['venda'] = $_SESSION['venda'] ?? [];
    $_SESSION['venda']['ID_EMPRESA'] = $allowed[0];
    header('Location: step2.php');
    exit;
}

if($allowed){
    $in = implode(',', array_fill(0,count($allowed),'?'));
    $stmt = $pdo->prepare("SELECT ID, NOME_FANTASIA FROM EMPRESA WHERE ID IN ($in) ORDER BY NOME_FANTASIA");
    $stmt->execute($allowed);
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $empresas = $pdo->query("SELECT ID, NOME_FANTASIA FROM EMPRESA ORDER BY NOME_FANTASIA")->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = 'Empresa da Venda';
include 'header.php';
?>
<div class="container mx-auto">
  <h2 class="text-2xl font-semibold mb-4">Selecione a Empresa</h2>
  <form method="post" class="space-y-4">
    <select name="id_empresa" class="border p-2 rounded w-full" required>
      <option value="">Selecione</option>
      <?php foreach($empresas as $e): ?>
        <option value="<?= $e['ID'] ?>"><?= htmlspecialchars($e['NOME_FANTASIA']) ?></option>
      <?php endforeach; ?>
    </select>
    <div>
      <button class="bg-primary text-white px-4 py-2 rounded">Próximo</button>
      <a href="cancelar_venda.php" class="ml-3 text-red-600">Cancelar</a>
    </div>
  </form>
</div>
<?php include 'footer.php'; ?>

