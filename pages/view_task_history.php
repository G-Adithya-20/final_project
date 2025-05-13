<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: error.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Validate task_id parameter
if (!isset($_GET['task_id']) || !is_numeric($_GET['task_id'])) {
    header("Location: " . ($user_role === 'TeamLead' ? 'assign_tasks.php' : 'teammember_dashboard.php'));
    exit;
}

$task_id = (int)$_GET['task_id'];

// Verify task assignment based on user role
if ($user_role === 'TeamLead') {
    // TeamLead can only view tasks they assigned
    $task_query = "SELECT t.*, u.username AS assigned_to_name, p.title AS project_name 
                  FROM tasks t
                  LEFT JOIN users u ON t.assigned_to = u.user_id
                  LEFT JOIN projects p ON t.project_id = p.project_id
                  WHERE t.task_id = ? AND t.assigned_by = ?";
    $stmt = $conn->prepare($task_query);
    $stmt->bind_param("ii", $task_id, $user_id);
} elseif ($user_role === 'TeamMember') {
    // TeamMember can only view tasks assigned to them
    $task_query = "SELECT t.*, u.username AS assigned_to_name, p.title AS project_name 
                  FROM tasks t
                  LEFT JOIN users u ON t.assigned_to = u.user_id
                  LEFT JOIN projects p ON t.project_id = p.project_id
                  WHERE t.task_id = ? AND t.assigned_to = ?";
    $stmt = $conn->prepare($task_query);
    $stmt->bind_param("ii", $task_id, $user_id);
} else {
    // Invalid role
    header("Location: error.php");
    exit;
}

$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header("Location: " . ($user_role === 'TeamLead' ? 'assign_tasks.php' : 'teammember_dashboard.php'));
    exit;
}

// Fetch task history (accessible only if user has permission to view the task)
$history_query = "SELECT h.*, u.username AS changed_by_name
                 FROM task_status_history h
                 LEFT JOIN users u ON h.changed_by = u.user_id
                 WHERE h.task_id = ?
                 ORDER BY h.changed_at DESC";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$history_result = $stmt->get_result();

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'not_viewed': return 'secondary';
        case 'in_progress': return 'primary';
        case 'completed': return 'success';
        case 'verified': return 'info';
        default: return 'secondary';
    }
}

