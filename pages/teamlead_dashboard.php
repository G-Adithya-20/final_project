<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
}

$user_id = $_SESSION['user_id'];
$notificationCount = 0;

// Count extension responses from last 2 days
$extensionQuery = "SELECT COUNT(*) as count
    FROM project_extensions
    WHERE requested_by = ?
    AND (status = 'approved' OR status = 'rejected')
    AND responded_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)";
$stmt = $conn->prepare($extensionQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$notificationCount += $row['count'];

// Count upcoming meetings
$meetingQuery = "SELECT COUNT(*) as count FROM meetings WHERE created_by = ? AND status = 'scheduled'";
$stmt = $conn->prepare($meetingQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$notificationCount += $row['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Lead Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Inter & Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #6366f1;
            --primary-dark: #3a56d4;
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
            --transition: all 0.3s ease;
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.12);
            --gradient-primary: linear-gradient(135deg, #4361ee, #3a56d4);
            --gradient-secondary: linear-gradient(135deg, #0ea5e9, #2563eb);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f4f8;
            min-height: 100vh;
            padding-top: 80px;
        }

        .navbar {
            background: var(--gradient-primary);
            box-shadow: var(--shadow-md);
            padding: 0.8rem 1.5rem;
            height: 70px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .navbar-brand i {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2rem;
        }

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            padding: 0.5rem 1rem;
            transition: var(--transition);
            position: relative;
            font-weight: 500;
        }

        .nav-link:hover {
            color: #ffffff !important;
            transform: translateY(-2px);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            width: 0;
            height: 2px;
            background: #ffffff;
            transition: var(--transition);
            transform: translateX(-50%);
        }

        .nav-link:hover::after {
            width: 70%;
        }

        .notification-link {
            position: relative;
            padding: 0.5rem 0.7rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.1);
        }

        .notification-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.45rem;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .dashboard-header {
            background: white;
            padding: 2rem 2.5rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            border-left: 5px solid var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 250px;
            height: 100%;
            background: linear-gradient(to left, rgba(99, 102, 241, 0.08), transparent);
            z-index: 1;
        }

        .dashboard-intro {
            flex: 1;
        }

        .dashboard-title {
            color: var(--text-primary);
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            margin: 0;
            font-size: 1.8rem;
        }

        .dashboard-subtitle {
            color: var(--text-secondary);
            font-weight: 500;
            margin: 0.5rem 0 0;
            opacity: 0.9;
            font-size: 1.05rem;
        }

        .dashboard-graphic {
            z-index: 2;
            font-size: 3.5rem;
            color: var(--primary-light);
            opacity: 0.7;
        }

        .menu-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: none;
            transition: var(--transition);
            height: 100%;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }

        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.4s ease;
        }

        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .menu-card:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .menu-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(99, 102, 241, 0.2));
            border-radius: 12px;
            margin-bottom: 1.2rem;
            transition: var(--transition);
            color: var(--primary-color);
            font-size: 1.8rem;
        }

        .menu-card:hover .menu-icon {
            transform: scale(1.1);
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.2), rgba(99, 102, 241, 0.3));
        }

        .menu-title {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.15rem;
            margin: 0;
            transition: var(--transition);
        }

        .menu-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            transition: var(--transition);
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: rgba(255, 255, 255, 0.9);
        }

        .btn-icon:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .user-menu {
            padding: 0.8rem;
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: 12px;
            min-width: 220px;
            animation: fadeIn 0.2s ease-out;
            background: var(--card-bg);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-menu-item {
            padding: 0.9rem 1.2rem;
            display: flex;
            align-items: center;
            color: var(--text-primary);
            text-decoration: none;
            transition: var(--transition);
            border-radius: 8px;
            margin-bottom: 3px;
            font-weight: 500;
        }

        .user-menu-item:hover {
            background-color: rgba(67, 97, 238, 0.08);
            color: var(--primary-color);
        }

        .user-menu-item i {
            width: 20px;
            margin-right: 1rem;
            font-size: 1rem;
        }

        .user-menu-item.text-danger:hover {
            color: var(--danger);
            background-color: rgba(239, 68, 68, 0.08);
        }

        .dropdown-divider {
            margin: 0.7rem 0;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .user-profile {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.95);
            background-color: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1.2rem;
            border-radius: 10px;
            transition: var(--transition);
            font-weight: 500;
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }

        .user-profile i {
            margin-right: 10px;
        }

        .card-body {
            padding: 1.8rem;
        }

        .card-body-flex {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            height: 100%;
        }

        .card-content {
            margin-top: auto;
        }

        .page-section {
            margin-bottom: 2.5rem;
        }

        .section-heading {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            padding-left: 0.5rem;
            position: relative;
        }

        .section-heading::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 22px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        @media (max-width: 992px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                padding: 1.8rem;
            }
            
            .dashboard-graphic {
                display: none;
            }
            
            .menu-card {
                margin-bottom: 0.75rem;
            }
        }

        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .dashboard-title {
                font-size: 1.5rem;
            }
            
            .section-heading {
                font-size: 1.25rem;
            }
            
            .menu-icon {
                width: 55px;
                height: 55px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                TeamLead Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <a class="notification-link" href="teamlead_notifications.php">
                            <i class="fas fa-bell"></i>
                            <?php if ($notificationCount > 0): ?>
                                <span class="notification-badge"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <span class="user-profile">
                            <i class="fas fa-user-tie"></i>Team Lead
                        </span>
                    </li>
                    <li class="nav-item">
                        <!-- User dropdown menu -->
                        <div class="dropdown">
                            <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle fa-lg"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end user-menu">
                                <a href="change_password.php" class="user-menu-item">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="../logout.php" class="user-menu-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-intro">
                <h1 class="dashboard-title">Welcome to Your Dashboard</h1>
                <p class="dashboard-subtitle">Manage your team and projects efficiently</p>
            </div>
            <div class="dashboard-graphic">
                <i class="fas fa-tachometer-alt"></i>
            </div>
        </div>

        <!-- Team Management Section -->
        <div class="page-section">
            <h2 class="section-heading">Team Management</h2>
            <div class="row g-4">
                <div class="col-md-4 col-sm-6">
                    <a href="team_details.php" class="text-decoration-none">
                        <div class="menu-card card h-100">
                            <div class="card-body card-body-flex">
                                <div class="menu-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="card-content">
                                    <h5 class="menu-title">Team Progress</h5>
                                    <p class="menu-description">Monitor individual performance and task details</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 col-sm-6">
                    <a href="assign_tasks.php" class="text-decoration-none">
                        <div class="menu-card card h-100">
                            <div class="card-body card-body-flex">
                                <div class="menu-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="card-content">
                                    <h5 class="menu-title">Task Assignment</h5>
                                    <p class="menu-description">Delegate and manage responsibilities</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 col-sm-6">
                    <a href="discussion.php" class="text-decoration-none">
                        <div class="menu-card card h-100">
                            <div class="card-body card-body-flex">
                                <div class="menu-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="card-content">
                                    <h5 class="menu-title">Team Discussions</h5>
                                    <p class="menu-description">Communicate and collaborate with your team</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Projects & Meetings Section -->
        <div class="page-section">
            <h2 class="section-heading">Projects & Meetings</h2>
            <div class="row g-4">
                <div class="col-md-4 col-sm-6">
                    <a href="team_projects.php" class="text-decoration-none">
                        <div class="menu-card card h-100">
                            <div class="card-body card-body-flex">
                                <div class="menu-icon">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                                <div class="card-content">
                                    <h5 class="menu-title">Projects Overview</h5>
                                    <p class="menu-description">Track project progress and milestones</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 col-sm-6">
                    <a href="create_meeting.php" class="text-decoration-none">
                        <div class="menu-card card h-100">
                            <div class="card-body card-body-flex">
                                <div class="menu-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="card-content">
                                    <h5 class="menu-title">Schedule Meeting</h5>
                                    <p class="menu-description">Create new team meetings and events</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 col-sm-6">
                    <a href="view_meetings.php" class="text-decoration-none">
                        <div class="menu-card card h-100">
                            <div class="card-body card-body-flex">
                                <div class="menu-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="card-content">
                                    <h5 class="menu-title">View Meetings</h5>
                                    <p class="menu-description">Manage upcoming and active meetings</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-md-4 col-sm-6">
                    <a href="file_sharing.php" class="text-decoration-none">
                        <div class="menu-card card h-100">
                            <div class="card-body card-body-flex">
                                <div class="menu-icon">
                                    <i class="fas fa-share-alt"></i>
                                </div>
                                <div class="card-content">
                                    <h5 class="menu-title">File Sharing</h5>
                                    <p class="menu-description">Share documents and resources with your team</p>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>