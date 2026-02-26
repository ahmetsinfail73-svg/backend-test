<?php

require __DIR__ . '/database.php';

$sql = "
CREATE TABLE IF NOT EXISTS ticket_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT UNSIGNED NOT NULL,
    filepath VARCHAR(255) NOT NULL,
    size INT UNSIGNED NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (db()->query($sql)) {
    echo "Table 'tickets' created or already exists\n";
} else {    
    echo "Error creating table: " . db()->error . "\n";
}
