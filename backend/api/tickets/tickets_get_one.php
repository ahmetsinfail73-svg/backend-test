<?php

require __DIR__ . '/../../config/database.php';

$ticketId = (int) $id;

if ($ticketId <= 0) {
    respond(400, 'Некорректный ID тикета');
}

$stmt = db()->prepare('SELECT * FROM tickets WHERE id = ?');
$stmt->bind_param('i', $ticketId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    respond(404, 'Тикет не найден');
}

$ticket = $result->fetch_assoc();

$attachStmt = db()->prepare(
    'SELECT id, filepath, size, uploaded_at FROM ticket_attachments 
     WHERE ticket_id = ? 
     ORDER BY uploaded_at DESC'
);
$attachStmt->bind_param('i', $ticketId);
$attachStmt->execute();
$attachResult = $attachStmt->get_result();

$attachments = [];
while ($attach = $attachResult->fetch_assoc()) {
    $attachments[] = $attach;
}

$ticket['attachments'] = $attachments;

respond(200, $ticket);
