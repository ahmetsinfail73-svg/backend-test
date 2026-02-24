<?php

require __DIR__ . '/../config/database.php';

switch ($method) {
    case 'GET':
        $limit = 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $status = $_GET['status']   ?? null;
        $priority = $_GET['priority'] ?? null;
        $search = trim($_GET['search'] ?? "");
        $created_at = $_GET['created_at'] ?? null;
        $updated_at = $_GET['updated_at'] ?? null;
        $id = $_GET['id'] ?? null;

        $offset = max(0, $offset);


        $sql = "SELECT id, title, priority, status FROM tickets";
        $where = [];
        $params = [];
        $types = '';
        if ($id) {
            $where[] = "id = ?";
            $params[] = $id;
            $types .= 'i';
        }
        if ($search != "") {
            $where[] = "title LIKE ?";
            $params[] = "%$search%";
            $types .= 's';
        }
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
            $types .= 's';
        }
        if ($priority) {
            $where[] = "priority = ?";
            $params[] = $priority;
            $types .= 's';
        }
        if ($created_at) {
            $where[] = "created_at >= ?";
            $params[] = $created_at;
            $types .= 's';
        }
        if ($updated_at) {
            $where[] = "updated_at >= ?";
            $params[] = $updated_at;
            $types .= 's';
        }


        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = db()->prepare($sql);
        $stmt->bind_param($types, ...$params);
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
        break;
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);

        $title = trim($data['title'] ?? "");
        $description = trim($data['description'] ?? "");
        $priority = $data['priority'] ?? "medium";
        $status = $data['status'] ?? "open";
        if ($title == "") {
            respond(400, "Title is required");
        }


        $allowed_priorities = ['low', 'medium', 'high'];
        if (!in_array($priority, $allowed_priorities)) {
            respond(400, "Invalid priority");
        }

        $allowed_statuses = ['open', 'in_progress', 'closed'];
        if (!in_array($status, $allowed_statuses)) {
            respond(400, "Invalid status");
        }

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
         $fields = [];
        $params = [];

        if (array_key_exists('title', $data)) {
            $fields[] = "title = ?";
            $params[] = trim($data['title']);
        }

        if (array_key_exists('description', $data)) {
            $fields[] = "description = ?";
            $params[] = trim($data['description']);
        }

        if (array_key_exists('priority', $data)) {
            $priority = $data['priority'];
            $allowed_priorities = ['low', 'medium', 'high'];
            if (!in_array($priority, $allowed_priorities)) {
                respond(400, "Invalid priority");
            }
            $fields[] = "priority = ?";
            $params[] = $priority;
        }

        if (array_key_exists('status', $data)) {
            $status = $data['status'];
            $allowed_statuses = ['open', 'in_progress', 'closed'];
            if (!in_array($status, $allowed_statuses)) {
                respond(400, "Invalid status");
            }
            $fields[] = "status = ?";
            $params[] = $status;
        }

        if (empty($fields)) {
            respond(400, "No fields to update");
        }

        $params[] = $id;

        $sql = "UPDATE tickets SET " . implode(", ", $fields) . " WHERE id = ?";

        $result = db()->execute_query($sql, $params);

        if ($result === false) {
            respond(404, "Not Found");
        }
        
        respond(200, "Updated");
        break;

}