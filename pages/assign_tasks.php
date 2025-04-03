<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
}

$team_lead_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$success_message = '';
$error_message = '';

// Fetch available projects for the team
$projects_query = "
    SELECT project_id, title 
    FROM projects 
    WHERE team_id = ? 
    AND status != 'completed' 
    AND status != 'verified'
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($projects_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$projects_result = $stmt->get_result();

$has_projects = $projects_result->num_rows > 0;

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create_task') {
            $task_title = $_POST['task_title'];
            $task_description = $_POST['task_description'];
            $assigned_to = $_POST['assigned_to'];
            $due_date = $_POST['due_date'];
            $points = $_POST['points'];
            $project_id = $_POST['project_id'];

            $insert_query = "
                INSERT INTO tasks (task_title, task_description, assigned_to, assigned_by, due_date, team_id, project_id, points, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'not_viewed')
            ";
            $stmt = $conn->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param("ssiisiii", $task_title, $task_description, $assigned_to, $team_lead_id, $due_date, $team_id, $project_id, $points);
                if ($stmt->execute()) {
                    $task_id = $conn->insert_id;
                    // Record initial status
                    $history_query = "INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, notes) 
                                    VALUES (?, NULL, 'not_viewed', ?, 'Task created')";
                    $hist_stmt = $conn->prepare($history_query);
                    $hist_stmt->bind_param("ii", $task_id, $team_lead_id);
                    $hist_stmt->execute();
                    $success_message = "Task assigned successfully!";
                } else {
                    $error_message = "Failed to assign task: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] == 'verify_task') {
            // ... (keep existing verify task code) addeddddddd
            $task_id = $_POST['task_id'];
            $verify_query = "UPDATE tasks SET status = 'verified' WHERE task_id = ? AND assigned_by = ?";
            $stmt = $conn->prepare($verify_query);
            $stmt->bind_param("ii", $task_id, $team_lead_id);
            if ($stmt->execute()) {
                // Record status change
                $history_query = "INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, notes) 
                                VALUES (?, 'completed', 'verified', ?, 'Task verified by team lead')";
                $hist_stmt = $conn->prepare($history_query);
                $hist_stmt->bind_param("ii", $task_id, $team_lead_id);
                $hist_stmt->execute();
                $success_message = "Task verified successfully!";
            }
        }
    }
}

// Modify tasks query to include project information
$tasks_query = "
    SELECT 
        t.task_id, 
        t.task_title, 
        t.due_date, 
        t.status,
        t.points,
        u.username AS assigned_to_name,
        p.title AS project_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.user_id
    LEFT JOIN projects p ON t.project_id = p.project_id
    WHERE t.assigned_by = ?
    ORDER BY t.created_at DESC
";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("i", $team_lead_id);
$stmt->execute();
$tasks_result = $stmt->get_result();

// Fetch team members (keep existing code)
$team_members_query = "
    SELECT u.user_id, u.username 
    FROM users u
    INNER JOIN team_members tm ON u.user_id = tm.user_id
    WHERE tm.team_id = ?
