<?php
include 'db.php';
$nome = $_POST['nome'] ?? '';
if ($nome) {
    $stmt = $pdo->prepare("INSERT INTO TIPO_PRODUTOS (NOME) VALUES (?)");
    $stmt->execute([$nome]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
