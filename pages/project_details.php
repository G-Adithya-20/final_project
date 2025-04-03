<?php
session_start();
require '../includes/db_connect.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

// Check if project ID is provided
if (!isset($_GET['id'])) {
    // header("Location: " . ($_SESSION['role'] === 'Manager' ? 'manage_projects.php' : 'team_projects.php'));
    // exit();
    echo "<script>window.location.href = '" . ($_SESSION['role'] === 'Manager' ? 'manage_projects.php' : 'team_projects.php') . "';</script>";
exit();
}

$project_id = (int)$_GET['id'];

// Fetch project details with team info and extension history
$query = "
    SELECT p.*, t.team_name, u.username as team_lead,
    u2.username as created_by_name,
    DATEDIFF(p.due_date, CURDATE()) as days_remaining
    FROM projects p
    JOIN teams t ON p.team_id = t.team_id
    JOIN users u ON t.team_lead_id = u.user_id
    JOIN users u2 ON p.created_by = u2.user_id
    WHERE p.project_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    // Redirect if project not found
    $_SESSION['error'] = "Project not found.";
    // header("Location: " . ($_SESSION['role'] === 'Manager' ? 'manage_projects.php' : 'team_projects.php'));
    // exit();
    echo "<script>window.location.href = '" . ($_SESSION['role'] === 'Manager' ? 'manage_projects.php' : 'team_projects.php') . "';</script>";
exit();
}

$project = $result->fetch_assoc();

// Fetch extension history
$query = "
    SELECT pe.*, u.username as requested_by_name
    FROM project_extensions pe
    JOIN users u ON pe.requested_by = u.user_id
    WHERE pe.project_id = ?
    ORDER BY pe.requested_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$extensions = $stmt->get_result();

