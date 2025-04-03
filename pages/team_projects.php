<?php
session_start();
require '../includes/db_connect.php';


// Check if user is logged in and is a team lead
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

$message = '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $project_id = (int)$_POST['project_id'];
    $new_status = sanitizeInput($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE project_id = ? AND team_id = (SELECT team_id FROM teams WHERE team_lead_id = ?)");
    $stmt->bind_param("sii", $new_status, $project_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = "Status updated successfully!";
    } else {
        $message = "Error updating status: " . $conn->error;
    }
    $stmt->close();
}

// Handle extension requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_extension'])) {
    $project_id = (int)$_POST['project_id'];
    $reason = sanitizeInput($_POST['reason']);
    $new_due_date = $_POST['new_due_date'];
    
    $stmt = $conn->prepare("INSERT INTO project_extensions (project_id, requested_by, requested_date, reason, new_due_date) VALUES (?, ?, CURDATE(), ?, ?)");
    $stmt->bind_param("iiss", $project_id, $_SESSION['user_id'], $reason, $new_due_date);
    
    if ($stmt->execute()) {
        $message = "Extension request submitted successfully!";
    } else {
        $message = "Error submitting extension request: " . $conn->error;
    }
    $stmt->close();
}

// Build the query with filters
$query = "
    SELECT p.*, t.team_name,
    CASE 
        WHEN pe.status = 'pending' THEN 'Extension Pending'
        WHEN pe.status = 'approved' THEN pe.new_due_date
        ELSE p.due_date
    END as effective_due_date
    FROM projects p
    JOIN teams t ON p.team_id = t.team_id
    LEFT JOIN project_extensions pe ON p.project_id = pe.project_id AND pe.status = 'pending'
    WHERE t.team_lead_id = ?";

$params = array($_SESSION['user_id']);
$types = "i";

if ($filter_status) {
    $query .= " AND p.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_start_date) {
    $query .= " AND p.start_date >= ?";
    $params[] = $filter_start_date;
    $types .= "s";
}

