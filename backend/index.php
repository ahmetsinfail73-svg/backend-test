<?php

header('Content-Type: application/json');



enum PriorityEnum: string {
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
}

enum StatusEnum: string {
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case CLOSED = 'closed';
}

class TOutdata extends stdClass
{
    public string $message;
    public int $code;
}

class TicketDto extends TOutdata
{
    public function __construct(
        public array $data
    ) {}

    public function validate(): array
    {
        $errors = [];

        if (
            trim($this->data['title'] ?? '') === ''
            || mb_strlen($this->data['title'] ?? '') < 3
            || mb_strlen($this->data['title'] ?? '') > 255
        ) {
            $errors['title'] = 'Title must be between 3 and 255 characters';
        }

        if (
            trim($this->data['description'] ?? '') === ''
            || mb_strlen($this->data['description'] ?? '') < 20
        ) {
            $errors['description'] = 'Description must be at least 20 characters';
        }

         $this->data['priority'] = PriorityEnum::tryFrom($this->data['priority']);
        if ($this->data['priority'] === null) {
            $errors['priority'] = "Invalid priority";
        }

        if (isset($this->data['status'])) {
            $this->data['status'] = StatusEnum::tryFrom($this->data['status']);
            if ($this->data['status'] === null) {
                $errors['status'] = "Invalid status";
            }
        }

        return $errors;
    }
}


class UpdateTicketDto extends TOutdata
{
    public function __construct(
        public array $data
    ) {}

    public function validate(): array
    {
        $errors = [];

        if (isset($this->data['title'])) {
            $title = trim($this->data['title']);
            if ($title === '' || mb_strlen($title) < 3 || mb_strlen($title) > 255) {
                $errors['title'] = 'Title must be between 3 and 255 characters';
            } else {
                $this->data['title'] = $title;
            }
        }

        if (isset($this->data['description'])) {
            $description = trim($this->data['description']);
            if ($description === '' || mb_strlen($description) < 20) {
                $errors['description'] = 'Description must be at least 20 characters';
            } else {
                $this->data['description'] = $description;
            }
        }

        if (isset($this->data['priority'])) {
            $this->data['priority'] = PriorityEnum::tryFrom($this->data['priority']);
            if ($this->data['priority'] === null) {
                $errors['priority'] = 'Invalid priority';
            }
        }

        
        if (isset($this->data['status'])) {
            $this->data['status'] = StatusEnum::tryFrom($this->data['status']);
            if ($this->data['status'] === null) {
                $errors['status'] = 'Invalid status';
            }
        }

        
        if (empty($this->data)) {
            $errors['data'] = 'No fields to update';
        }

        return $errors;
    }


    public function toSqlSet(): array
{
    $fields = [];
    $params = [];

    foreach (['title', 'description', 'priority', 'status'] as $field) {
        if (isset($this->data[$field])) {
            $fields[] = "$field = ?";
            $value = $this->data[$field];

            
            if ($value instanceof BackedEnum) { 
                $value = $value->value;
            }

            $params[] = $value;
        }
    }

    return [$fields, $params];
}
}


$uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];
$segments = explode('/', $uri);

if ($segments[0] !== 'api') {
    respond(404, 'Resource not found');
}

$resource = $segments[1] ?? null;
$id = $segments[2] ?? null;
$sub  = $segments[3] ?? null;


if ($resource === 'tickets' && $sub === 'attachments') {
    switch ($method) {
        case 'POST':
            require __DIR__ . '/api/attachments/attachments_post.php';
            break;
        case 'GET':
            require __DIR__ . '/api/attachments/attachments_get.php';
            break;
        default:
            respond(405, 'Method not allowed');
    }
}

switch ($resource) {
    case 'tickets':
        switch ($method) {
            case 'GET':
                require __DIR__ . '/api/tickets/tickets_get.php';
                break;
            case 'POST':
                require __DIR__ . '/api/tickets/tickets_post.php';
                break;
            case 'PUT':
                require __DIR__ . '/api/tickets/tickets_put.php';
                break;
            case 'DELETE':
                require __DIR__ . '/api/tickets/tickets_delete.php';
                break;
            default:
                respond(405, 'Method not allowed');
        }
        break;

    default:
        respond(404, 'Resource not found');
}

function respond($code, $data)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}
