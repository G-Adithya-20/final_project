<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'TeamMember') {
    echo "<script>window.location.href = 'error.php';</script>";
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['new_status'];
    $notes = $_POST['notes'];

    // Get current status
    $curr_status_query = "SELECT status FROM tasks WHERE task_id = ? AND assigned_to = ?";
    $stmt = $conn->prepare($curr_status_query);
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_task = $result->fetch_assoc();

    if ($current_task) {
        $old_status = $current_task['status'];
        
        // Validate status transition
        $valid_transition = false;
        switch ($old_status) {
            case 'not_viewed':
                $valid_transition = ($new_status == 'in_study');
                break;
            case 'in_study':
                $valid_transition = ($new_status == 'in_progress');
                break;
            case 'in_progress':
                $valid_transition = ($new_status == 'completed');
                break;
            default:
                $valid_transition = false;
        }

        if ($valid_transition) {
            // Update task status
            $update_query = "UPDATE tasks SET status = ? WHERE task_id = ? AND assigned_to = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sii", $new_status, $task_id, $user_id);
            
            if ($stmt->execute()) {
                // Record status change in history
                $history_query = "INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, notes) 
                                VALUES (?, ?, ?, ?, ?)";
                $hist_stmt = $conn->prepare($history_query);
                $hist_stmt->bind_param("issss", $task_id, $old_status, $new_status, $user_id, $notes);
                $hist_stmt->execute();
                $success_message = "Task status updated successfully!";
            } else {
                $error_message = "Failed to update task status.";
            }
        } else {
            $error_message = "Invalid status transition.";
        }
    }
}

// Fetch assigned tasks with project name
$tasks_query = "
    SELECT 
        t.task_id,
        t.task_title,
        t.task_description,
        t.due_date,
        t.status,
        t.points,
        u.username AS assigned_by_name,
        p.title AS project_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_by = u.user_id
    LEFT JOIN projects p ON t.project_id = p.project_id
    WHERE t.assigned_to = ?
    ORDER BY 
        CASE 
            WHEN t.status = 'not_viewed' THEN 1
            WHEN t.status = 'in_study' THEN 2
            WHEN t.status = 'in_progress' THEN 3
            WHEN t.status = 'completed' THEN 4
            WHEN t.status = 'verified' THEN 5
        END,
        t.due_date ASC
