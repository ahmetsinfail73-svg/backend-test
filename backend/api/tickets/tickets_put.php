<?php


require __DIR__ . '/../../config/database.php';



$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
if (!$id) {
    respond(400, 'ID обязателен');
}
$dto = new UpdateTicketDto($data);
$errors = $dto->validate();

if (!empty($errors)) {
    respond(400, $errors);
}

list($fields, $params) = $dto->toSqlSet();
$params[] = $id;

$sql = "UPDATE tickets SET " . implode(", ", $fields) . " WHERE id = ?";

$result = db()->execute_query($sql, $params);

if ($result === false) {
    respond(404, 'Тикет не найден');
}

respond(200, 'Тикет обновлён');

