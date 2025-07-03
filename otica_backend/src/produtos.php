<?php
// src/produtos.php
require_once __DIR__ . '/db.php';

function handle_produtos($method, $segments) {
    $db = Database::getConnection();
    header('Content-Type: application/json');
    $id = $segments[1] ?? null;

    switch ($method) {
        case 'GET':
            if ($id) {
                // Busca um produto
                $stmt = $db->prepare('SELECT * FROM PRODUTO WHERE ID = ?');
                $stmt->execute([$id]);
                echo json_encode($stmt->fetch());
            } else {
                // Lista todos os produtos
                $stmt = $db->query('SELECT * FROM PRODUTO');
                echo json_encode($stmt->fetchAll());
            }
            break;

        case 'POST':
            // Cria um novo produto
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO PRODUTO
                (NOME, ID_TIPO, ID_CATEGORIA, MARCA, CODIGO, UNIDADE_MEDIDA,
                 VALOR_UNITARIO, ESTOQUE_ATUAL, STATUS, IMAGEM)
             VALUES
                (:nome, :id_tipo, :id_categoria, :marca, :codigo, :unidade_medida,
                 :valor_unitario, :estoque_atual, :status, :imagem)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'nome'           => $data['nome'],
                'id_tipo'        => $data['id_tipo']        ?? null,
                'id_categoria'   => $data['id_categoria']   ?? null,
                'marca'          => $data['marca']          ?? null,
                'codigo'         => $data['codigo']         ?? null,
                'unidade_medida' => $data['unidade_medida'] ?? null,
                'valor_unitario' => $data['valor_unitario'] ?? 0,
                'estoque_atual'  => $data['estoque_atual']  ?? 0,
                'status'         => $data['status']         ?? 'ATIVO',
                'imagem'         => $data['imagem']         ?? null
            ]);
            echo json_encode(['ID' => $db->lastInsertId()]);
            break;

        case 'PUT':
            // Atualiza um produto existente
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing ID']);
                break;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "UPDATE PRODUTO SET
                NOME           = :nome,
                ID_TIPO        = :id_tipo,
                ID_CATEGORIA   = :id_categoria,
                MARCA          = :marca,
                CODIGO         = :codigo,
                UNIDADE_MEDIDA = :unidade_medida,
                VALOR_UNITARIO = :valor_unitario,
                ESTOQUE_ATUAL  = :estoque_atual,
                STATUS         = :status,
                IMAGEM         = :imagem
             WHERE ID = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'nome'           => $data['nome'],
                'id_tipo'        => $data['id_tipo']        ?? null,
                'id_categoria'   => $data['id_categoria']   ?? null,
                'marca'          => $data['marca']          ?? null,
                'codigo'         => $data['codigo']         ?? null,
                'unidade_medida' => $data['unidade_medida'] ?? null,
                'valor_unitario' => $data['valor_unitario'] ?? 0,
                'estoque_atual'  => $data['estoque_atual']  ?? 0,
                'status'         => $data['status']         ?? 'ATIVO',
                'imagem'         => $data['imagem']         ?? null,
                'id'             => $id
            ]);
            echo json_encode(['updated' => $id]);
            break;

        case 'DELETE':
            // Deleta um produto
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing ID']);
                break;
            }
            $stmt = $db->prepare('DELETE FROM PRODUTO WHERE ID = ?');
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