";
$stmt = $conn->prepare($team_members_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$team_members_result = $stmt->get_result();

// Keep existing getStatusColor function
function getStatusColor($status) {
    // ... (keep existing code) added
    switch ($status) {
        case 'not_viewed':
            return 'secondary';
        case 'in_progress':
            return 'primary';
        case 'completed':
            return 'success';
        case 'verified':
            return 'info';
        default:
            return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Keep existing head content   addded -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Assignment | Team Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --success-color: #0cce6b;
            --warning-color: #ff9e00;
            --danger-color: #e63946;
            --info-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-900: #212529;
            --border-radius: 0.75rem;
            --box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7ff;
            color: var(--gray-900);
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .back-btn {
            color: rgba(255, 255, 255, 0.95);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
        }

        .back-btn:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(-3px);
        }

        .container {
            max-width: 1200px;
            padding: 0 1.5rem;
        }

        .py-4 {
            padding-top: 2rem !important;
            padding-bottom: 2rem !important;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 2rem;
            background-color: white;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem 1.5rem 1rem;
        }

        .card-header h4 {
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            position: relative;
            display: inline-block;
        }

        .card-header h4::after {
            content: '';
            position: absolute;
            width: 50px;
            height: 3px;
            background: var(--accent-color);
            bottom: -8px;
            left: 0;
            border-radius: 10px;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            box-shadow: none;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            transition: all 0.4s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(67, 97, 238, 0.2);
        }

        .btn-success {
            background-color: var(--success-color);
            border: none;
        }

        .btn-success:hover {
            background-color: #0ab15c;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(12, 206, 107, 0.2);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border: none;
            color: #ffffff;
        }

        .btn-warning:hover {
            background-color: #f59400;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 158, 0, 0.2);
        }

        .btn-info {
            background-color: var(--info-color);
            border: none;
            color: #ffffff;
        }

        .btn-info:hover {
            background-color: #30b8df;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76, 201, 240, 0.2);
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            border-spacing: 0;
            border-collapse: separate;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table th {
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--gray-900);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem 1.25rem;
            border-top: none;
            border-bottom: 1px solid var(--gray-200);
        }

        .table td {
            padding: 1.25rem;
            vertical-align: middle;
            border-top: none;
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-600);
            font-size: 0.95rem;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr {
            transition: all 0.3s ease;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }

        .status-badge {
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .alert {
            border: none;
            border-radius: var (--border-radius);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .alert::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 1.25rem;
        }

        .alert-success {
            background-color: rgba(12, 206, 107, 0.1);
            color: var(--success-color);
        }

        .alert-success::before {
            content: '\f058'; /* Check circle icon */
        }

        .alert-danger {
            background-color: rgba(230, 57, 70, 0.1);
            color: var (--danger-color);
        }

        .alert-danger::before {
            content: '\f06a'; /* Exclamation circle icon */
        }

        .alert-warning {
            background-color: rgba(255, 158, 0, 0.1);
            color: var(--warning-color);
        }

        .alert-warning::before {
            content: '\f071'; /* Warning icon */
        }

        .alert-dismissible .btn-close {
            opacity: 0.7;
            transition: all 0.2s;
            padding: 1.25rem;
        }

        .alert-dismissible .btn-close:hover {
            opacity: 1;
        }

        /* Task points styling */
        .points-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Due date styling */
        .due-date {
            display: inline-flex;
            align-items: center;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .due-date i {
            color: var(--accent-color);
            margin-right: 0.5rem;
        }

        /* No projects alert special styling */
        .alert-no-projects {
            background-color: #fff3cd;
            border-left: 4px solid var(--warning-color);
            color: #664d03;
            padding: 1.5rem;
        }

        .alert-no-projects h4 {
            color: #664d03;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .navbar {
            background: linear-gradient(135deg, #3a66db, #2952c8);
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 120px;
            display: flex;
            align-items: center;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .navbar-brand {
            font-size: 1.4rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
    
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-tasks me-2"></i>
                Task Assignment
            </div>
            <a href="teamlead_dashboard.php" class="btn-back">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>
    
    <div class="container py-4">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$has_projects): ?>
            <div class="alert alert-no-projects" role="alert">
                <h4><i class="fas fa-exclamation-triangle me-2"></i>No Active Projects Available</h4>
                <p class="mb-0">There are currently no active projects assigned to your team. Tasks can only be created within projects.</p>
            </div>
        <?php else: ?>
            <!-- Task Assignment Card -->
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-tasks me-2"></i>Assign New Task</h4>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="create_task">
                        
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">Project</label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">-- Select Project --</option>
                                <?php while ($project = $projects_result->fetch_assoc()): ?>
                                    <option value="<?= $project['project_id'] ?>"><?= htmlspecialchars($project['title'] ?? '') ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Keep existing form fields -->
                        <div class="col-md-6">
                            <label for="task_title" class="form-label">Task Title</label>
                            <input type="text" class="form-control" id="task_title" name="task_title" placeholder="Enter task title" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="points" class="form-label">Task Points (1-20)</label>
                            <input type="number" class="form-control" id="points" name="points" min="1" max="20" placeholder="Assign points" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="task_description" class="form-label">Task Description</label>
                            <textarea class="form-control" id="task_description" name="task_description" rows="3" placeholder="Describe the task requirements" required></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to" required>
                                <option value="">-- Select Team Member --</option>
                                <?php while ($member = $team_members_result->fetch_assoc()): ?>
                                    <option value="<?= $member['user_id'] ?>"><?= htmlspecialchars($member['username']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                        
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Assign Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tasks List Card -->
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-clipboard-list me-2"></i>Assigned Tasks</h4>
            </div>
            <div class="card-body">
                <?php if ($tasks_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Task ID</th>
                                <th>Project</th>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Points</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($task = $tasks_result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="text-muted">#<?= $task['task_id'] ?></span></td>
                                    <td><?= htmlspecialchars($task['project_name'] ?? '') ?: 'No Project' ?></td>
                                    <td><strong><?= htmlspecialchars($task['task_title'] ?? '') ?></strong></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar-icon me-2 bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <?= htmlspecialchars($task['assigned_to_name'] ?? '') ?: 'Unassigned' ?>
                                        </div>
                                    </td>
                                    <td><span class="points-badge"><?= $task['points'] ?></span></td>
                                    <td>
                                        <span class="due-date">
                                            <i class="far fa-calendar-alt"></i>
                                            <?= $task['due_date'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $statusClass = getStatusColor($task['status']);
                                            $statusIcon = '';
                                            switch($task['status']) {
                                                case 'not_viewed':
                                                    $statusIcon = '<i class="far fa-eye-slash me-1"></i>';
                                                    break;
                                                case 'in_progress':
                                                    $statusIcon = '<i class="fas fa-spinner me-1"></i>';
                                                    break;
                                                case 'completed':
                                                    $statusIcon = '<i class="fas fa-check-circle me-1"></i>';
                                                    break;
                                                case 'verified':
                                                    $statusIcon = '<i class="fas fa-award me-1"></i>';
                                                    break;
                                            }
                                        ?>
                                        <span class="status-badge bg-<?= $statusClass ?> bg-opacity-10 text-<?= $statusClass ?>">
                                            <?= $statusIcon ?><?= str_replace('_', ' ', ucfirst($task['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <?php if ($task['status'] == 'completed'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        onclick="verifyTask(<?= $task['task_id'] ?>)">
                                                    <i class="fas fa-check me-1"></i>Verify
                                                </button>
                                            <?php endif; ?>
                                            
                                            <a href="view_task_history.php?task_id=<?= $task['task_id'] ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            
                                            <?php if ($task['status'] == 'not_viewed'): ?>
                                                <a href="edit_task.php?task_id=<?= $task['task_id'] ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit me-1"></i>Edit
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-tasks text-muted mb-3" style="font-size: 3rem; opacity: 0.2;"></i>
                    <h5 class="text-muted">No tasks assigned yet</h5>
                    <p class="text-muted">Create your first task using the form above</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Keep existing hidden form and scripts  added-->
        <!-- Verification Form (Hidden) -->
    <form id="verifyForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="verify_task">
        <input type="hidden" name="task_id" id="verify_task_id">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize date input min value
    window.addEventListener('load', function() {
        const dateInput = document.getElementById('due_date');
        const today = new Date();
        today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
        dateInput.min = today.toISOString().split('T')[0];
    });

    function verifyTask(taskId) {
        if (confirm('Are you sure you want to verify this task?')) {
            document.getElementById('verify_task_id').value = taskId;
            document.getElementById('verifyForm').submit();
        }
    }
    </script>
</body>
</html>