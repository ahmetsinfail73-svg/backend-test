<?php

require __DIR__ . '/../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$errors = (new TicketDto($data))->validate();
if (!empty($errors)) {
    respond(400, $errors);
}

$title = trim($data['title'] ?? "");
$description = trim($data['description'] ?? "");
$priority = $data['priority'] ?? "medium";
$status = "open";

$stmt = db()->prepare("INSERT INTO tickets (title, description, priority, status) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $title, $description, $priority, $status);
if ($stmt->execute()) {
    respond(201, 'Тикет успешно создан');
} else {
    respond(500, 'Ошибка базы данных: ' . $stmt->error);
}
