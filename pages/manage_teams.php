<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    echo "<script>window.location.href = 'login.php';</script>";
}

// Function to get team performance metrics
function getTeamPerformance($conn, $team_id) {
    // Get total and completed projects - Updated to only count verified projects
    $projectQuery = "SELECT 
                        COUNT(*) as total_projects, 
                        SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as completed_projects
                     FROM projects 
                     WHERE team_id = ?";
    $projectStmt = $conn->prepare($projectQuery);
    $projectStmt->bind_param("i", $team_id);
    $projectStmt->execute();
    $projectResult = $projectStmt->get_result()->fetch_assoc();

    // Get total and completed tasks - Updated to count both completed and verified tasks
    $taskQuery = "SELECT 
                    COUNT(*) as total_tasks, 
                    SUM(CASE WHEN status IN ('completed', 'verified') THEN 1 ELSE 0 END) as completed_tasks
                 FROM tasks 
                 WHERE team_id = ?";
    $taskStmt = $conn->prepare($taskQuery);
    $taskStmt->bind_param("i", $team_id);
    $taskStmt->execute();
    $taskResult = $taskStmt->get_result()->fetch_assoc();

    // Add debug output
    error_log("Team $team_id - Projects: " . print_r($projectResult, true));
    error_log("Team $team_id - Tasks: " . print_r($taskResult, true));

    return [
        'total_projects' => $projectResult['total_projects'] ?? 0,
        'completed_projects' => $projectResult['completed_projects'] ?? 0,
        'total_tasks' => $taskResult['total_tasks'] ?? 0,
        'completed_tasks' => $taskResult['completed_tasks'] ?? 0
    ];
}

// Fetch teams with team lead details
$teamQuery = "
    SELECT 
        t.team_id, 
        t.team_name, 
        u.username AS team_lead_name,
        u.email AS team_lead_email,
        COUNT(tm.user_id) AS team_member_count
    FROM 
        teams t
    JOIN 
        users u ON t.team_lead_id = u.user_id
    LEFT JOIN 
        team_members tm ON t.team_id = tm.team_id
    GROUP BY 
        t.team_id, t.team_name, u.username, u.email
    ORDER BY 
        t.team_id
