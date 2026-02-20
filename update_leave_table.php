<?php
require_once 'config.php';

$queries = [
    "ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS leave_days DECIMAL(4,1) DEFAULT 1.0 AFTER end_date",
    "ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS approved_by INT AFTER status",
    "ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL AFTER approved_by",
    "ALTER TABLE leave_requests ADD COLUMN IF NOT EXISTS rejection_reason TEXT AFTER approved_at"
];

foreach ($queries as $query) {
    if ($conn->query($query)) {
        echo "Success: " . substr($query, 0, 50) . "...<br>";
    } else {
        echo "Error: " . $conn->error . " (Query: $query)<br>";
    }
}

echo "Database update complete.";
?>