// Helper function to safely get array value
function getValue($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Helper function to format date
function formatDate($date) {
    return $date ? date('M d, Y', strtotime($date)) : 'N/A';
}

// Function to get progress percentage
function getProgressPercentage($project) {
    $status = getValue($project, 'status', 'unknown');
    
    if ( $status === 'verified') {
        return 100;
    } 
    elseif($status === 'completed') {
        return 90;
    }
    elseif ($status === 'inprogress') {
        return 60;
    } elseif ($status === 'instudy') {
        return 30;
    } else {
        return 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details - <?php echo htmlspecialchars(getValue($project, 'title')); ?></title>
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- AOS Animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary-color: #0f172a;
            --accent-color: #38bdf8;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #0ea5e9;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --gray-color: #64748b;
            --border-radius: 0.75rem;
            --box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(145deg, var(--primary-color), var(--primary-dark));
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .back-btn {
            transition: var(--transition);
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            letter-spacing: 0.025em;
            border: none;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .back-btn:hover {
            transform: translateX(-5px);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .container {
            max-width: 1200px;
            padding: 0 1.5rem;
        }

        .page-header {
            margin-bottom: 2rem;
            padding: 1.5rem 0;
        }

        .details-section, .extension-history {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .details-section:hover, .extension-history:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: translateY(-5px);
        }

        .section-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
        }

        .section-body {
            padding: 1.5rem;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-weight: 600;
            letter-spacing: 0.025em;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .info-card {
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: 100%;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 0.25rem;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .info-card .card-body {
            padding: 1.5rem;
        }

        .info-card .card-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }

        .info-card .card-title i {
            background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 0.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            transition: var(--transition);
            padding: 0.5rem;
            border-radius: 0.5rem;
        }

        .info-item:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-item i {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #f8fafc, #f1f5f9);
            border-radius: 50%;
            margin-right: 0.75rem;
            color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .info-item:hover i {
            transform: scale(1.1);
            background: linear-gradient(145deg, var(--primary-light), var(--primary-color));
            color: white;
        }

        .info-item strong {
            font-weight: 600;
            margin-right: 0.5rem;
            color: var(--dark-color);
        }

        .description {
            background-color: #f8fafc;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .description::after {
            content: '"';
            position: absolute;
            bottom: -20px;
            right: 10px;
            font-size: 8rem;
            opacity: 0.05;
            font-family: Georgia, serif;
            color: var(--primary-color);
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 9px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #e0e7ff, #38bdf8, #818cf8);
        }

        .timeline-item {
            position: relative;
            padding: 1.5rem;
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
        }

        .timeline-item:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.5rem;
            top: 1.5rem;
            width: 1rem;
            height: 1rem;
            background: linear-gradient(45deg, var(--accent-color), var(--primary-color));
            border-radius: 50%;
            box-shadow: 0 0 0 3px #e0e7ff, 0 0 0 6px rgba(59, 130, 246, 0.1);
            z-index: 1;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .timeline-content p {
            margin-bottom: 0.5rem;
        }

        .timeline-content p:last-child {
            margin-bottom: 0;
        }

        .alert {
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .progress-container {
            margin-bottom: 1.5rem;
        }

        .progress-bar {
            height: 0.5rem;
            border-radius: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .progress {
            height: 0.5rem;
            border-radius: 1rem;
            background-color: #e2e8f0;
            overflow: hidden;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 0;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-color);
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        .empty-state p {
            color: var(--gray-color);
            font-size: 1.1rem;
            max-width: 300px;
            margin: 0 auto;
        }

        .badge {
            font-weight: 600;
            border-radius: 50px;
            padding: 0.35rem 0.85rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .badge-completed {
            background: linear-gradient(45deg, #059669, #10b981);
        }

        .badge-verified {
            background: linear-gradient(45deg, #1d4ed8, #3b82f6);
        }

        .badge-inprogress {
            background: linear-gradient(45deg, #d97706, #f59e0b);
        }

        .badge-instudy {
            background: linear-gradient(45deg, #0284c7, #0ea5e9);
        }

        .badge-approved {
            background: linear-gradient(45deg, #059669, #10b981);
        }

        .badge-rejected {
            background: linear-gradient(45deg, #b91c1c, #ef4444);
        }

        .badge-pending {
            background: linear-gradient(45deg, #d97706, #f59e0b);
        }

        .days-remaining {
            font-weight: 600;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .days-remaining-positive {
            background: linear-gradient(45deg, #0ea5e9, #38bdf8);
            color: white;
        }

        .days-remaining-negative {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            color: white;
        }

        @media (max-width: 768px) {
            .timeline {
                padding-left: 1.5rem;
            }
            
            .timeline-item::before {
                left: -2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-project-diagram me-2"></i>
                Project Details
            </a>
            <div class="d-flex">
                <a href="<?php echo $_SESSION['role'] === 'Manager' ? 'manage_projects.php' : 'team_projects.php'; ?>" 
                   class="btn back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Back to Project Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="page-header" data-aos="fade-up">
            <h1 class="display-6 fw-bold text-primary-dark"><?php echo htmlspecialchars(getValue($project, 'title')); ?></h1>
            <div class="progress-container">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Project Progress</span>
                    <span class="fw-bold"><?php echo getProgressPercentage($project); ?>%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?php echo getProgressPercentage($project); ?>%" 
                         aria-valuenow="<?php echo getProgressPercentage($project); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="details-section" data-aos="fade-up">
                    <div class="section-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0 fs-4 fw-bold">Project Overview</h2>
                            <?php 
                            $status = getValue($project, 'status', 'unknown');
                            $statusClass = match($status) {
                                'completed' => 'badge-completed',
                                'verified' => 'badge-verified',
                                'inprogress' => 'badge-inprogress',
                                'instudy' => 'badge-instudy',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $statusClass; ?> status-badge">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="section-body">
                        <div class="mb-4">
                            <h5 class="text-primary mb-3 fs-6 fw-bold">Project Description</h5>
                            <div class="description">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars(getValue($project, 'description'))); ?></p>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6" data-aos="fade-right" data-aos-delay="100">
                                <div class="info-card">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-users"></i>
                                            Team Information
                                        </h5>
                                        <div class="info-item">
                                            <i class="fas fa-user-group"></i>
                                            <div>
                                                <strong>Team:</strong> 
                                                <?php echo htmlspecialchars(getValue($project, 'team_name')); ?>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-user-tie"></i>
                                            <div>
                                                <strong>Team Lead:</strong> 
                                                <?php echo htmlspecialchars(getValue($project, 'team_lead')); ?>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-user"></i>
                                            <div>
                                                <strong>Created By:</strong> 
                                                <?php echo htmlspecialchars(getValue($project, 'created_by_name')); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6" data-aos="fade-left" data-aos-delay="200">
                                <div class="info-card">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-calendar"></i>
                                            Timeline
                                        </h5>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-plus"></i>
                                            <div>
                                                <strong>Start Date:</strong> 
                                                <?php echo formatDate(getValue($project, 'start_date')); ?>
                                            </div>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-check"></i>
                                            <div>
                                                <strong>Due Date:</strong> 
                                                <?php echo formatDate(getValue($project, 'due_date')); ?>
                                            </div>
                                        </div>
                                        <?php if (getValue($project, 'actual_end_date')): ?>
                                            <div class="info-item">
                                                <i class="fas fa-flag-checkered"></i>
                                                <div>
                                                    <strong>Completed:</strong> 
                                                    <?php echo formatDate(getValue($project, 'actual_end_date')); ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="info-item">
                                                <i class="fas fa-clock"></i>
                                                <div>
                                                    <strong>Status:</strong>
                                                    <?php 
                                                    $days_remaining = getValue($project, 'days_remaining', 0);
                                                    $daysClass = $days_remaining < 0 ? 'days-remaining-negative' : 'days-remaining-positive';
                                                    ?>
                                                    <span class="days-remaining <?php echo $daysClass; ?>">
                                                        <i class="fas <?php echo $days_remaining < 0 ? 'fa-exclamation-triangle' : 'fa-calendar-day'; ?>"></i>
                                                        <?php echo $days_remaining < 0 
                                                            ? 'Overdue by ' . abs($days_remaining) . ' days'
                                                            : $days_remaining . ' days left'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="extension-history" data-aos="fade-up" data-aos-delay="300">
                    <div class="section-header">
                        <h3 class="mb-0 fs-4 fw-bold">Extension History</h3>
                    </div>
                    
                    <div class="section-body">
                        <?php if ($extensions && $extensions->num_rows > 0): ?>
                            <div class="timeline">
                                <?php $delay = 100; ?>
                                <?php while ($extension = $extensions->fetch_assoc()): ?>
                                    <div class="timeline-item" data-aos="fade-right" data-aos-delay="<?php echo $delay; ?>">
                                        <?php $delay += 100; ?>
                                        <div class="timeline-header">
                                            <?php 
                                                $statusClass = match(getValue($extension, 'status', 'pending')) {
                                                    'approved' => 'badge-approved',
                                                    'rejected' => 'badge-rejected',
                                                    default => 'badge-pending'
                                                };
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(getValue($extension, 'status', 'pending')); ?>
                                            </span>
                                            <small class="text-muted">
                                                <i class="far fa-calendar-alt me-1"></i>
                                                <?php echo formatDate(getValue($extension, 'requested_date')); ?>
                                            </small>
                                        </div>
                                        <div class="timeline-content">
                                            <p class="mb-2">
                                                <strong>Requested By:</strong> 
                                                <?php echo htmlspecialchars(getValue($extension, 'requested_by_name')); ?>
                                            </p>
                                            <p class="mb-2">
                                                <strong>New Due Date:</strong> 
                                                <?php echo formatDate(getValue($extension, 'new_due_date')); ?>
                                            </p>
                                            <p class="mb-2">
                                                <strong>Reason:</strong> 
                                                <?php echo htmlspecialchars(getValue($extension, 'reason')); ?>
                                            </p>
                                            <?php if (getValue($extension, 'response_note')): ?>
                                                <p class="mb-0">
                                                    <strong>Response:</strong> 
                                                    <?php echo htmlspecialchars(getValue($extension, 'response_note')); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state" data-aos="fade-up">
                                <i class="fas fa-file-signature"></i>
                                <p>No extension requests found for this project.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

           
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    </script>
</body>
</html>