<?php

require __DIR__ . '/../config/database.php';


if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($uri, '/'));

if (!isset($parts[1], $parts[2], $parts[3]) || $parts[1] !== 'tickets' || $parts[3] !== 'attachments') {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
    exit;
}

$ticketId = (int)$parts[2];

$stmt = db()->prepare('SELECT 1 FROM tickets WHERE id = ?');
$stmt->bind_param('i', $ticketId);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    respond(404, 'Ticket not found');
}

if (!isset($_FILES['file'])) {
    respond(400, 'No file uploaded');
}
$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    respond(400, 'Upload error: ' . $file['error']);
}
if ($file['size'] > 5 * 1024 * 1024) {     
    respond(400, 'File too large');
}

$allowed = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png',
];
if (!in_array($file['type'], $allowed, true)) {
    respond(400, 'Invalid file type');
}

$ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$storedName = uniqid('', true) . (strlen($ext) ? "." . $ext : '');
$target     = $uploadDir . '/' . $storedName;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    respond(500, 'Failed to save file');
}


$size = $file['size'];
$stmt = db()->prepare(
    'INSERT INTO ticket_attachments (ticket_id, filepath, size, uploaded_at)
     VALUES (?, ?, ?, NOW())'
);
$stmt->bind_param('isi', $ticketId, $storedName, $size);
$stmt->execute();

respond(201, ['attachment_id' => $stmt->insert_id]);
