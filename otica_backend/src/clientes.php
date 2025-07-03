<?php
require_once __DIR__ . '/db.php';

function handle_clientes($method, $segments) {
    $pdo = Database::getConnection();

    switch ($method) {
        case 'GET':
            if (isset($segments[1]) && is_numeric($segments[1])) {
                $stmt = $pdo->prepare(
                  "SELECT c.*, cid.NOME AS CIDADE, cid.UF
                   FROM CLIENTE c
                   LEFT JOIN CIDADE cid ON c.ID_CIDADE = cid.ID
                   WHERE c.ID = ?"
                );
                $stmt->execute([$segments[1]]);
                echo json_encode($stmt->fetch());
            } else {
                $stmt = $pdo->query(
                  "SELECT c.*, cid.NOME AS CIDADE, cid.UF
                   FROM CLIENTE c
                   LEFT JOIN CIDADE cid ON c.ID_CIDADE = cid.ID
                   ORDER BY c.NOME"
                );
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare(
              "INSERT INTO CLIENTE
               (NOME, CPF, DATA_NASCIMENTO, CEP, RUA, BAIRRO, ID_CIDADE, CONTATO, STATUS)
               VALUES (?,?,?,?,?,?,?,?,?)"
            );
            $stmt->execute([
              $data['nome'], $data['cpf'], $data['data_nascimento'] ?? null,
              $data['cep'], $data['rua'], $data['bairro'],
              $data['id_cidade'] ?: null,
              $data['contato'], $data['status']
            ]);
            echo json_encode(['ID' => $pdo->lastInsertId()]);
            break;

        case 'PUT':
            $id   = $segments[1];
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare(
              "UPDATE CLIENTE SET
                 NOME=?, CPF=?, DATA_NASCIMENTO=?, CEP=?, RUA=?, BAIRRO=?,
                 ID_CIDADE=?, CONTATO=?, STATUS=?
               WHERE ID=?"
            );
            $stmt->execute([
              $data['nome'], $data['cpf'], $data['data_nascimento'] ?? null,
              $data['cep'], $data['rua'], $data['bairro'],
              $data['id_cidade'] ?: null,
              $data['contato'], $data['status'],
              $id
            ]);
            echo json_encode(['updated' => $id]);
            break;

        case 'DELETE':
            $stmt = $pdo->prepare("DELETE FROM CLIENTE WHERE ID = ?");
            $stmt->execute([$segments[1]]);
            echo json_encode(['deleted' => $segments[1]]);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
}