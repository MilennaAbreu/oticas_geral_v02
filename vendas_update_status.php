<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = $_POST['status'] ?? '';

if ($id && $status) {
    try {
        $stmt = $pdo->prepare("UPDATE VENDAS SET STATUS=? WHERE ID=?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
}
exit;
?>