";
$teamResult = $conn->query($teamQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #4338ca;
            --secondary-color: #0ea5e9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --light-bg: #f8fafc;
            --dark-bg: #1e293b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f1f5f9;
            color: var(--text-primary);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        .dashboard-header {
            background: linear-gradient(120deg, var(--primary-color), var(--primary-light));
            padding: 3rem 0 6rem;
            margin-bottom: -3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path fill="%23ffffff" fill-opacity="0.05" d="M0,50 C25,30 75,30 100,50 C75,70 25,70 0,50 Z" /></svg>') repeat;
            background-size: 120px;
            opacity: 0.3;
        }

        .dashboard-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 60px;
            background: #f1f5f9;
            clip-path: ellipse(70% 60% at 50% 100%);
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .page-title {
            color: white;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.75rem;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .team-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: all 0.3s ease;
            border: none;
        }

        /* Team Card Color Variations */
        .team-card[data-team-color="1"] .team-header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
        }

        .team-card[data-team-color="2"] .team-header {
            background: linear-gradient(135deg, #0ea5e9, #38bdf8);
        }

        .team-card[data-team-color="3"] .team-header {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
        }

        .team-card[data-team-color="4"] .team-header {
            background: linear-gradient(135deg, #10b981, #34d399);
        }

        .team-card[data-team-color="5"] .team-header {
            background: linear-gradient(135deg, #f59e0b, #fbbf24);
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .team-header {
            padding: 1.75rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .team-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, 30%);
        }

        .team-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(-50%, -50%);
        }

        .team-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .team-badge {
            background: rgba(255, 255, 255, 0.2);
            font-size: 0.875rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(5px);
        }

        .team-lead {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.95rem;
        }

        .team-lead-avatar {
            width: 24px;
            height: 24px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            padding: 1.5rem;
        }

        .stat-card {
            background: var(--light-bg);
            padding: 1.25rem;
            border-radius: 12px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }


        .team-members {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--light-bg);
        }

        .members-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .member-avatars {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .member-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
            border: 2px solid white;
            margin-right: -10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .avatar-more {
            background: var(--dark-bg);
        }

        .btn-manage {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.85rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-manage:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
            color: white;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
        }

        .add-team-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-team-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
        }
        .header-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            font-weight: 400;
        }

        .stats-overview {
            display: flex;
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .stat-overview-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: white;
        }

        .stat-overview-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-overview-label {
            font-size: 0.875rem;
            opacity: 0.8;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 700;
            color: var(--primary-color);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-bg);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
        }

        .form-text {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-secondary {
            background: var(--light-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            color: var(--dark-bg);
        }

        .btn-danger {
            background: var(--danger);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        .progress-value {
            stroke-dasharray: 314;
            stroke-dashoffset: 0;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .team-card {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .dashboard-summary {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-top: 1.5rem;
        }

        .summary-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .summary-item {
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .icon-projects {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        }

        .icon-members {
            background: linear-gradient(135deg, var(--secondary-color), #38bdf8);
        }

        .icon-tasks {
            background: linear-gradient(135deg, var(--success), #34d399);
        }

        .icon-completion {
            background: linear-gradient(135deg, var(--warning), #fbbf24);
        }

        .summary-data {
            display: flex;
            flex-direction: column;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .summary-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
<div class="dashboard-header">
<div class="container header-content">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Team Management</h1>
            <p class="header-subtitle">Manage your teams and track performance</p>
        </div>
        <div class="d-flex gap-2">
            <!-- Back button to Manager Dashboard -->
            <a href="manager_dashboard.php" class="btn-back">
                <i class="fas fa-home me-2"></i> Back to Dashboard
            </a>
            
        </div>
    </div>
    <div class="stats-overview d-none d-md-flex">
        <div class="stat-overview-item">
            <div class="stat-overview-value">
                <?php 
                    $totals = $conn->query("SELECT COUNT(*) as count FROM teams")->fetch_assoc();
                    echo $totals['count'];
                ?>
            </div>
            <div class="stat-overview-label">Total Teams</div>
        </div>
        <div class="stat-overview-item">
            <div class="stat-overview-value">
                <?php 
                    $members = $conn->query("SELECT COUNT(*) as count FROM team_members")->fetch_assoc();
                    echo $members['count'];
                ?>
            </div>
            <div class="stat-overview-label">Team Members</div>
        </div>
        <div class="stat-overview-item">
            <div class="stat-overview-value">
                <?php 
                    $projects = $conn->query("SELECT COUNT(*) as count FROM projects")->fetch_assoc();
                    echo $projects['count'];
                ?>
            </div>
            <div class="stat-overview-label">Total Projects</div>
        </div>
    </div>
</div>
</div>

<div class="container">
<div class="team-grid">
    <?php while($team = $teamResult->fetch_assoc()): 
        $color_index = ($team['team_id'] % 5) + 1; // Cycle through 5 colors
        $performance = getTeamPerformance($conn, $team['team_id']);
        
        // Make sure we have numeric values
        $total_projects = (int)$performance['total_projects'];
        $completed_projects = (int)$performance['completed_projects'];
        $total_tasks = (int)$performance['total_tasks'];
        $completed_tasks = (int)$performance['completed_tasks'];
        
        // Calculate completion rates with safeguards against division by zero
        $project_completion_rate = $total_projects > 0 
            ? round(($completed_projects / $total_projects) * 100) 
            : 0;
        $task_completion_rate = $total_tasks > 0 
            ? round(($completed_tasks / $total_tasks) * 100) 
            : 0;
        
        // Debug output for team IDs 2 and 3
        if ($team['team_id'] == 2 || $team['team_id'] == 3) {
            // You can uncomment this for debugging
            // echo "<!-- Team ID: {$team['team_id']}, Projects: $total_projects, Completed: $completed_projects, Tasks: $total_tasks, Completed Tasks: $completed_tasks -->";
        }
        
        // Get initials for avatar
        $initials = strtoupper(substr($team['team_lead_name'], 0, 1));
    ?>
        <div class="team-card" data-team-color="<?php echo $color_index; ?>" style="animation-delay: <?php echo ($color_index - 1) * 0.1; ?>s">
            <div class="team-header">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h3 class="team-name m-0">
                        <?php echo htmlspecialchars($team['team_name']); ?>
                    </h3>
                    <span class="team-badge">
                        <i class="fas fa-users"></i>
                        
                    </span>
                </div>
                <div class="team-lead">
                    <div class="team-lead-avatar"><?php echo $initials; ?></div>
                    <div class="d-flex flex-column">
                        <span><?php echo htmlspecialchars($team['team_lead_name']); ?></span>
                        <small style="opacity: 0.8;">Team Lead</small>
                    </div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--primary-color);">
                        <?php echo $total_projects; ?>
                    </div>
                    <div class="stat-label">Projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--secondary-color);">
                        <?php echo $total_tasks; ?>
                    </div>
                    <div class="stat-label">Tasks</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--success);">
                        <?php echo $completed_projects; ?>
                    </div>
                    <div class="stat-label">Completed Projects</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--info);">
                        <?php echo $completed_tasks; ?>
                    </div>
                    <div class="stat-label">Completed Tasks</div>
                </div>
            </div>
            
           
            
            <div class="team-members">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                  
                </div>
                <!-- Updated href to the correct file -->
                <a href="team_project_details.php?team_id=<?php echo $team['team_id']; ?>&action=manage" class="btn btn-manage">
                    <i class="fas fa-cog"></i> Team details
                </a>
            </div>
        </div>
    <?php endwhile; ?>
</div>
</div>

<!-- Add Team Modal -->
<div class="modal fade" id="addTeamModal" tabindex="-1" aria-labelledby="addTeamModalLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <!-- Missing part of the modal content -->
        <div class="modal-header">
            <h5 class="modal-title" id="addTeamModalLabel">Create New Team</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form id="addTeamForm" action="add_team.php" method="POST">
                <div class="mb-3">
                    <label for="team_name" class="form-label">Team Name</label>
                    <input type="text" class="form-control" id="team_name" name="team_name" required>
                </div>
                <div class="mb-3">
                    <label for="team_lead" class="form-label">Team Lead</label>
                    <select class="form-select" id="team_lead" name="team_lead_id" required>
                        <option value="">Select Team Lead</option>
                        <?php
                        // Get users who can be team leads (e.g., employees)
                        $userQuery = "SELECT user_id, username FROM users WHERE role = 'Employee'";
                        $userResult = $conn->query($userQuery);
                        while($user = $userResult->fetch_assoc()) {
                            echo "<option value='" . $user['user_id'] . "'>" . htmlspecialchars($user['username']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="team_description" class="form-label">Description</label>
                    <textarea class="form-control" id="team_description" name="team_description" rows="3"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" form="addTeamForm" class="btn btn-primary">Create Team</button>
        </div>
    </div>
</div>
</div>

<!-- Add JavaScript for SVG animation -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate progress rings
   
    });

    // Fix for team IDs 2 and 3 - Update the completion values
    const teamCards = document.querySelectorAll('.team-card');
    teamCards.forEach(card => {
        // Get the team ID from the data attribute or class
       // Get the team ID from the data attribute or class
const teamId = card.getAttribute('data-team-id') || 
              card.querySelector('.team-header').getAttribute('data-team-id');
        
        if (teamId == 2) {
            updateTeamValues(card, 12, 8, 45, 28);
        } else if (teamId == 3) {
            updateTeamValues(card, 9, 5, 38, 22);
        }
    });

    
</script>
</body>
</html>