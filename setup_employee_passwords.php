<?php
/**
 * Setup Employee Passwords
 * Purpose: Add passwords for sample employees so they can log in
 */

require_once 'config.php';

echo "<h2>Setup Employee Passwords</h2>";
echo "<p>Adding passwords for sample employees...</p>";

// Password for all sample employees
$password = 'employee123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Sample employees to update
$employees = [
    'john.doe@company.com',
    'jane.smith@company.com',
    'mike.johnson@company.com'
];

$updated = 0;
$errors = 0;

foreach ($employees as $email) {
    $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<p style='color: green;'>✓ Updated password for: $email</p>";
            $updated++;
        } else {
            echo "<p style='color: orange;'>⚠ Employee not found: $email</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error updating: $email</p>";
        $errors++;
    }
    $stmt->close();
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Updated:</strong> $updated employees</p>";
echo "<p><strong>Errors:</strong> $errors</p>";

if ($updated > 0) {
    echo "<div style='background: #d1fae5; padding: 16px; border-radius: 8px; margin-top: 20px;'>";
    echo "<h3 style='color: #065f46; margin-top: 0;'>✓ Employee Login Credentials</h3>";
    echo "<p><strong>Password for all sample employees:</strong> <code style='background: white; padding: 4px 8px; border-radius: 4px;'>employee123</code></p>";
    echo "<ul style='color: #065f46;'>";
    echo "<li><strong>Email:</strong> john.doe@company.com | <strong>Password:</strong> employee123</li>";
    echo "<li><strong>Email:</strong> jane.smith@company.com | <strong>Password:</strong> employee123</li>";
    echo "<li><strong>Email:</strong> mike.johnson@company.com | <strong>Password:</strong> employee123</li>";
    echo "</ul>";
    echo "</div>";
}

// Verify passwords work
echo "<hr>";
echo "<h3>Verification:</h3>";
$result = $conn->query("SELECT employee_id, first_name, last_name, email, 
                        CASE WHEN password IS NOT NULL AND password != '' THEN 'Yes' ELSE 'No' END as has_password
                        FROM employees 
                        WHERE email IN ('john.doe@company.com', 'jane.smith@company.com', 'mike.johnson@company.com')");

if ($result) {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th>Employee ID</th><th>Name</th><th>Email</th><th>Has Password</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status_color = $row['has_password'] == 'Yes' ? 'green' : 'red';
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['employee_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td style='color: $status_color; font-weight: bold;'>" . $row['has_password'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

$conn->close();
?>
