<?php
// Тип ответа — JSON
header('Content-Type: application/json');

// Получаем путь запроса
$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];
$segments = explode('/', $uri);

if ($segments[0] !== 'api') {
    http_response_code(404, "Invalid endpoint");
}

$resource = $segments[1] ?? null;
$id = $segments[2] ?? null;

// Простейший роутер
switch ($resource) {
    case 'tickets':
        require __DIR__ . '/api/tickets.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
}

function respond($code, $data)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}