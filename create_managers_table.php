<?php
require_once 'config.php';

echo "<h2>Create Managers Table</h2>";
echo "<p>Creating managers table...</p>";

$sql = "CREATE TABLE IF NOT EXISTS managers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Managers table created successfully!</p>";
} else {
    if (strpos($conn->error, 'already exists') !== false) {
        echo "<p style='color: orange;'>⚠ Managers table already exists!</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Setup complete!</strong></p>";
echo "<p><a href='managers.php'>Go to Managers Page</a> | <a href='dashboard.php'>Go to Dashboard</a></p>";

$conn->close();
?>
