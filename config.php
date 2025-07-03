<?php
session_start();
// Configurações de conexão com o banco Hostinger
define('DB_HOST', '212.85.3.45');
define('DB_PORT', '3306');
define('DB_USER', 'u820180255_admin');
define('DB_PASS', 'd@VuM4&e8W');
define('DB_NAME', 'u820180255_controleoticas');

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
?>