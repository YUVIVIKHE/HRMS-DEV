<?php
require_once 'config.php';

echo "<h2>Database Update Script</h2>";
echo "<p>Adding shift_type and required_hours columns...</p>";

// Add columns
$sql1 = "ALTER TABLE employees 
ADD COLUMN timezone VARCHAR(100) DEFAULT 'Asia/Kolkata' AFTER employee_type,
ADD COLUMN shift_type ENUM('fixed', 'flexible') DEFAULT 'fixed' AFTER timezone,
ADD COLUMN required_hours DECIMAL(4,2) DEFAULT 8.00 AFTER shift_type";

if ($conn->query($sql1) === TRUE) {
    echo "<p style='color: green;'>✓ Columns added successfully!</p>";
} else {
    if (strpos($conn->error, 'Duplicate column name') !== false) {
        echo "<p style='color: orange;'>⚠ Columns already exist, skipping...</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
}

// Update existing records
$sql2 = "UPDATE employees 
SET timezone = 'Asia/Kolkata', 
    shift_type = 'fixed', 
    required_hours = 8.00 
WHERE timezone IS NULL OR shift_type IS NULL OR required_hours IS NULL";

if ($conn->query($sql2) === TRUE) {
    echo "<p style='color: green;'>✓ Existing records updated successfully!</p>";
    echo "<p>Affected rows: " . $conn->affected_rows . "</p>";
} else {
    echo "<p style='color: red;'>✗ Error updating records: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<p><strong>Database update complete!</strong></p>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";

$conn->close();
?>
