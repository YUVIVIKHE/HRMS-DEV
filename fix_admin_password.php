<?php
require_once 'config.php';

// Generate correct password hash for 'admin123'
$correct_password = 'admin123';
$correct_hash = password_hash($correct_password, PASSWORD_DEFAULT);

echo "Fixing admin password...\n\n";

// Update the admin password
$stmt = $conn->prepare("UPDATE admins SET password = ? WHERE email = 'admin@hrms.com'");
$stmt->bind_param("s", $correct_hash);

if ($stmt->execute()) {
    echo "✓ Admin password updated successfully!\n\n";
    echo "You can now login with:\n";
    echo "Email: admin@hrms.com\n";
    echo "Password: admin123\n";
} else {
    echo "✗ Error updating password: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
