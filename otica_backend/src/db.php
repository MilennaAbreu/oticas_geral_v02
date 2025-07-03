<?php
// Carrega variáveis do .env manualmente
$envFile = __DIR__ . '/../config/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        putenv("{$key}={$value}");
    }
}

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: '212.85.3.45';
            $port = getenv('DB_PORT') ?: '3306';
            $db   = getenv('DB_NAME') ?: '';
            $user = getenv('DB_USER') ?: '';
            $pass = getenv('DB_PASS') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode([
                    'error'   => 'Database connection failed',
                    'details' => $e->getMessage(),
                ]);
                exit;
            }
        }
        return self::$instance;
    }
}
?>
