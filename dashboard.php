<?php
$pageTitle = 'Dashboard';
include 'header.php';

// Definir permissões e período
$permissoes = $_SESSION['permissoes'];
$isAdmin = strpos($permissoes, 'ADMINISTRADOR') !== false;
$isDiretor = strpos($permissoes, 'DIRETORIA') !== false;
$showDashboard = $isAdmin || $isDiretor;

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');

if ($showDashboard) {
    // Contas a Pagar
    $stmt = $pdo->prepare("SELECT SUM(VALOR_PARCELA) FROM CONTAS_A_PAGAR WHERE DATA_VENCIMENTO BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $totalPagar = $stmt->fetchColumn() ?: 0;
    // Contas a Receber
    $stmt = $pdo->prepare("SELECT SUM(VALOR) FROM CONTAS_A_RECEBER WHERE DATA_VENCIMENTO BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $totalReceber = $stmt->fetchColumn() ?: 0;
    // Quantidade de vendas concluídas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM VENDAS WHERE STATUS='CONCLUÍDA' AND DATA_VENDA BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $cntVendas = $stmt->fetchColumn() ?: 0;
    // Valor líquido das vendas concluídas
    $stmt = $pdo->prepare("SELECT SUM(VALOR_LIQUIDO) FROM VENDAS WHERE STATUS='CONCLUÍDA' AND DATA_VENDA BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $totalVendas = $stmt->fetchColumn() ?: 0;
    // Desconto concedido nas vendas concluídas
    $stmt = $pdo->prepare("SELECT SUM(DESCONTO) FROM VENDAS WHERE STATUS='CONCLUÍDA' AND DATA_VENDA BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $descVendas = $stmt->fetchColumn() ?: 0;
    $stmt = $pdo->prepare("SELECT SUM(iv.DESCONTO) FROM ITENS_VENDA iv JOIN VENDAS v ON v.ID = iv.ID_VENDA WHERE v.STATUS='CONCLUÍDA' AND v.DATA_VENDA BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $descItens = $stmt->fetchColumn() ?: 0;
    $totalDesconto = $descVendas + $descItens;
    // Top 10 produtos com menor estoque (sem considerar período)
    $stmt = $pdo->query("SELECT NOME AS PRODUTO, ESTOQUE_ATUAL FROM PRODUTO ORDER BY ESTOQUE_ATUAL ASC LIMIT 10");
    $topProdutos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<div class="container mx-auto">
    <form method="get" class="period-form mb-4 flex items-center space-x-2">
        <input type="date" name="start" value="<?= htmlspecialchars($start) ?>" class="border rounded px-3 py-2 focus:ring focus:ring-primary/50">
        <span>até</span>
        <input type="date" name="end" value="<?= htmlspecialchars($end) ?>" class="border rounded px-3 py-2 focus:ring focus:ring-primary/50">
        <button class="bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Filtrar</button>
    </form>
    <div class="text-right mb-4">
        <a href="step1.php" class="add-btn bg-primary text-white rounded px-4 py-2 hover:bg-opacity-80 transition">Nova Venda</a>
    </div>
    <?php if ($showDashboard): ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded shadow p-4">
            <h3 class="font-semibold mb-2">Contas a Pagar x Receber</h3>
            <canvas id="chartContas"></canvas>
        </div>
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
            <tr><td class="border-t p-2"><?= htmlspecialchars($prod['PRODUTO']) ?></td><td class="border-t p-2"><?= $prod['ESTOQUE_ATUAL'] ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
    <?php else: ?>
    <h1 class="text-2xl">Olá, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