if ($filter_end_date) {
    $query .= " AND p.due_date <= ?";
    $params[] = $filter_end_date;
    $types .= "s";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

function sanitizeInput($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Projects | TeamLead Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #1a73e8;
            --primary-dark: #0d47a1;
            --secondary: #5f6368;
            --background: #f5f7fa;
            --accent: #4285f4;
            --success: #0f9d58;
            --warning: #fbbc05;
            --danger: #ea4335;
            --light-gray: #e0e0e0;
            --white: #ffffff;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 8px 28px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
            padding-top: 0; /* Changed from 80px to 0 */
            color: #424242;
        }

        .navbar {
            background: linear-gradient(135deg, #3a66db, #2952c8);
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 120px;
            display: flex;
            align-items: center;
            position: relative; /* Added to ensure proper positioning */
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .navbar-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: white;
            margin: 0;
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
            margin-left: auto; /* Push to right */
        }

        .navbar-brand {
            font-weight: 600;
            color: var(--primary) !important;
        }

        .back-button {
            color: var(--primary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
            font-weight: 500;
        }

        .back-button:hover {
            color: var(--primary-dark);
            transform: translateX(-3px);
        }

        .project-card {
            background: var(--white);
            border-radius: 16px;
            border: none;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 1.8rem;
            overflow: hidden;
        }

        .project-card:hover {
            box-shadow: var(--hover-shadow);
            transform: translateY(-4px);
        }

        .status-badge {
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 500;
            font-size: 0.875rem;
            letter-spacing: 0.3px;
        }

        .status-notviewed { background-color: #e9ecef; color: var(--secondary); }
        .status-instudy { background-color: var(--warning); color: #212121; }
        .status-inprogress { background-color: var(--accent); color: var(--white); }
        .status-completed { background-color: var(--success); color: var(--white); }
        .status-verified { background-color: var(--primary-dark); color: var(--white); }

        .project-header {
            background-color: var(--white);
            padding: 2.5rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            box-shadow: var(--card-shadow);
        }

        .project-title {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
            font-size: 1.75rem;
        }

        .extension-form {
            background: rgba(66, 133, 244, 0.05);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border-left: 4px solid var(--accent);
        }

        .progress {
            height: 10px;
            border-radius: 8px;
            background-color: #e9ecef;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .progress-bar {
            background-image: linear-gradient(to right, var(--accent), var(--primary));
            border-radius: 8px;
        }

        .date-badge {
            background-color: rgba(66, 133, 244, 0.1);
            color: var(--primary);
            padding: 0.35rem 1rem;
            border-radius: 30px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .date-badge i {
            margin-right: 6px;
        }

        .action-button {
            padding: 0.6rem 1.4rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            transition: var(--transition);
            background-color: var(--accent);
            color: white;
            letter-spacing: 0.3px;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.2);
            background-color: var(--primary);
        }

        .action-button:active {
            transform: translateY(0);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(66, 133, 244, 0.2);
            border-top: 4px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.2);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-outline-primary {
            color: var(--accent);
            border-color: var(--accent);
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: var(--transition);
        }

        .btn-outline-primary:hover {
            background-color: var(--accent);
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .btn-outline-primary:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            margin-bottom: 1.5rem;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .alert-success {
            background-color: rgba(15, 157, 88, 0.1);
            color: var(--success);
        }

        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.15);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .project-header {
                padding: 1.5rem;
            }
            
            .project-card {
                margin-bottom: 1.2rem;
            }

            body {
                padding-top: 0; /* Changed from 70px to 0 */
            }
        }

        .active-filters {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: rgba(66, 133, 244, 0.1);
            color: var(--primary);
            border-radius: 30px;
            margin-right: 0.8rem;
            margin-bottom: 0.8rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .filter-tag:hover {
            background: rgba(66, 133, 244, 0.2);
        }

        .filter-tag .remove-filter {
            margin-left: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }

        .filter-tag .remove-filter:hover {
            color: var(--danger);
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        .modal-title {
            color: var(--primary);
            font-weight: 600;
        }

        .btn-secondary {
            background-color: #f5f5f5;
            color: var(--secondary);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
            color: #212121;
        }

        .project-description {
            color: #757575;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .project-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .card-divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(0, 0, 0, 0.05), transparent);
            margin: 1.5rem 0;
        }

        .empty-state {
            padding: 3rem 0;
            text-align: center;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #e0e0e0;
            margin-bottom: 1.5rem;
        }

        .empty-state-title {
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-state-text {
            color: #9e9e9e;
            max-width: 400px;
            margin: 0 auto;
        }

        .card-body-content {
            padding: 1.5rem 2rem;
        }

        .card-body-progress {
            background-color: rgba(245, 247, 250, 0.8);
            padding: 1.5rem 2rem;
            border-radius: 0 0 16px 16px;
        }

        .project-title-card {
            font-size: 1.35rem;
            font-weight: 600;
            color: #424242;
            margin-bottom: 0.75rem;
        }

        .navbar-dark {
            background-color: var(--primary);
        }

        .navbar-brand {
            color: white !important;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .back-button-light {
            color: rgba(255, 255, 255, 0.9);
            transition: var(--transition);
        }

        .back-button-light:hover {
            color: white;
            transform: translateX(-3px);
        }
    </style>
</head>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container navbar-container">
            <h1 class="navbar-title">
                <i class="fas fa-project-diagram me-2"></i>Team Projects
            </h1>
            <a href="teamlead_dashboard.php" class="btn-back">
                <i class="fas fa-home me-2"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Project Header -->
        <div class="project-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="project-title">Project Overview</h1>
                    <p class="text-muted mb-0">Track and manage your team's projects</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-2"></i>Filter Projects
                    </button>
                </div>
            </div>
        </div>

        <!-- Active Filters -->
        <?php if ($filter_status || $filter_start_date || $filter_end_date): ?>
        <div class="active-filters">
            <h6 class="mb-3 text-secondary"><i class="fas fa-tag me-2"></i>Active Filters:</h6>
            <?php if ($filter_status): ?>
            <span class="filter-tag">
                Status: <?php echo ucfirst($filter_status); ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => null])); ?>" class="remove-filter text-decoration-none">×</a>
            </span>
            <?php endif; ?>
            
            <?php if ($filter_start_date): ?>
            <span class="filter-tag">
                From: <?php echo $filter_start_date; ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['start_date' => null])); ?>" class="remove-filter text-decoration-none">×</a>
            </span>
            <?php endif; ?>

            <?php if ($filter_end_date): ?>
            <span class="filter-tag">
                To: <?php echo $filter_end_date; ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['end_date' => null])); ?>" class="remove-filter text-decoration-none">×</a>
            </span>
            <?php endif; ?>

            <a href="team_projects.php" class="btn btn-sm btn-outline-secondary ms-2">
                <i class="fas fa-times-circle me-1"></i>Clear All
            </a>
        </div>
        <?php endif; ?>

        <!-- Project Cards -->
        <?php while ($project = $result->fetch_assoc()): ?>
        <div class="project-card">
            <div class="card-body p-0">
                <div class="card-body-content">
                    <h4 class="project-title-card"><?php echo htmlspecialchars($project['title']); ?></h4>
                    <p class="project-description"><?php echo htmlspecialchars($project['description']); ?></p>
                    
                    <div class="project-meta">
                        <span class="status-badge status-<?php echo $project['status']; ?>">
                            <?php 
                                $status_icons = [
                                    'notviewed' => '<i class="far fa-eye-slash me-1"></i>',
                                    'instudy' => '<i class="fas fa-book me-1"></i>',
                                    'inprogress' => '<i class="fas fa-spinner me-1"></i>',
                                    'completed' => '<i class="fas fa-check-circle me-1"></i>',
                                    'verified' => '<i class="fas fa-shield-alt me-1"></i>'
                                ];
                                echo $status_icons[$project['status']] . ucfirst($project['status']); 
                            ?>
                        </span>
                        <span class="date-badge">
                            <i class="far fa-calendar-alt"></i>
                            Due: <?php echo $project['effective_due_date']; ?>
                        </span>
                    </div>

                    <?php if ($project['status'] !== 'verified'): ?>
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                        <div class="row g-2">
                            <div class="col-md-4 col-sm-6">
                                <select name="status" class="form-select">
                                <option value="notviewed" <?php echo $project['status'] == 'notviewed' ? 'selected' : ''; ?>>Not Viewed</option>
                                    <option value="instudy" <?php echo $project['status'] == 'instudy' ? 'selected' : ''; ?>>In Study</option>
                                    <option value="inprogress" <?php echo $project['status'] == 'inprogress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="update_status" class="btn btn-primary action-button">
                                    <i class="fas fa-sync-alt me-2"></i>Update Status
                                </button>
                            </div>
                        </div>
                    </form>

                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#extension<?php echo $project['project_id']; ?>">
                        <i class="fas fa-clock me-2"></i>Request Extension
                    </button>

                    <div class="collapse mt-3" id="extension<?php echo $project['project_id']; ?>">
                        <form method="POST" class="extension-form">
                            <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">New Due Date</label>
                                    <input type="date" name="new_due_date" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reason for Extension</label>
                                    <textarea name="reason" class="form-control" rows="2" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="request_extension" class="btn btn-primary action-button">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body-progress">
                    <div class="progress mb-2">
                        <div class="progress-bar" role="progressbar" style="width: 
                            <?php
                                switch($project['status']) {
                                    case 'notviewed': echo "0%"; break;
                                    case 'instudy': echo "25%"; break;
                                    case 'inprogress': echo "50%"; break;
                                    case 'completed': echo "75%"; break;
                                    case 'verified': echo "100%"; break;
                                }
                            ?>" 
                            aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small><i class="fas fa-flag-checkered me-1"></i> Start: <?php echo $project['start_date']; ?></small>
                        <small><i class="fas fa-hourglass-end me-1"></i> Due: <?php echo $project['effective_due_date']; ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if ($result->num_rows === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-search"></i></div>
            <h3 class="empty-state-title">No projects found</h3>
            <p class="empty-state-text">Try adjusting your filters or check back later</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-filter me-2"></i>Filter Projects</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm" action="" method="GET">
                        <div class="mb-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="notviewed" <?php echo $filter_status == 'notviewed' ? 'selected' : ''; ?>>Not Viewed</option>
                                <option value="instudy" <?php echo $filter_status == 'instudy' ? 'selected' : ''; ?>>In Study</option>
                                <option value="inprogress" <?php echo $filter_status == 'inprogress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="verified" <?php echo $filter_status == 'verified' ? 'selected' : ''; ?>>Verified</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date Range</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small">From</label>
                                    <input type="date" class="form-control" name="start_date" 
                                           value="<?php echo $filter_start_date; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">To</label>
                                    <input type="date" class="form-control" name="end_date"
                                           value="<?php echo $filter_end_date; ?>">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">
                        <i class="fas fa-check me-1"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Show loading overlay during form submissions
        document.addEventListener('submit', function(e) {
            document.getElementById('loadingOverlay').style.display = 'flex';
        });

        // Filter functionality
        function applyFilters() {
            document.getElementById('loadingOverlay').style.display = 'flex';
            document.getElementById('filterForm').submit();
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto-dismiss alerts after 5 seconds
        window.setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Add event listener to reset form when modal is closed
        document.getElementById('filterModal').addEventListener('hidden.bs.modal', function () {
            // Optional: Reset form fields if you want to clear unsubmitted changes
            // document.getElementById('filterForm').reset();
        });

        // Handle date validation
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.querySelector('input[name="start_date"]');
            const endDateInput = document.querySelector('input[name="end_date"]');
            
            if(startDateInput && endDateInput) {
                endDateInput.addEventListener('change', function() {
                    if(startDateInput.value && this.value && new Date(this.value) < new Date(startDateInput.value)) {
                        alert('End date cannot be earlier than start date');
                        this.value = '';
                    }
                });
                
                startDateInput.addEventListener('change', function() {
                    if(endDateInput.value && this.value && new Date(endDateInput.value) < new Date(this.value)) {
                        alert('Start date cannot be later than end date');
                        this.value = '';
                    }
                });
            }
        });
    </script>
</body>
</html>