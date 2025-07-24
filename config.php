<?php
// Inicia sessão apenas se ainda não houver uma ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constantes de conexão apenas uma vez
if (!defined('DB_HOST')) {
    define('DB_HOST', '212.85.3.45');
    define('DB_PORT', '3306');
    define('DB_USER', 'u820180255_admin');
    define('DB_PASS', 'd@VuM4&e8W');
    define('DB_NAME', 'u820180255_controleoticas');
}

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper to verify column existence, avoiding errors on older schemas
function columnExists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `".$table."` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    } catch (PDOException $e) {
        return false;
    }
}

// Helper to verify table existence
function tableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    } catch (PDOException $e) {
        return false;
    }
}

// Helper to detect the foreign key column name for consertos in CONSERTO_OCULOS_ITENS
function consertoItemColumn(PDO $pdo): string {
    static $col;
    if ($col !== null) return $col;
    $candidates = ['ID_CONCERTO', 'ID_CONSERTO'];
    foreach ($candidates as $c) {
        if (columnExists($pdo, 'CONSERTO_OCULOS_ITENS', $c)) {
            $col = $c;
            return $col;
        }
    }
    // default to original name
    $col = 'ID_CONCERTO';
    return $col;
}
?>
