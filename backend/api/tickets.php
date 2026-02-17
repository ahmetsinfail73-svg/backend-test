<?php

require __DIR__ . '/../config/database.php';

// Простой ответ JSON
echo json_encode([
    'status' => 'success',
    'message' => 'hello world'
]);
