<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a Team Lead
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    // header("Location: login.php");
    echo "<script>window.location.href = 'error.php';</script>";
    exit();
}



$user_id = intval($_GET['user_id']);
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get user details
$user_query = "SELECT username FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get tasks for the user with optional status filter
$tasks_query = "SELECT 
    task_id, 
    task_title, 
    task_description, 
    status, 
    points, 
    due_date 
FROM tasks 
WHERE assigned_to = $user_id 
" . (!empty($status) ? " AND status = '$status'" : "") . "
ORDER BY due_date";

$tasks_result = mysqli_query($conn, $tasks_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks for <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --success-color: #22c55e;
            --info-color: #0ea5e9;
            --warning-color: #eab308;
            --danger-color: #ef4444;
            --verified-color: #8b5cf6;
            --light-bg: #f0f2f5;
            --dark-text: #1e293b;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --card-border-radius: 20px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(120deg, #f0f2f5 0%, #e5e7eb 100%);
            min-height: 100vh;
            padding-bottom: 2rem;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path fill="%236366f1" fill-opacity="0.02" d="M45.3,-59.2C62.2,-52.8,81.6,-43.7,89.5,-28.7C97.3,-13.7,93.6,7.2,86.4,26.3C79.2,45.4,68.4,62.8,53.1,72.5C37.8,82.2,18.9,84.3,0.7,83.3C-17.5,82.3,-34.9,78.3,-48.8,68.1C-62.7,57.9,-73,41.5,-77.9,23.5C-82.7,5.5,-82.1,-14.1,-74.9,-30.2C-67.8,-46.2,-54.1,-58.7,-39.2,-66C-24.2,-73.3,-8.1,-75.4,4.4,-81.2C16.9,-87,33.5,-96.6,45.3,-59.2Z" transform="translate(100 100)"/></svg>') no-repeat center center fixed;
            background-size: cover;
            opacity: 0.5;
            z-index: -1;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 2rem 0;
            margin-bottom: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .page-title {
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0;
        }

        .task-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05),
                        0 10px 15px rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.7);
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.2);
        }

        .task-header {
            padding: 1.5rem;
            border-bottom: 2px solid rgba(99, 102, 241, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .task-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-text);
            margin: 0;
        }

        .task-body {
            padding: 1.5rem;
        }

        .task-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #64748b;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-completed { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
        .status-in_progress { background: rgba(14, 165, 233, 0.1); color: #0ea5e9; }
        .status-in_study { background: rgba(234, 179, 8, 0.1); color: #eab308; }
        .status-not_viewed { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .status-verified { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

        .points-badge {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }

        .task-description {
            color: #475569;
            line-height: 1.6;
            margin-top: 1rem;
        }

        .task-footer {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(99, 102, 241, 0.1);
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.8);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .task-card {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .task-card:nth-child(2) { animation-delay: 0.2s; }
        .task-card:nth-child(3) { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-tasks me-3"></i>Task Details for <?php echo htmlspecialchars($user['username']); ?>
                </h1>
                <a href="team_details.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back to Team
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (mysqli_num_rows($tasks_result) == 0): ?>
            <div class="alert alert-info" role="alert">
                No tasks found for this user.
            </div>
        <?php else: ?>
            <div class="row">
                <?php while($task = mysqli_fetch_assoc($tasks_result)) { ?>
                    <div class="task-card">
                        <div class="task-header">
                            <h2 class="task-title"><?php echo htmlspecialchars($task['task_title']); ?></h2>
                            <span class="points-badge">
                                <i class="fas fa-star me-1"></i><?php echo $task['points']; ?> Points
                            </span>
                        </div>
                        <div class="task-body">
                            <div class="task-meta">
                                <div class="meta-item">
                                    <i class="far fa-calendar"></i>
                                    Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                </div>
                                <span class="status-badge status-<?php echo $task['status']; ?>">
                                    <i class="fas fa-circle me-1"></i><?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                </span>
                            </div>
                            <p class="task-description"><?php echo nl2br(htmlspecialchars($task['task_description'])); ?></p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>