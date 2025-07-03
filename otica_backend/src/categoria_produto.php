<?php

// src/categoria_produto.php
require_once __DIR__ . '/db.php';

function handle_categoria_produto($method, $segments) {
    $db = Database::getConnection();
    header('Content-Type: application/json');
    $id = $segments[1] ?? null;

    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $db->prepare('SELECT * FROM CATEGORIA_PRODUTO WHERE ID = ?');
                $stmt->execute([$id]);
                echo json_encode($stmt->fetch());
            } else {
                $stmt = $db->query('SELECT * FROM CATEGORIA_PRODUTO');
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare('INSERT INTO CATEGORIA_PRODUTO (NOME) VALUES (?)');
            $stmt->execute([$data['nome']]);
            echo json_encode(['ID' => $db->lastInsertId(), 'NOME' => $data['nome']]);
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing ID']);
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $db->prepare('UPDATE CATEGORIA_PRODUTO SET NOME = ? WHERE ID = ?');
            $stmt->execute([$data['nome'], $id]);
            echo json_encode(['updated' => $id, 'NOME' => $data['nome']]);
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing ID']);
                break;
            }
            $stmt = $db->prepare('DELETE FROM CATEGORIA_PRODUTO WHERE ID = ?');
            $stmt->execute([$id]);
            echo json_encode(['deleted' => true]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            break;
    }
}
?>