";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | Team Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #560bad;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 10px 25px rgba(67, 97, 238, 0.1);
            --text-muted: #6c757d;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f9fc;
            padding-top: 100px;  /* Reduced from 120px */
            color: #444;
            line-height: 1.7;
        }

        /* Navbar styling */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
        }

        .back-button {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
        }

        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            transform: translateX(-3px);
        }

        /* Page header styling */
        .page-header {
            background: #ffffff;
            padding: 2rem 0;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03);
        }

        .page-header h1 {
            font-weight: 600;
            color: var(--dark-color);
            position: relative;
            padding-left: 15px;
        }

        .page-header h1:before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 70%;
            width: 5px;
            background: var(--primary-color);
            border-radius: 3px;
        }

        /* Card styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        .card-title {
            color: var(--dark-color);
            font-weight: 600;
            margin-right: 10px;
        }

        .card-body {
            padding: 1.5rem;
            background-color: #ffffff;
        }

        /* Status badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .not-viewed { 
            background-color: #dee2e6; 
            color: #495057; 
        }
        
        .in-study { 
            background: linear-gradient(135deg, #f8961e, #f3722c); 
            color: white; 
        }
        
        .in-progress { 
            background: linear-gradient(135deg, #4361ee, #3a0ca3); 
            color: white; 
        }
        
        .completed { 
            background: linear-gradient(135deg, #4cc9f0, #4895ef); 
            color: white; 
        }
        
        .verified { 
            background: linear-gradient(135deg, #34d399, #10b981); 
            color: white; 
        }

        /* Points badge */
        .points-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 0.5rem 1rem;
            border-radius: 50px;
            color: white;
            font-weight: 500;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .points-badge i {
            color: #ffdd00;
        }

        /* Form elements */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-info {
            background: linear-gradient(135deg, var(--info-color), #480ca8);
            color: white;
        }

        .btn-info:hover {
            background: linear-gradient(135deg, #480ca8, var(--info-color));
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        /* Task metadata */
        .task-meta {
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            height: 100%;
        }

        .task-meta i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .task-meta strong {
            margin-right: 5px;
            color: #495057;
        }

        /* Alert messages */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Form controls */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        /* Description area */
        .task-description {
            background-color: #f8f9fa;
            padding: 1.25rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            margin-bottom: 1.5rem;
        }

        /* Empty state */
        .no-tasks {
            text-align: center;
            padding: 3rem 0;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
        }

        .no-tasks i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .no-tasks h3 {
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --background: #f8f9fc;
        }

        /* Updated Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, #3a66db, #2952c8);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 100px;  /* Reduced from 120px */
            display: flex;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
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
                My Tasks
            </div>
            <a href="teammember_dashboard.php" class="btn-back">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">My Tasks</h1>
                <div class="header-actions">
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        <i class="fas fa-clipboard-list me-1"></i>
                        <?= $tasks_result->num_rows ?> Tasks
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= $success_message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Task List -->
        <div class="task-list">
            <?php if ($tasks_result->num_rows == 0): ?>
                <div class="no-tasks">
                    <i class="far fa-clipboard"></i>
                    <h3>No tasks assigned yet</h3>
                    <p class="text-muted">Check back later for new assignments</p>
                </div>
            <?php endif; ?>
            
            <?php while ($task = $tasks_result->fetch_assoc()): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($task['task_title']) ?></h5>
                            <span class="status-badge <?= str_replace('_', '-', $task['status']) ?> ms-3">
                                <?= str_replace('_', ' ', ucfirst($task['status'])) ?>
                            </span>
                        </div>
                        <span class="points-badge">
                            <i class="fas fa-star"></i>
                            <?= $task['points'] ?> Points
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="task-description">
                            <?= nl2br(htmlspecialchars($task['task_description'])) ?>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="task-meta">
                                    <i class="far fa-calendar"></i>
                                    <div>
                                        <strong>Due Date:</strong><br>
                                        <?= date('M d, Y', strtotime($task['due_date'])) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="task-meta">
                                    <i class="far fa-user"></i>
                                    <div>
                                        <strong>Assigned By:</strong><br>
                                        <?= htmlspecialchars($task['assigned_by_name']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="task-meta">
                                    <i class="fas fa-project-diagram"></i>
                                    <div>
                                        <strong>Project:</strong><br>
                                        <?= htmlspecialchars($task['project_name'] ?? 'N/A') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="task-meta">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong>Progress:</strong><br>
                                        <?php
                                            $progress = 0;
                                            switch($task['status']) {
                                                case 'not_viewed': $progress = 0; break;
                                                case 'in_study': $progress = 30; break;
                                                case 'in_progress': $progress = 60; break;
                                                case 'completed': $progress = 90; break;
                                                case 'verified': $progress = 100; break;
                                            }
                                        ?>
                                        <div class="progress mt-1" style="height: 8px;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progress ?>%" 
                                                aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($task['status'] != 'verified'): ?>
                            <form method="POST" class="mb-4 bg-light p-4 rounded-3">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                
                                <h6 class="mb-3 fw-bold"><i class="fas fa-edit me-2"></i>Update Task Status</h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="new_status_<?= $task['task_id'] ?>" class="form-label">New Status</label>
                                        <select class="form-select" id="new_status_<?= $task['task_id'] ?>" name="new_status" required>
                                            <option value="">Select new status</option>
                                            <?php if ($task['status'] == 'not_viewed'): ?>
                                                <option value="in_study">In Study</option>
                                            <?php elseif ($task['status'] == 'in_study'): ?>
                                                <option value="in_progress">In Progress</option>
                                            <?php elseif ($task['status'] == 'in_progress'): ?>
                                                <option value="completed">Completed</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notes_<?= $task['task_id'] ?>" class="form-label">Status Update Notes</label>
                                        <textarea class="form-control" id="notes_<?= $task['task_id'] ?>" name="notes" rows="2" placeholder="Add your notes here..." required></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">
                                    <i class="fas fa-check me-2"></i>Update Status
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-end">
                        <a href="view_task_history.php?task_id=<?= $task['task_id'] ?>" class="btn btn-info">
                                <i class="fas fa-history me-2"></i>View History
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <footer class="bg-white mt-5 py-4 border-top">
        <div class="container text-center">
            <p class="text-muted mb-0">© <?= date('Y') ?> Team Workspace | All Rights Reserved</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>