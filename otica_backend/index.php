<?php
require_once __DIR__ . '/src/db.php';

// Headers CORS e JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];

// Caminho da requisição e rota base do projeto
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];          // ex: /otica_backend/index.php
$basePath   = rtrim(dirname($scriptName), '/'); // ex: /otica_backend

if (strpos($requestUri, $scriptName) === 0) {
    $relative = substr($requestUri, strlen($scriptName));
} elseif (strpos($requestUri, $basePath) === 0) {
    $relative = substr($requestUri, strlen($basePath));
} else {
    $relative = $requestUri;
}

$path     = trim($relative, '/');
$segments = explode('/', $path);

switch ($segments[0] ?? '') {
    case 'produtos':
        require_once __DIR__ . '/src/produtos.php';
        handle_produtos($method, $segments);
        break;

    case 'clientes':
        require_once __DIR__ . '/src/clientes.php';
        handle_clientes($method, $segments);
        break;

    case 'tipo_produto':
        require_once __DIR__ . '/src/tipo_produto.php';
        handle_tipo_produto($method, $segments);
        break;

    case 'categoria_produto':
        require_once __DIR__ . '/src/categoria_produto.php';
        handle_categoria_produto($method, $segments);
        break;

    case 'cidades':
        require_once __DIR__ . '/src/cidades.php';
        handle_cidades($method, $segments);
        break;

    case 'upload':
        require_once __DIR__ . '/src/upload.php';
        break;

    default:
        echo json_encode(['message' => 'API running']);
        break;
}

exit;
