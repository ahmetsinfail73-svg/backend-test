<?php

require __DIR__ . '/../config/database.php';

switch ($method) {
    case 'GET':
        $limit = 20;


        $stmt = db()->prepare("SELECT id, title, priority, status FROM tickets LIMIT = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            respond(200, "Not Found");
        }

        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        respond(200, $tickets);
}