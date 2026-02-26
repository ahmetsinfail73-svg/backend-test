<?php

require __DIR__ . '/../config/database.php';


switch ($method) {
    case 'GET':
        $limit = 20;
        $priority = $_GET['priority'] ?? null;
        $search = trim($_GET['search'] ?? "");
        $created_at = $_GET['created_at'] ?? null;
        $updated_at = $_GET['updated_at'] ?? null;
        $page = $_GET['page'] ?? 1;

        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM tickets";
        $where = [];
        $params = [];
        $types = '';
        $filters = [
        'status' => $_GET['status'] ?? null,
        'priority' => $_GET['priority'] ?? null,
        'title' => $_GET['search'] ?? null,
        "id" => $_GET['id'] ?? null
        ];

        $where = [];
        $params = [];
        $types = '';

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if ($field === 'title') {
                    $where[] = "(title LIKE ? OR description LIKE ?)";
                    $params[] = "%$value%";
                    $params[] = "%$value%";
                    $types .= 'ss';
                } else {
                    $where[] = "$field = ?";
                    $params[] = $value;
                    $types .= 's';
                }
            }
        }
        
        $sql_total = "SELECT COUNT(*) AS total FROM tickets";
        if (!empty($where)) {
            $sql_total .= " WHERE " . implode(" AND ", $where);
        }

        $stmt_total = db()->prepare($sql_total);
        if (!empty($params)) {
            $stmt_total->bind_param($types, ...$params);
        }
        $stmt_total->execute();
        $result_total = $stmt_total->get_result();
        $total = (int)$result_total->fetch_assoc()['total'];
        $total_pages = ceil($total / $limit);

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sort_order = $_GET['sort'] ?? 'newest'; 
        $sort_order = $sort_order === 'oldest' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY created_at $sort_order LIMIT $limit OFFSET $offset";

        $stmt = db()->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            respond(200, "Not Found");
        }

        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }

        respond(200, [
        'tickets' => $tickets,
        'meta' => [
        'total' => $total,
        'page' => $page,
        'pages' => $total_pages,
        'limit' => $limit
        ]
        ]);
        break;
    case 'POST':
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
            respond(201, ['id' => $stmt->insert_id]);
        } else {
            respond(500, "Database error: " . $stmt->error);
        }
        break;
    case "PUT":
         $data = json_decode(file_get_contents('php://input'), true);

         $id = $data['id'] ?? null;
         if (!$id) {
             respond(400, "ID is required");
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
            respond(404, "Not Found");
        }
        
        respond(200, "Updated");
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);

        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            respond(400, 'ID is required');
        }

        $sql = "DELETE FROM tickets WHERE id = ?";
        $result = db()->execute_query($sql, [$id]);

        if ($result === false || db()->affected_rows === 0) {
            respond(404, 'Ticket not found');
        }

        respond(200, 'Ticket deleted');
        break;

}