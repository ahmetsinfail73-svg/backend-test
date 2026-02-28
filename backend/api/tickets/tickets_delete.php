<?php

require __DIR__ . '/../../config/database.php';


$data = json_decode(file_get_contents('php://input'), true);

$id = (int) ($data['id'] ?? 0);
if ($id <= 0) {
    respond(400, 'ID обязателен');
}

$sql = "DELETE FROM tickets WHERE id = ?";
$result = db()->execute_query($sql, [$id]);

if ($result === false || db()->affected_rows === 0) {
    respond(404, 'Тикет не найден');
}

respond(200, 'Тикет удалён');

