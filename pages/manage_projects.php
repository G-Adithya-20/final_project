<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    echo "<script>window.location.href = 'login.php';</script>";
}

$message = '';
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'table';

// Handle project verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_project'])) {
    $project_id = (int)$_POST['project_id'];
    
    // Check if project exists and is in completed status
    $check_stmt = $conn->prepare("SELECT status FROM projects WHERE project_id = ?");
    $check_stmt->bind_param("i", $project_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $project_status = $result->fetch_assoc();
    $check_stmt->close();

    if ($project_status && $project_status['status'] === 'completed') {
        $stmt = $conn->prepare("UPDATE projects SET status = 'verified', actual_end_date = CURDATE() WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        
        if ($stmt->execute()) {
            $message = "Project verified successfully!";
        } else {
            $message = "Error verifying project: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Project must be in 'completed' status to verify.";
    }
}

// Handle extension requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond_extension'])) {
    $extension_id = (int)$_POST['extension_id'];
    $response_type = $_POST['response_type'];
    $response_note = sanitizeInput($_POST['response_note']);
    
    if ($response_type !== 'approved' && $response_type !== 'rejected') {
        $message = "Invalid response type";
    } else {
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("
                UPDATE project_extensions 
                SET status = ?, response_note = ?, responded_at = CURRENT_TIMESTAMP 
                WHERE extension_id = ? AND status = 'pending'");
            $stmt->bind_param("ssi", $response_type, $response_note, $extension_id);
            
            if ($stmt->execute()) {
                if ($response_type === 'approved') {
                    $stmt2 = $conn->prepare("
                        UPDATE projects p
                        JOIN project_extensions pe ON p.project_id = pe.project_id
                        SET p.due_date = pe.new_due_date
                        WHERE pe.extension_id = ?");
                    $stmt2->bind_param("i", $extension_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
                
                $conn->commit();
                $message = "Extension request " . $response_type . " successfully!";
            } else {
                throw new Exception("Error updating extension request");
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error processing extension request: " . $e->getMessage();
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

function sanitizeInput($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
}

function truncateDescription($description, $length = 100) {
    return strlen($description) > $length 
        ? substr($description, 0, $length) . '...' 
        : $description;
}

// Fetch all projects with team and extension request details
$query = "
    SELECT 
        p.*,
        t.team_name,
        u.username as team_lead,
        pe.extension_id,
        pe.new_due_date as requested_due_date,
        pe.reason as extension_reason,
        pe.status as extension_status,
        DATEDIFF(p.due_date, CURDATE()) as days_remaining
    FROM projects p
    JOIN teams t ON p.team_id = t.team_id
    JOIN users u ON t.team_lead_id = u.user_id
    LEFT JOIN project_extensions pe ON p.project_id = pe.project_id 
        AND pe.status = 'pending'
    ORDER BY 
        CASE p.status
            WHEN 'completed' THEN 1
            WHEN 'inprogress' THEN 2
            WHEN 'instudy' THEN 3
            WHEN 'notviewed' THEN 4
            ELSE 5
        END,
        p.due_date ASC";

$result = $conn->query($query);

if (!$result) {
    die("Error fetching projects: " . $conn->error);
}

// Status mapping for styling
$statusMap = [
    'completed' => ['class' => 'success', 'icon' => 'check-circle'],
    'verified' => ['class' => 'primary', 'icon' => 'award'],
    'inprogress' => ['class' => 'warning', 'icon' => 'clock'],
    'instudy' => ['class' => 'info', 'icon' => 'book'],
    'notviewed' => ['class' => 'secondary', 'icon' => 'eye-slash']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management Dashboard</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #edf2fb;
            --primary-dark: #3a56d4;
            --secondary-color: #2b2d42;
            --accent-color: #48cae4;
            --success-color: #06d6a0;
            --warning-color: #ffd166;
            --danger-color: #ef476f;
            --info-color: #90e0ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --background-color: #f7f7f9;
            --card-bg: #ffffff;
            --border-radius: 12px;
            --box-shadow: 0 8px 16px rgba(0,0,0,0.05);
            --hover-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--secondary-color);
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: white;
        }

        .navbar-brand i {
            color: var(--accent-color);
        }

        .nav-link {
            color: rgba(255,255,255,0.85) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .nav-link:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.1);
        }

        .btn-outline-light {
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .page-title {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0;
        }

        .project-card {
            background-color: var(--card-bg);
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .project-card .card-header {
            background-color: var(--primary-light);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem;
        }

        .project-card .card-body {
            padding: 1.5rem;
        }

        .project-card .card-footer {
            background-color: transparent;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .project-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-item i {
            color: var(--primary-color);
            font-size: 0.9rem;
            width: 16px;
            text-align: center;
        }

        .meta-item .label {
            font-size: 0.75rem;
            color: #6c757d;
            min-width: 80px;
        }

        .meta-item .value {
            font-weight: 500;
            font-size: 0.875rem;
        }

        .table-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }

        .custom-table {
            margin-bottom: 0;
        }

        .custom-table th {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            font-weight: 500;
            padding: 1rem;
            font-size: 0.9rem;
            border: none;
        }

        .custom-table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: rgba(0,0,0,0.05);
        }

        .custom-table tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }

        .btn {
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-sm {
            padding: 0.25rem 0.7rem;
            font-size: 0.8rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
        }

        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #05c090;
            border-color: #05c090;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(6, 214, 160, 0.2);
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: var(--dark-color);
        }

        .btn-warning:hover {
            background-color: #ffc656;
            border-color: #ffc656;
            color: var(--dark-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 209, 102, 0.2);
        }

        .view-toggle-btn {
            background: linear-gradient(135deg, var(--accent-color), #56cfe1);
            color: white;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(72, 202, 228, 0.2);
        }

        .view-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(72, 202, 228, 0.3);
        }

        .new-project-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.2);
        }

        .new-project-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.3);
        }

        .extension-pending {
            background-color: #fff8e6;
            border-left: 4px solid var(--warning-color);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .extension-pending .title {
            color: #9a6700;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .extension-pending .title i {
            color: var(--warning-color);
        }

        .extension-pending .details {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }

        .extension-pending .form-control {
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            padding: 0.5rem;
            font-size: 0.85rem;
        }

        .extension-pending .form-select {
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.1);
            padding: 0.5rem;
            font-size: 0.85rem;
        }

        .badge {
            font-weight: 500;
            padding: 0.35rem 0.75rem;
        }

        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-warning {
            background-color: var(--warning-color) !important;
            color: var(--dark-color);
        }

        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }

        .badge.bg-info {
            background-color: var(--info-color) !important;
            color: var(--dark-color);
        }

        .badge.bg-secondary {
            background-color: var(--secondary-color) !important;
        }

        .overdue-alert {
            color: var(--danger-color);
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.25rem;
        }

        .alert {
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(6, 214, 160, 0.1);
            border-color: rgba(6, 214, 160, 0.2);
            color: #05a882;
        }

        .alert-danger {
            background-color: rgba(239, 71, 111, 0.1);
            border-color: rgba(239, 71, 111, 0.2);
            color: #e62c59;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .timeline-icon {
            background-color: var(--primary-light);
            color: var(--primary-color);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            font-size: 0.8rem;
        }

        .timeline-content {
            flex: 1;
            line-height: 1.4;
        }

        .timeline-date {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .project-description {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .project-progress {
            margin: 1rem 0;
        }

        .progress {
            height: 8px;
            border-radius: 50px;
            overflow: hidden;
            background-color: #e9ecef;
        }

        .progress-bar {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }

        .stats-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stats-content {
            flex: 1;
        }

        .stats-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .stats-label {
            font-size: 0.85rem;
            color: #6c757d;
        }

        @media (max-width: 992px) {
            .navbar-brand {
                font-size: 1.25rem;
            }
            .page-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .project-card {
                margin-bottom: 1rem;
            }
            .btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
            .custom-table {
                white-space: nowrap;
            }
            .navbar-brand {
                font-size: 1.1rem;
            }
            .page-title {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 576px) {
            .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .btn {
                padding: 0.35rem 0.7rem;
                font-size: 0.85rem;
            }
            .navbar-brand {
                font-size: 1rem;
            }
            .page-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-project-diagram me-2"></i>
                Project Management
            </a>
            <div class="d-flex">
                <a href="manager_dashboard.php" class="btn btn-outline-light">
                    <i class="fas fa-home me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h1 class="page-title">Project Dashboard</h1>
            <div class="d-flex gap-2">
                <a href="?view=<?php echo $view_mode === 'table' ? 'grid' : 'table'; ?>" 
                   class="view-toggle-btn">
                    <i class="fas fa-<?php echo $view_mode === 'table' ? 'th' : 'list'; ?> me-2"></i>
                    <?php echo $view_mode === 'table' ? 'Grid View' : 'Table View'; ?>
                </a>
                <a href="assign_project.php" class="new-project-btn">
                    <i class="fas fa-plus me-2"></i>New Project
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo strpos($message, 'Error') !== false ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($view_mode === 'grid'): ?>
            <div class="row g-4">
                <?php while ($project = $result->fetch_assoc()): ?>
                    <?php 
                    $status = $project['status'];
                    $statusInfo = $statusMap[$status] ?? ['class' => 'secondary', 'icon' => 'question-circle'];
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="project-card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-start">
                                    <h5 class="mb-0 fw-bold" style="max-width: 80%; word-wrap: break-word;">
                                        <?php echo htmlspecialchars($project['title']); ?>
                                    </h5>
                                    <span class="status-badge bg-<?php echo $statusInfo['class']; ?>">
                                        <i class="fas fa-<?php echo $statusInfo['icon']; ?>"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="project-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span class="label">Started:</span>
                                        <span class="value"><?php echo date('M d, Y', strtotime($project['start_date'])); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span class="label">Due:</span>
                                        <span class="value"><?php echo date('M d, Y', strtotime($project['due_date'])); ?></span>
                                    </div>
                                    <?php if ($project['actual_end_date']): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-flag-checkered"></i>
                                        <span class="label">Completed:</span>
                                        <span class="value"><?php echo date('M d, Y', strtotime($project['actual_end_date'])); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="project-progress">
                                    <?php 
                                    $progress = match($status) {
                                        'verified' => 100,
                                        'completed' => 90,
                                        'inprogress' => 60,
                                        'instudy' => 30,
                                        default => 10
                                    };
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-muted small">Progress</span>
                                        <span class="text-muted small"><?php echo $progress; ?>%</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo $progress; ?>%" role="progressbar" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div>
                                        <span class="d-block text-muted small">Team</span>
                                        <span class="fw-medium"><?php echo htmlspecialchars($project['team_name']); ?></span>
                                    </div>
                                    <div>
                                        <span class="d-block text-muted small">Team Lead</span>
                                        <span class="fw-medium"><?php echo htmlspecialchars($project['team_lead']); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($project['extension_id']): ?>
                                    <div class="extension-pending">
                                        <div class="title">
                                            <i class="fas fa-exclamation-circle"></i>
                                            Extension Requested
                                        </div>
                                        <div class="details">
                                            <div><strong>New Due Date:</strong> <?php echo date('M d, Y', strtotime($project['requested_due_date'])); ?></div>
                                            <div><strong>Reason:</strong> <?php echo htmlspecialchars($project['extension_reason']); ?></div>
                                        </div>
                                        <form method="POST">
                                            <input type="hidden" name="extension_id" value="<?php echo $project['extension_id']; ?>">
                                            <div class="mb-2">
                                                <textarea name="response_note" class="form-control" placeholder="Your response..." required></textarea>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <select name="response_type" class="form-select" required>
                                                    <option value="">Select Response</option>
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                                <button type="submit" name="respond_extension" class="btn btn-primary">
                                                    Submit
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex gap-2 justify-content-center">
                                    <?php if ($project['status'] === 'completed'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                            <button type="submit" name="verify_project" class="btn btn-success">
                                                <i class="fas fa-check me-1"></i>Verify
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="project_details.php?id=<?php echo $project['project_id']; ?>" 
                                      class="btn btn-primary">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php $result->data_seek(0); // Reset result pointer for table view ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Project Title</th>
                            <th>Team</th>
                            <th>Team Lead</th>
                            <th>Status</th>
                            <th>Timeline</th>
                            <th>Extension Request</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($project = $result->fetch_assoc()): ?>
                            <?php 
                            $status = $project['status'];
                            $statusInfo = $statusMap[$status] ?? ['class' => 'secondary', 'icon' => 'question-circle'];
                            $daysRemaining = $project['days_remaining'];
                            $isOverdue = $status !== 'completed' && $status !== 'verified' && $daysRemaining < 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-medium" style="white-space: normal; word-wrap: break-word; min-width: 200px;">
                                        <?php echo htmlspecialchars($project['title']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($project['team_name']); ?></td>
                                <td><?php echo htmlspecialchars($project['team_lead']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $statusInfo['class']; ?>">
                                        <i class="fas fa-<?php echo $statusInfo['icon']; ?> me-1"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div><span class="text-muted small">Start:</span> <?php echo date('M d, Y', strtotime($project['start_date'])); ?></div>
                                    <div>
                                        <span class="text-muted small">Due:</span> <?php echo date('M d, Y', strtotime($project['due_date'])); ?>
                                        <?php if ($isOverdue): ?>
                                            <div class="overdue-alert">
                                                <i class="fas fa-exclamation-circle"></i> Overdue by <?php echo abs($daysRemaining); ?> days
                                            </div>
                                        <?php elseif ($status !== 'completed' && $status !== 'verified' && $daysRemaining <= 5): ?>
                                            <div class="overdue-alert">
                                                <i class="fas fa-clock"></i> Due soon: <?php echo $daysRemaining; ?> days left
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($project['actual_end_date']): ?>
                                        <div><span class="text-muted small">Completed:</span> <?php echo date('M d, Y', strtotime($project['actual_end_date'])); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($project['extension_id']): ?>
                                        <div class="extension-pending">
                                            <div class="title">
                                                <i class="fas fa-exclamation-circle"></i>
                                                Extension Requested
                                            </div>
                                            <div class="details">
                                                <div><strong>New Due:</strong> <?php echo date('M d, Y', strtotime($project['requested_due_date'])); ?></div>
                                                <div><strong>Reason:</strong> <?php echo truncateDescription($project['extension_reason'], 50); ?></div>
                                            </div>
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="extension_id" value="<?php echo $project['extension_id']; ?>">
                                                <div class="mb-2">
                                                    <textarea name="response_note" class="form-control" placeholder="Your response..." required></textarea>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <select name="response_type" class="form-select" required>
                                                        <option value="">Select</option>
                                                        <option value="approved">Approve</option>
                                                        <option value="rejected">Reject</option>
                                                    </select>
                                                    <button type="submit" name="respond_extension" class="btn btn-primary btn-sm">
                                                        Submit
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <?php if ($project['status'] === 'completed'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                                <button type="submit" name="verify_project" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check me-1"></i>Verify
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="project_details.php?id=<?php echo $project['project_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>