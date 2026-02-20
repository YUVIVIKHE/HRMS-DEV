<?php
// Test password verification
$password = 'admin123';
$hash_from_db = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Testing password: admin123\n";
echo "Hash from database: $hash_from_db\n\n";

if (password_verify($password, $hash_from_db)) {
    echo "✓ Password verification: SUCCESS\n";
} else {
    echo "✗ Password verification: FAILED\n";
}

echo "\n--- Generating new hash for 'admin123' ---\n";
$new_hash = password_hash('admin123', PASSWORD_DEFAULT);
echo "New hash: $new_hash\n";

// Test database connection
require_once 'config.php';

echo "\n--- Checking database connection ---\n";
if ($conn->connect_error) {
    echo "✗ Database connection failed: " . $conn->connect_error . "\n";
} else {
    echo "✓ Database connected successfully\n";
    
    // Check if admin exists
    $result = $conn->query("SELECT id, name, email, password, status FROM admins WHERE email = 'admin@hrms.com'");
    
    if ($result && $result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "✓ Admin found in database\n";
        echo "  - ID: " . $admin['id'] . "\n";
        echo "  - Name: " . $admin['name'] . "\n";
        echo "  - Email: " . $admin['email'] . "\n";
        echo "  - Status: " . $admin['status'] . "\n";
        echo "  - Password hash: " . $admin['password'] . "\n";
        
        if (password_verify('admin123', $admin['password'])) {
            echo "✓ Password 'admin123' matches database hash\n";
        } else {
            echo "✗ Password 'admin123' does NOT match database hash\n";
            echo "\nTo fix this, run this SQL query:\n";
            echo "UPDATE admins SET password = '$new_hash' WHERE email = 'admin@hrms.com';\n";
        }
    } else {
        echo "✗ Admin not found in database\n";
        echo "\nTo fix this, run this SQL query:\n";
        echo "INSERT INTO admins (name, email, password, status) VALUES ('Admin User', 'admin@hrms.com', '$new_hash', 'active');\n";
    }
}
?>
