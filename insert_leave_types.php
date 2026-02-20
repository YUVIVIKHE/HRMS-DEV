<?php
require_once 'config.php';

$leave_types = [
    'Casual Leave',
    'Sick Leave',
    'Earned Leave',
    'Maternity Leave',
    'Paternity Leave',
    'LWP (Leave Without Pay)'
];

try {
    // Get category ID for leave_type
    $stmt = $conn->prepare("SELECT id FROM dropdown_categories WHERE category_name = 'leave_type'");
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    
    if (!$category) {
        // Create category if not exists
        $conn->query("INSERT INTO dropdown_categories (category_name, category_label, description) VALUES ('leave_type', 'Leave Type', 'Types of employee leave')");
        $category_id = $conn->insert_id;
        echo "Created 'leave_type' category.<br>";
    } else {
        $category_id = $category['id'];
        echo "Found 'leave_type' category.<br>";
    }
    
    // Check if values already exist
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM dropdown_values WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
    
    if ($count > 0) {
        echo "Leave types already exist ($count found). No changes made.<br>";
    } else {
        // Insert values
        $stmt = $conn->prepare("INSERT INTO dropdown_values (category_id, value_text, display_order) VALUES (?, ?, ?)");
        
        foreach ($leave_types as $index => $type) {
            $display_order = $index + 1;
            $stmt->bind_param("isi", $category_id, $type, $display_order);
            if ($stmt->execute()) {
                echo "Inserted: $type<br>";
            } else {
                echo "Error inserting $type: " . $conn->error . "<br>";
            }
        }
    }
    
    echo "Done! Leave Type dropdown should now be populated.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
