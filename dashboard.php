<?php
$pageTitle = 'Dashboard';
include 'header.php';

// Definir permissões e período
$permissoes = $_SESSION['permissoes'];
$isAdmin   = strpos($permissoes, 'ADMINISTRADOR') !== false;
$isDiretor = strpos($permissoes, 'DIRETORIA') !== false;
$isVend    = strpos($permissoes, 'VENDEDOR') !== false;
$showDashboard = $isAdmin || $isDiretor || $isVend;
$showFinance   = $isAdmin || $isDiretor;

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-d');
$fUser = $isVend ? $_SESSION['user'] : ($_GET['user'] ?? '');
$fEmp  = $_GET['empresa'] ?? '';

$usuarios = $pdo->query("SELECT ID, NOME FROM USUARIO ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);
$empresas = $pdo->query("SELECT ID, NOME FROM EMPRESA ORDER BY NOME")->fetchAll(PDO::FETCH_ASSOC);

if ($showDashboard) {
    if ($showFinance) {
        $stmt = $pdo->prepare("SELECT SUM(VALOR_PARCELA) FROM CONTAS_A_PAGAR WHERE DATA_VENCIMENTO BETWEEN ? AND ?");
        $stmt->execute([$start, $end]);
        $totalPagar = $stmt->fetchColumn() ?: 0;
        $stmt = $pdo->prepare("SELECT SUM(VALOR) FROM CONTAS_A_RECEBER WHERE DATA_VENCIMENTO BETWEEN ? AND ?");
        $stmt->execute([$start, $end]);
        $totalReceber = $stmt->fetchColumn() ?: 0;
    }
    // filtros dinâmicos para vendas
    $where = "WHERE STATUS='CONCLUÍDA' AND DATA_VENDA BETWEEN ? AND ?";
    $params = [$start, $end];
    if ($fEmp) { $where .= " AND ID_EMPRESA=?"; $params[] = $fEmp; }
    if ($isVend) {
        $where .= " AND ID_USUARIO=?";
        $params[] = $_SESSION['user'];
    } elseif ($fUser) {
        $where .= " AND ID_USUARIO=?";
        $params[] = $fUser;
    }
    // Quantidade de vendas concluídas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM VENDAS $where");
    $stmt->execute($params);
    $cntVendas = $stmt->fetchColumn() ?: 0;
    // Valor das vendas (usa VALOR_LIQUIDO se existir, senao VALOR_TOTAL)
    $col = columnExists($pdo,'VENDAS','VALOR_LIQUIDO') ? 'VALOR_LIQUIDO' : 'VALOR_TOTAL';
    $stmt = $pdo->prepare("SELECT SUM($col) FROM VENDAS $where");
    $stmt->execute($params);
    $totalVendas = $stmt->fetchColumn() ?: 0;
    // Desconto concedido nas vendas concluídas
    $stmt = $pdo->prepare("SELECT SUM(DESCONTO) FROM VENDAS $where");
    $stmt->execute($params);
    $descVendas = $stmt->fetchColumn() ?: 0;
    $stmt = $pdo->prepare("SELECT SUM(iv.DESCONTO) FROM ITENS_VENDA iv JOIN VENDAS v ON v.ID = iv.ID_VENDA $where");
    $stmt->execute($params);
    $descItens = $stmt->fetchColumn() ?: 0;
    $totalDesconto = $descVendas + $descItens;
    // Top 10 produtos com menor estoque (sem considerar período)
    $sql = "SELECT p.NOME, IFNULL(m.NOME,'') AS MARCA, p.CODIGO, e.NOME AS EMPRESA, p.ESTOQUE_ATUAL
            FROM PRODUTO p
            LEFT JOIN MARCA_PRODUTO m ON p.ID_MARCA = m.ID
            LEFT JOIN EMPRESA e       ON p.ID_EMPRESA = e.ID
            ORDER BY p.ESTOQUE_ATUAL ASC
            LIMIT 10";
    $stmt = $pdo->query($sql);
    $topProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="container mx-auto">
    <form method="get" class="period-form mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-sm">Início</label>
            <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="border rounded px-3 py-2 focus:ring focus:ring-primary/50">
        </div>
        <div>
            <label class="block text-sm">Fim</label>
            <input type="date" name="end" value="<?= htmlspecialchars($end) ?>" class="border rounded px-3 py-2 focus:ring focus:ring-primary/50">
        </div>
        <?php if(!$isVend): ?>
        <div>
            <label class="block text-sm">Usuário</label>
            <select name="user" class="border rounded px-3 py-2 focus:ring focus:ring-primary/50">
                <option value="">Todos</option>
                <?php foreach($usuarios as $u): ?>
                    <option value="<?= $u['ID'] ?>" <?= $fUser==$u['ID']?'selected':'' ?>><?= htmlspecialchars($u['NOME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="user" value="<?= $_SESSION['user'] ?>">
        <?php endif; ?>
        <div>
            <label class="block text-sm">Empresa</label>
            <select name="empresa" class="border rounded px-3 py-2 focus:ring focus:ring-primary/50">
                <option value="">Todas</option>
                <?php foreach($empresas as $e): ?>
                    <option value="<?= $e['ID'] ?>" <?= $fEmp==$e['ID']?'selected':'' ?>><?= htmlspecialchars($e['NOME']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="self-end">
            <button class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Filtrar</button>
        </div>
    </form>
    <div class="text-right mb-4">
        <a href="step1.php" class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Nova Venda</a>
    </div>
    <?php if ($showDashboard): ?>
    <div class="grid grid-cols-1 md:grid-cols-<?= $showFinance ? '4' : '3' ?> gap-4 mb-6">
        <?php if($showFinance): ?>
        <div class="bg-white rounded shadow p-4">
            <h3 class="font-semibold mb-2">Contas a Pagar x Receber</h3>
            <canvas id="chartContas"></canvas>
        </div>
        <?php endif; ?>
        <div class="bg-white rounded shadow p-4">
            <h3 class="font-semibold mb-2">Vendas Efetuadas</h3>
            <p class="text-xl"><?= $cntVendas ?></p>
        </div>
        <div class="bg-white rounded shadow p-4">
            <h3 class="font-semibold mb-2">Valor de Vendas</h3>
            <p class="text-xl">R$ <?= number_format($totalVendas,2,',','.') ?></p>
        </div>
        <div class="bg-white rounded shadow p-4">
            <h3 class="font-semibold mb-2">Desconto Concedido</h3>
            <p class="text-xl"><?= number_format($totalDesconto,2,',','.') ?></p>
        </div>
    </div>
    <h3 class="font-semibold mb-2">Top 10 Produtos com Menor Estoque</h3>
    <table class="min-w-full bg-white rounded shadow mb-6">
        <thead class="bg-secondary text-white">
            <tr><th class="p-2">Produto</th><th class="p-2">Estoque Atual</th></tr>
        </thead>
        <tbody>
            <?php foreach($topProdutos as $prod): ?>
            <tr>
                <td class="border-t p-2">
                    <?= htmlspecialchars($prod['NOME']) ?>
                    <?php if($prod['MARCA']): ?> (<?= htmlspecialchars($prod['MARCA']) ?>)<?php endif; ?>
                    - <?= htmlspecialchars($prod['CODIGO']) ?> | <?= htmlspecialchars($prod['EMPRESA']) ?>
                </td>
                <td class="border-t p-2 text-center"><?= $prod['ESTOQUE_ATUAL'] ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if($showFinance): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('chartContas').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: { labels: ['Pagar','Receber'], datasets: [{ label:'Valores', data:[<?= $totalPagar ?>,<?= $totalReceber ?>] }] },
            options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
        });
    });
    </script>
    <?php endif; ?>
    <?php else: ?>
    <h1 class="text-2xl">Olá, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
