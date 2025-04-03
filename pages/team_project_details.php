<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

// Validate team_id
$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
if ($team_id <= 0) {
    // header("Location: teams_view.php");
    // exit();
    echo "<script>window.location.href = 'teams_view.php';</script>";

}

// Fetch team details
$teamQuery = "
    SELECT 
        t.team_id, 
        t.team_name, 
        u.username AS team_lead_name,
        u.email AS team_lead_email
    FROM 
        teams t
    JOIN 
        users u ON t.team_lead_id = u.user_id
    WHERE 
        t.team_id = ?
";
$teamStmt = $conn->prepare($teamQuery);
$teamStmt->bind_param("i", $team_id);
$teamStmt->execute();
$teamDetails = $teamStmt->get_result()->fetch_assoc();

// Fetch team members
$membersQuery = "
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.role
    FROM 
        team_members tm
    JOIN 
        users u ON tm.user_id = u.user_id
    WHERE 
        tm.team_id = ?
";
$membersStmt = $conn->prepare($membersQuery);
$membersStmt->bind_param("i", $team_id);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();

// Fetch team projects
$projectsQuery = "
    SELECT 
        project_id,
        title,
        description,
        status,
        start_date,
        due_date
    FROM 
        projects
    WHERE 
        team_id = ?
    ORDER BY 
        created_at DESC
";
$projectsStmt = $conn->prepare($projectsQuery);
$projectsStmt->bind_param("i", $team_id);
$projectsStmt->execute();
$projectsResult = $projectsStmt->get_result();

// Fetch team tasks
$tasksQuery = "
    SELECT 
        t.task_id,
        t.task_title,
        t.task_description,
        t.status,
        t.due_date,
        u.username AS assigned_to
    FROM 
        tasks t
    LEFT JOIN 
        users u ON t.assigned_to = u.user_id
    WHERE 
        t.team_id = ?
    ORDER BY 
        t.created_at DESC
";
$tasksStmt = $conn->prepare($tasksQuery);
$tasksStmt->bind_param("i", $team_id);
$tasksStmt->execute();
$tasksResult = $tasksStmt->get_result();

// Count team stats
$totalProjects = $projectsResult->num_rows;
$totalTasks = $tasksResult->num_rows;
$totalMembers = $membersResult->num_rows;

// Count completed projects - Only count verified projects
$completedProjects = 0;
$projectsResult->data_seek(0);
while($project = $projectsResult->fetch_assoc()) {
    if($project['status'] === 'verified') {  // Changed to only count 'verified' status
        $completedProjects++;
    }
}
$projectCompletion = $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100) : 0;

// Count completed tasks - Update this section
$completedTasks = 0;
$tasksResult->data_seek(0);
while($task = $tasksResult->fetch_assoc()) {
    // Debug output
    error_log("Task status: " . $task['status']);
    
    // Count tasks that are either 'completed' or 'verified'
    if($task['status'] === 'completed' || $task['status'] === 'verified') {
        $completedTasks++;
    }
}
$taskCompletion = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

