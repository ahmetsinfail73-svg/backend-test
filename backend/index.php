<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:4200');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}



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
            $errors['title'] = 'Заголовок должен быть от 3 до 255 символов';
        }

        if (
            trim($this->data['description'] ?? '') === ''
            || mb_strlen($this->data['description'] ?? '') < 20
        ) {
            $errors['description'] = 'Описание должно быть не менее 20 символов';
        }

         $this->data['priority'] = PriorityEnum::tryFrom($this->data['priority']);
        if ($this->data['priority'] === null) {
            $errors['priority'] = 'Некорректный приоритет';
        }

        if (isset($this->data['status'])) {
            $this->data['status'] = StatusEnum::tryFrom($this->data['status']);
            if ($this->data['status'] === null) {
                $errors['status'] = 'Некорректный статус';
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
                $errors['title'] = 'Заголовок должен быть от 3 до 255 символов';
            } else {
                $this->data['title'] = $title;
            }
        }

        if (isset($this->data['description'])) {
            $description = trim($this->data['description']);
            if ($description === '' || mb_strlen($description) < 20) {
                $errors['description'] = 'Описание должно быть не менее 20 символов';
            } else {
                $this->data['description'] = $description;
            }
        }

        if (isset($this->data['priority'])) {
            $this->data['priority'] = PriorityEnum::tryFrom($this->data['priority']);
            if ($this->data['priority'] === null) {
                $errors['priority'] = 'Некорректный приоритет';
            }
        }

        
        if (isset($this->data['status'])) {
            $this->data['status'] = StatusEnum::tryFrom($this->data['status']);
            if ($this->data['status'] === null) {
                $errors['status'] = 'Некорректный статус';
            }
        }

        
        if (empty($this->data)) {
            $errors['data'] = 'Нет полей для обновления';
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
    respond(404, 'Ресурс не найден');
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
            respond(405, 'Метод не разрешён');
    }
}

switch ($resource) {
    case 'tickets':
        switch ($method) {
            case 'GET':
                if ($id) {
                    require __DIR__ . '/api/tickets/tickets_get_one.php';
                } else {
                    require __DIR__ . '/api/tickets/tickets_get.php';
                }
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
                respond(405, 'Метод не разрешён');
        }
        break;

    default:
        respond(404, 'Ресурс не найден');
}

function respond($code, $data)
{
    http_response_code($code);
    if (is_string($data)) {
        $response = ['message' => $data];
        if ($code >= 400) {
            $response['statusCode'] = $code;
        }
        echo json_encode($response);
    } else {
        echo json_encode($data);
    }
    exit;
}
