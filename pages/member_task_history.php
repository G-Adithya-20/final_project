<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in as team member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamMember') {
    echo "<script>window.location.href = 'error.php';</script>";
    exit;
}

// Validate task_id parameter
if (!isset($_GET['task_id'])) {
    echo "<script>window.location.href = 't_member_mytasks.php';</script>";
    exit;
}

$task_id = (int)$_GET['task_id'];
$user_id = $_SESSION['user_id'];

// Fetch task details with security check
$task_query = "
    SELECT t.*, p.title AS project_name 
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.project_id
    WHERE t.task_id = ? AND t.assigned_to = ?
";
$stmt = $conn->prepare($task_query);
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    echo "<script>window.location.href = 't_member_mytasks.php';</script>";
    exit;
}

// Fetch task history
$history_query = "
    SELECT h.*, u.username AS changed_by_name
    FROM task_status_history h
    LEFT JOIN users u ON h.changed_by = u.user_id
    WHERE h.task_id = ?
    ORDER BY h.changed_at DESC
";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$history_result = $stmt->get_result();

// Function to get status badge color
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'not_viewed': return 'secondary';
        case 'in_progress': return 'primary';
        case 'completed': return 'success';
        case 'verified': return 'info';
        default: return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task History</title>
    <!-- Include your CSS and other head elements -->
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-history me-2"></i>
                Task History
            </div>
            <a href="t_member_mytasks.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to My Tasks
            </a>
        </div>
    </nav>

    <!-- Rest of your task history display code -->
</body>
</html>
