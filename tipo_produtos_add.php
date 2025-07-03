<?php
include 'db.php';
$nome = $_POST['nome'] ?? '';
if ($nome) {
    $stmt = $pdo->prepare("INSERT INTO TIPO_PRODUTO (NOME) VALUES (?)");
    $stmt->execute([$nome]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'nome' => $nome]);
} else {
    echo json_encode(['success' => false]);
}
