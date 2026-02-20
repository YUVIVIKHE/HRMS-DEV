<?php
/**
 * Dropdown Helper Functions
 * Purpose: Centralized functions to fetch dropdown values from database
 * Requirement: Master Drop-down Management module
 */

/**
 * Get all active dropdown values for a category
 * @param mysqli $conn Database connection
 * @param string $category_name Category name (e.g., 'department', 'designation')
 * @return array Array of dropdown values
 */
function getDropdownValues($conn, $category_name) {
    $values = [];
    
    $stmt = $conn->prepare("
        SELECT dv.id, dv.value_text 
        FROM dropdown_values dv
        INNER JOIN dropdown_categories dc ON dv.category_id = dc.id
        WHERE dc.category_name = ? AND dv.status = 'active' AND dc.status = 'active'
        ORDER BY dv.display_order ASC, dv.value_text ASC
    ");
    
    $stmt->bind_param("s", $category_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $values[] = $row;
    }
    
    $stmt->close();
    return $values;
}

/**
 * Render dropdown options HTML
 * @param mysqli $conn Database connection
 * @param string $category_name Category name
 * @param string $selected_value Currently selected value (optional)
 * @param bool $include_empty Include empty option (default: true)
 * @return string HTML options
 */
function renderDropdownOptions($conn, $category_name, $selected_value = '', $include_empty = true) {
    $html = '';
    
    if ($include_empty) {
        $html .= '<option value="">Select ' . ucwords(str_replace('_', ' ', $category_name)) . '</option>';
    }
    
    $values = getDropdownValues($conn, $category_name);
    
    foreach ($values as $value) {
        $selected = ($value['value_text'] == $selected_value) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($value['value_text']) . '" ' . $selected . '>';
        $html .= htmlspecialchars($value['value_text']);
        $html .= '</option>';
    }
    
    return $html;
}

/**
 * Get all dropdown categories
 * @param mysqli $conn Database connection
 * @return array Array of categories
 */
function getAllDropdownCategories($conn) {
    $categories = [];
    
    $result = $conn->query("
        SELECT id, category_name, category_label, description, status
        FROM dropdown_categories
        ORDER BY category_label ASC
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

/**
 * Get dropdown values by category ID
 * @param mysqli $conn Database connection
 * @param int $category_id Category ID
 * @return array Array of values
 */
function getDropdownValuesByCategoryId($conn, $category_id) {
    $values = [];
    
    $stmt = $conn->prepare("
        SELECT id, value_text, display_order, status
        FROM dropdown_values
        WHERE category_id = ?
        ORDER BY display_order ASC, value_text ASC
    ");
    
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $values[] = $row;
    }
    
    $stmt->close();
    return $values;
}
?>
