<?php
function hasRole(string ...$roles): bool {
    $current = $_SESSION['permissoes'] ?? '';
    foreach ($roles as $r) {
        if ($current === $r) return true;
    }
    return false;
}

function requireRole(string ...$roles): void {
    if (!hasRole(...$roles)) {
        header('Location: dashboard.php');
        exit();
    }
}

function userCompanies(PDO $pdo): array {
    if (!isset($_SESSION['user'])) return [];
    $stmt = $pdo->prepare('SELECT id_empresa FROM USUARIO_EMPRESA WHERE id_usuario=?');
    $stmt->execute([$_SESSION['user']]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
