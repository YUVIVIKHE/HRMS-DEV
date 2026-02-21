<?php
/**
 * Project Budget Sync Utility
 * Purpose: Recalculates utilized_amount for all projects based on project_expenses
 */

session_start();
require_once 'config.php';

// Check if admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die("Unauthorized access.");
}

$results = [];

// Fetch all projects
$projects_sql = "SELECT id, project_name, utilized_amount FROM projects";
$projects_res = $conn->query($projects_sql);

if ($projects_res) {
    while ($project = $projects_res->fetch_assoc()) {
        $project_id = $project['id'];
        $old_amount = floatval($project['utilized_amount']);
        
        // Calculate current total from expenses
        $expenses_stmt = $conn->prepare("SELECT SUM(amount) as total FROM project_expenses WHERE project_id = ?");
        $expenses_stmt->bind_param("i", $project_id);
        $expenses_stmt->execute();
        $expenses_res = $expenses_stmt->get_result()->fetch_assoc();
        $new_amount = floatval($expenses_res['total'] ?? 0);
        $expenses_stmt->close();
        
        if (abs($old_amount - $new_amount) > 0.001) {
            // Update the project
            $update_stmt = $conn->prepare("UPDATE projects SET utilized_amount = ? WHERE id = ?");
            $update_stmt->bind_param("di", $new_amount, $project_id);
            if ($update_stmt->execute()) {
                $results[] = [
                    'name' => $project['project_name'],
                    'old' => $old_amount,
                    'new' => $new_amount,
                    'status' => 'Updated'
                ];
            } else {
                $results[] = [
                    'name' => $project['project_name'],
                    'status' => 'Error: ' . $conn->error
                ];
            }
            $update_stmt->close();
        } else {
            $results[] = [
                'name' => $project['project_name'],
                'status' => 'Synced'
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget Sync - HRMS</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .sync-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .sync-table th, .sync-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-updated { background: #dcfce7; color: #15803d; }
        .status-synced { background: #f3f4f6; color: #6b7280; }
    </style>
</head>
<body>
    <div style="max-width: 800px; margin: 40px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2>Budget Synchronization</h2>
        <p>Recalculated <code>utilized_amount</code> for all projects based on actual <code>project_expenses</code> entries.</p>
        
        <table class="sync-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Old Amount</th>
                    <th>New Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $res): ?>
                <tr>
                    <td><?php echo htmlspecialchars($res['name']); ?></td>
                    <td>₹<?php echo isset($res['old']) ? number_format($res['old'], 2) : '-'; ?></td>
                    <td>₹<?php echo isset($res['new']) ? number_format($res['new'], 2) : '-'; ?></td>
                    <td>
                        <span class="status-badge <?php echo $res['status'] == 'Updated' ? 'status-updated' : 'status-synced'; ?>">
                            <?php echo $res['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px;">
            <a href="projects.php" class="btn-primary" style="text-decoration: none; padding: 10px 20px; display: inline-block;">Back to Projects</a>
        </div>
    </div>
</body>
</html>
