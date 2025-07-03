<?php
// src/upload.php
header('Content-Type: application/json');

if (!isset($_FILES['imagem'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Nenhum arquivo enviado']);
    exit;
}

$targetDir = __DIR__ . '/../public/uploads/';
if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

$file = $_FILES['imagem'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newName = uniqid('prod_') . '.' . $ext;
$dest = $targetDir . $newName;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    // Retorna caminho relativo
    echo json_encode(['url'=>"/otica_backend/public/uploads/$newName"]);
} else {
    http_response_code(500);
    echo json_encode(['error'=>'Falha ao mover arquivo']);
}
