<?php

require __DIR__ . '/../config/database.php';

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
            
            $attachStmt = db()->prepare(
                'SELECT id, filepath, size, uploaded_at FROM ticket_attachments 
                 WHERE ticket_id = ? 
                 ORDER BY uploaded_at DESC'
            );
            $attachStmt->bind_param('i', $row['id']);
            $attachStmt->execute();
            $attachResult = $attachStmt->get_result();
            
            $attachments = [];
            while ($attach = $attachResult->fetch_assoc()) {
                $attachments[] = $attach;
            }
            
            $row['attachments'] = $attachments;
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
