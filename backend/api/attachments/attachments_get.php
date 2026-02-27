<?php

require __DIR__ . '/../config/database.php';

$ticketId = (int)$id;


$stmt = db()->prepare('SELECT 1 FROM tickets WHERE id = ?');
$stmt->bind_param('i', $ticketId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    respond(404, 'Ticket not found');
}


$stmt = db()->prepare(
    'SELECT id, filepath, size, uploaded_at FROM ticket_attachments 
     WHERE ticket_id = ? 
     ORDER BY uploaded_at DESC'
);
$stmt->bind_param('i', $ticketId);
$stmt->execute();
$result = $stmt->get_result();

$attachments = [];
while ($row = $result->fetch_assoc()) {
    $attachments[] = $row;
}

respond(200, [
    'ticket_id' => $ticketId,
    'attachments' => $attachments,
    'count' => count($attachments)
]);