function getStatusIcon($status) {
    switch ($status) {
        case 'not_viewed': return 'fa-circle-exclamation';
        case 'in_progress': return 'fa-spinner fa-spin';
        case 'completed': return 'fa-check-circle';
        case 'verified': return 'fa-badge-check';
        default: return 'fa-circle';
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a49dd;
            --primary-light: #ebefff;
            --secondary: #6c757d;
            --accent: #4cc9f0;
            --success: #10b981;
            --info: #0ea5e9;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f6f9ff 0%, #f3f6ff 100%);
            color: var(--gray-700);
            line-height: 1.6;
            min-height: 100vh;
            padding-bottom: 3rem;
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c5d0e6;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a3b1cc;
        }

        /* Navbar - Modern glassmorphism style */
        .navbar {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.9), rgba(58, 66, 221, 0.9));
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 100px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand i {
            background: rgba(255, 255, 255, 0.2);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
            transition: var(--transition);
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 10px 25px rgba(67, 97, 238, 0.25);
        }

        /* Card Styles - Modern and clean */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2.5rem;
            overflow: hidden;
            background: white;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--gray-200);
            padding: 1.75rem 2rem;
        }

        .card-body {
            padding: 2rem;
        }

        .card-title {
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--primary);
            background: var(--primary-light);
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        /* Task Info Panel */
        .task-info {
            padding: 2rem;
            background: #f8f9fe;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .task-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 8px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--accent));
            border-radius: 4px 0 0 4px;
        }

        .info-item {
            margin-bottom: 1.75rem;
            position: relative;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray-500);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: var(--gray-800);
            font-weight: 600;
            font-size: 1.1rem;
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
        }

        .info-value i {
            margin-right: 0.75rem;
            color: var(--primary);
            font-size: 1.2rem;
        }

        /* Timeline Styles - Enhanced with animations */
        .section-title {
            color: var(--gray-800);
            font-weight: 700;
            margin-bottom: 1.75rem;
            padding-left: 1rem;
            border-left: 4px solid var(--primary);
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary);
        }

        .timeline {
            position: relative;
            padding: 1rem 0 0 1rem;
            margin-left: 1rem;
        }

        .timeline-item {
            position: relative;
            padding-left: 2.5rem;
            padding-bottom: 2.5rem;
            border-left: 2px solid rgba(67, 97, 238, 0.2);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .timeline-item:last-child {
            border-left: 2px solid transparent;
            padding-bottom: 0.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.75rem;
            top: 0;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--primary);
            box-shadow: 0 0 0 5px rgba(67, 97, 238, 0.15);
            z-index: 2;
            transition: var(--transition);
        }

        .timeline-item:hover::before {
            transform: scale(1.1);
            box-shadow: 0 0 0 8px rgba(67, 97, 238, 0.2);
        }

        .timeline-content {
            background: white;
            padding: 1.75rem;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }

        .timeline-content:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(67, 97, 238, 0.1);
        }

        /* Status Badges - Enhanced with icons */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .status-badge i {
            font-size: 0.875rem;
        }

        .timestamp {
            font-size: 0.875rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .timestamp i {
            color: var(--gray-400);
        }

        .changed-by {
            font-weight: 600;
            color: var(--gray-700);
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
        }

        .changed-by i {
            color: var(--primary);
            margin-right: 0.75rem;
            background: var(--primary-light);
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 0.875rem;
        }

        .notes-content {
            color: var(--gray-600);
            font-size: 0.95rem;
            line-height: 1.6;
            padding-top: 0.75rem;
            border-top: 1px solid var(--gray-200);
            margin-top: 0.75rem;
            position: relative;
        }

        .notes-content::before {
            content: '"';
            font-size: 2rem;
            color: var(--gray-300);
            position: absolute;
            top: 0.5rem;
            left: 0;
            line-height: 0;
            font-family: serif;
        }

        /* Status Colors with better contrast */
        .bg-secondary-soft {
            background-color: rgba(108, 117, 125, 0.15);
            color: var(--secondary);
        }

        .bg-primary-soft {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        .bg-success-soft {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }

        .bg-info-soft {
            background-color: rgba(14, 165, 233, 0.15);
            color: var(--info);
        }

        /* Container layout */
        .container {
            max-width: 1000px;
            padding: 0 1.5rem;
        }

        /* Empty state handling */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-400);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                height: auto;
                padding: 1rem 0;
            }
            
            .navbar-brand {
                font-size: 1.3rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .task-info {
                padding: 1.5rem;
            }
            
            .timeline {
                padding-left: 0;
                margin-left: 0;
            }
            
            .timeline-item {
                padding-left: 2rem;
            }
            
            .info-value {
                font-size: 1rem;
            }
        }

        /* Animations */
        .card, .task-info, .btn-back {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Hover effects */
        .info-item:hover .info-value {
            color: var(--primary);
            transform: translateX(3px);
            transition: var(--transition);
        }
    </style>
</head>
<body>
    
     <!-- Navigation Bar -->
     <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-history"></i>
                Task History
            </div>
            <a href="<?= $user_role === 'TeamLead' ? 'assign_tasks.php' : 'teammember_dashboard.php' ?>" class="btn-back">
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
                                <div class="info-value">
                                    <i class="fas fa-tasks"></i>
                                    <?= htmlspecialchars($task['task_title'] ?? '') ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Project</div>
                                <div class="info-value">
                                    <i class="fas fa-project-diagram"></i>
                                    <?= htmlspecialchars($task['project_name'] ?? '') ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Assigned To</div>
                                <div class="info-value">
                                    <i class="fas fa-user-circle"></i>
                                    <?= htmlspecialchars($task['assigned_to_name'] ?? '') ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-label">Current Status</div>
                                <div class="info-value">
                                    <span class="status-badge bg-<?= getStatusBadgeClass($task['status']) ?>-soft">
                                        <i class="fas <?= getStatusIcon($task['status']) ?>"></i>
                                        <?= ucfirst(str_replace('_', ' ', $task['status'] ?? '')) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status History Timeline -->
                <h6 class="section-title"><i class="fas fa-history"></i>Status History</h6>
                
                <?php if ($history_result->num_rows > 0): ?>
                <div class="timeline">
                    <?php while ($history = $history_result->fetch_assoc()): ?>
                        <div class="timeline-item">
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="status-badge bg-<?= getStatusBadgeClass($history['new_status']) ?>-soft">
                                        <i class="fas <?= getStatusIcon($history['new_status']) ?>"></i>
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
                                    <div class="notes-content">
                                        <?= htmlspecialchars($history['notes'] ?? '') ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-calendar-times"></i>
                    <p>No status history available for this task.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>