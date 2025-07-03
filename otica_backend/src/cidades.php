<?php
require_once __DIR__ . '/db.php';

function handle_cidades($method, $segments) {
    $pdo = Database::getConnection();

    switch ($method) {
        case 'GET':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $stmt = $pdo->prepare("SELECT * FROM CIDADE WHERE ID = ?");
                $stmt->execute([$segments[1]]);
                echo json_encode($stmt->fetch());
            } else {
                $stmt = $pdo->query("SELECT * FROM CIDADE ORDER BY NOME");
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO CIDADE (NOME, UF) VALUES (?,?)");
            $stmt->execute([$data['nome'], $data['uf']]);
            echo json_encode([
              'ID' => $pdo->lastInsertId(),
              'NOME' => $data['nome'],
              'UF' => $data['uf']
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
}