<?php
// Тип ответа — JSON
header('Content-Type: application/json');

// Получаем путь запроса
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Простейший роутер
switch ($uri) {
    case '/api/tickets':
        require __DIR__ . '/api/tickets.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
}
