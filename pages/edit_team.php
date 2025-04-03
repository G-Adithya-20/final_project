<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: error.php");
    exit();
}

// Check if team_id is provided
if (!isset($_GET['team_id'])) {
    header("Location: manage_teams.php");
    exit();
}

$team_id = intval($_GET['team_id']);

// Verify team exists
$team_query = "SELECT t.*, u.username as team_lead_name 
               FROM teams t 
               JOIN users u ON t.team_lead_id = u.user_id 
               WHERE t.team_id = ?";
$stmt = $conn->prepare($team_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_teams.php");
    exit();
}

$team = $result->fetch_assoc();

// Get team members
$members_query = "SELECT u.user_id, u.username, u.email 
                 FROM users u 
                 JOIN team_members tm ON u.user_id = tm.user_id 
                 WHERE tm.team_id = ?";
$stmt = $conn->prepare($members_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$members_result = $stmt->get_result();

// Rest of your HTML and form code will go here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team - <?php echo htmlspecialchars($team['team_name']); ?></title>
    <!-- Include your CSS and other head elements -->
</head>
<body>
    <div class="container">
        <h1>Edit Team: <?php echo htmlspecialchars($team['team_name']); ?></h1>
        <!-- Add your team management interface here -->
    </div>
</body>
</html>