// Add debug logging
error_log("Team $team_id - Total Tasks: $totalTasks, Completed Tasks: $completedTasks");
error_log("Team $team_id - Total Projects: $totalProjects, Verified Projects: $completedProjects");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Details - <?php echo htmlspecialchars($teamDetails['team_name']); ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --info-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            background-color: #f5f7fb;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 60px;
            background: var(--primary-color);
            border-radius: 10px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        .team-card {
            background: white;
        }
        
        .team-card .card-body {
            padding: 1.5rem;
        }
        
        .task-card {
            border-left: 4px solid transparent;
        }
        
        .task-card.status-completed {
            border-left-color: #4cc9f0;
        }
        
        .task-card.status-inprogress {
            border-left-color: #f72585;
        }
        
        .task-card.status-pending {
            border-left-color: #fcbf49;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .badge {
            padding: 0.5em 0.75em;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .badge.bg-success {
            background-color: var(--success-color) !important;
        }
        
        .badge.bg-warning {
            background-color: var(--warning-color) !important;
            color: white;
        }
        
        .badge.bg-info {
            background-color: var(--info-color) !important;
            color: white;
        }
        
        .stat-card {
            padding: 1.25rem;
            text-align: center;
            border-radius: 12px;
            color: white;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card-primary {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
        }
        
        .stat-card-success {
            background: linear-gradient(135deg, #4cc9f0, #4895ef);
        }
        
        .stat-card-warning {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }

        .member-list li {
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.1);
        }
        
        .member-list li:last-child {
            border-bottom: none;
        }
        
        .member-role {
            background-color: #e9ecef;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            color: #495057;
        }
        
        .btn-back {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .project-item {
            border-radius: 10px;
            background-color: white;
            margin-bottom: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .project-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .project-dates {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-right: 8px;
        }
        
        .description-text {
            line-height: 1.6;
            color: #495057;
        }

        /* New styles for enhanced tables */
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
            border: none;
        }
        
        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            display: block;
        }
        
        .stat-card .progress {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        .truncate-text {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
        }
        
        .status-dot {
            display: inline-block;
            height: 10px;
            width: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-dot.completed {
            background-color: var(--success-color);
        }
        
        .status-dot.verified {
            background-color: var(--primary-color);
        }
        
        .status-dot.inprogress {
            background-color: var(--warning-color);
        }
        
        .status-dot.instudy, .status-dot.pending {
            background-color: var(--info-color);
        }
        
        .stats-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-icon-container {
            display: flex;
            justify-content: center;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="page-header">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="page-title">
                        <i class="fas fa-tasks me-3"></i>Team Details: <?php echo htmlspecialchars($teamDetails['team_name']); ?>
                    </h1>
                    <div class="ms-auto">
                        <a href="manage_teams.php" class="btn btn-back">
                            <i class="fas fa-arrow-left me-2"></i>Back to Team
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Stats with aligned icons -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon-container">
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $totalMembers; ?></h3>
                    <p class="mb-0">Team Members</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon-container">
                        <i class="fas fa-project-diagram stat-icon"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $completedProjects; ?> / <?php echo $totalProjects; ?></h3>
                    <p class="mb-0">Projects Completed</p>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-light" role="progressbar" style="width: <?php echo $projectCompletion; ?>%" aria-valuenow="<?php echo $projectCompletion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon-container">
                        <i class="fas fa-tasks stat-icon"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $completedTasks; ?> / <?php echo $totalTasks; ?></h3>
                    <p class="mb-0">Tasks Completed</p>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-light" role="progressbar" style="width: <?php echo $taskCompletion; ?>%" aria-valuenow="<?php echo $taskCompletion; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card team-card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="section-title">Team Lead</h5>
                                <div class="d-flex align-items-center">
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($teamDetails['team_lead_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($teamDetails['team_lead_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($teamDetails['team_lead_email']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5 class="section-title">Team Members</h5>
                                <ul class="list-unstyled member-list">
                                    <?php 
                                    // Reset pointer for second iteration
                                    $membersResult->data_seek(0);
                                    while($member = $membersResult->fetch_assoc()): 
                                    ?>
                                        <li class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar">
                                                    <?php echo strtoupper(substr($member['username'], 0, 1)); ?>
                                                </div>
                                                <?php echo htmlspecialchars($member['username']); ?>
                                            </div>
                                            <span class="member-role">
                                                <?php echo htmlspecialchars($member['role']); ?>
                                            </span>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h3 class="section-title">Team Projects</h3>
                <?php if ($projectsResult->num_rows > 0): ?>
                    <div class="table-container p-3">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Status</th>
                                    <th>Timeline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $projectsResult->data_seek(0);
                                while($project = $projectsResult->fetch_assoc()): 
                                    $statusClass = match($project['status']) {
                                        'completed' => 'completed',
                                        'verified' => 'verified',
                                        'inprogress' => 'inprogress',
                                        'instudy' => 'instudy',
                                        default => 'pending'
                                    };
                                ?>
                                <tr data-bs-toggle="collapse" data-bs-target="#project<?php echo $project['project_id']; ?>" class="accordion-toggle">
                                    <td>
                                        <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-dot <?php echo $statusClass; ?>"></span>
                                        <?php echo ucfirst($project['status']); ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo date('M d, Y', strtotime($project['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($project['due_date'])); ?>
                                        </small>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="p-0">
                                        <div id="project<?php echo $project['project_id']; ?>" class="collapse p-3 bg-light">
                                            <p class="mb-0 description-text"><?php echo htmlspecialchars($project['description']); ?></p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No projects found for this team.
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <h3 class="section-title">Team Tasks</h3>
                <?php if ($tasksResult->num_rows > 0): ?>
                    <div class="table-container p-3">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $tasksResult->data_seek(0);
                                while($task = $tasksResult->fetch_assoc()): 
                                    $statusClass = match($task['status']) {
                                        'completed' => 'completed',
                                        'inprogress' => 'inprogress',
                                        'pending' => 'pending',
                                        default => 'pending'
                                    };
                                ?>
                                <tr data-bs-toggle="collapse" data-bs-target="#task<?php echo $task['task_id']; ?>" class="accordion-toggle">
                                    <td>
                                        <strong><?php echo htmlspecialchars($task['task_title']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="status-dot <?php echo $statusClass; ?>"></span>
                                        <?php echo ucfirst($task['status']); ?>
                                    </td>
                                    <td>
                                        <small><?php echo $task['assigned_to'] ? htmlspecialchars($task['assigned_to']) : 'Unassigned'; ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($task['due_date'])); ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="p-0">
                                        <div id="task<?php echo $task['task_id']; ?>" class="collapse p-3 bg-light">
                                            <p class="mb-0 description-text"><?php echo htmlspecialchars($task['task_description']); ?></p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No tasks found for this team.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>