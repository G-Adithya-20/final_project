<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is a TeamLead
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
    exit;
}

// Validate task_id parameter
if (!isset($_GET['task_id'])) {
    echo "<script>window.location.href = 'assign_tasks.php';</script>";
    exit;
}

$task_id = (int)$_GET['task_id'];
$team_lead_id = $_SESSION['user_id'];

// Fetch task details
$task_query = "
    SELECT t.*, u.username AS assigned_to_name, p.title AS project_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.user_id
    LEFT JOIN projects p ON t.project_id = p.project_id
    WHERE t.task_id = ? AND t.assigned_by = ?
";
$stmt = $conn->prepare($task_query);
$stmt->bind_param("ii", $task_id, $team_lead_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    echo "<script>window.location.href = 'assign_tasks.php';</script>";
    exit;
}

// Fetch task history
$history_query = "
    SELECT 
        h.*,
        u.username AS changed_by_name
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task History | <?= htmlspecialchars($task['task_title'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --light: #f8fafc;
            --dark: #1e293b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f8;
            color: var(--gray-800);
            line-height: 1.6;
        }

        /* Navbar - Updated to match app theme */
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

        /* Card Styles */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06);
            margin-bottom: 2.5rem;
            overflow: hidden;
            background: white;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 1.5rem 2rem;
        }

        .card-body {
            padding: 2rem;
        }

        .card-title {
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--primary);
        }

        .task-info {
            padding: 1.75rem;
            background: #f8f9fe;
            border-radius: 12px;
            margin-bottom: 2rem;
            border: 1px solid var(--gray-200);
        }

        .info-item {
            margin-bottom: 1.5rem;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: var(--gray-800);
            font-weight: 500;
            font-size: 1.05rem;
        }

        /* Timeline Styles */
        h6 {
            color: var(--gray-700);
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-left: 0.5rem;
            border-left: 3px solid var(--primary);
            font-size: 1.125rem;
        }

        .timeline {
            position: relative;
            padding: 0.5rem 0 0 1rem;
            margin-left: 1rem;
        }

        .timeline-item {
            position: relative;
            padding-left: 2.5rem;
            padding-bottom: 2.5rem;
            border-left: 2px solid rgba(67, 97, 238, 0.2);
        }

        .timeline-item:last-child {
            border-left: 2px solid transparent;
            padding-bottom: 0.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.625rem;
            top: 0;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            z-index: 2;
        }

        .timeline-content {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .timeline-content:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }

        .timestamp {
            font-size: 0.875rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .changed-by {
            font-weight: 600;
            color: var(--gray-700);
            margin-top: 0.75rem;
        }

        .changed-by i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .text-muted {
            color: var(--gray-600) !important;
            font-size: 0.95rem;
            line-height: 1.6;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-200);
            margin-top: 0.75rem;
        }

        /* Status Colors */
        .bg-secondary-soft {
            background-color: rgba(108, 117, 125, 0.15);
            color: #6c757d;
        }

        .bg-primary-soft {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        .bg-success-soft {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--success);
        }

        .bg-info-soft {
            background-color: rgba(13, 202, 240, 0.15);
            color: #0dcaf0;
        }

        /* Container layout */
        .container {
            max-width: 1000px;
            padding: 0 1rem;
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .task-info {
                padding: 1.25rem;
            }
            
            .timeline {
                padding-left: 0;
                margin-left: 0;
            }
            
            .timeline-item {
                padding-left: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-history me-2"></i>
                Task History
            </div>
            <a href="assign_tasks.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Tasks
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- Task Details Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="fas fa-clipboard-list"></i>
                    Task Details
                </h5>
            </div>
            <div class="card-body">
                <div class="task-info">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Task Title</div>
                                <div class="info-value"><?= htmlspecialchars($task['task_title'] ?? '') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Project</div>
                                <div class="info-value"><?= htmlspecialchars($task['project_name'] ?? '') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Assigned To</div>
                                <div class="info-value">
                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                    <?= htmlspecialchars($task['assigned_to_name'] ?? '') ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Current Status</div>
                                <div class="info-value">
                                    <span class="status-badge bg-<?= getStatusBadgeClass($task['status']) ?>-soft">
                                        <?= ucfirst(str_replace('_', ' ', $task['status'] ?? '')) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status History Timeline -->
                <h6><i class="fas fa-history me-2"></i>Status History</h6>
                <div class="timeline">
                    <?php while ($history = $history_result->fetch_assoc()): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="status-badge bg-<?= getStatusBadgeClass($history['new_status']) ?>-soft">
                                        <?= ucfirst(str_replace('_', ' ', $history['new_status'] ?? '')) ?>
                                    </span>
                                    <span class="timestamp">
                                        <i class="far fa-clock"></i>
                                        <?= date('M d, Y g:i A', strtotime($history['changed_at'] ?? '')) ?>
                                    </span>
                                </div>
                                <div class="changed-by">
                                    <i class="fas fa-user-edit"></i>
                                    <?= htmlspecialchars($history['changed_by_name'] ?? '') ?>
                                </div>
                                <?php if ($history['notes']): ?>
                                    <div class="mt-2 text-muted">
                                        <?= htmlspecialchars($history['notes'] ?? '') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>