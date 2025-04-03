<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    echo "<script>window.location.href = 'login.php';</script>";
}

// Get current user's id
$manager_id = $_SESSION['user_id'];

// Count active notifications
// 1. Pending project extensions less than 2 days old
$extensionQuery = "SELECT COUNT(*) as extension_count 
    FROM project_extensions pe 
    WHERE pe.status = 'pending' 
    AND pe.requested_date >= DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY)";
$extensionResult = $conn->query($extensionQuery);
$extensionCount = $extensionResult->fetch_assoc()['extension_count'];

// 2. Meetings requiring manager
$meetingQuery = "SELECT COUNT(*) as meeting_count 
    FROM meetings 
    WHERE manager_required = 1 
    AND (status = 'scheduled' OR status = 'in_progress')";
$meetingResult = $conn->query($meetingQuery);
$meetingCount = $meetingResult->fetch_assoc()['meeting_count'];

// Total notification count
$totalNotifications = $extensionCount + $meetingCount;

// Get teams count
$teamsQuery = "SELECT COUNT(*) as team_count FROM teams";
$teamsResult = $conn->query($teamsQuery);
$teamCount = $teamsResult->fetch_assoc()['team_count'];

// Get active projects count
$projectsQuery = "SELECT COUNT(*) as project_count FROM projects WHERE status != 'completed' AND status != 'verified'";
$projectsResult = $conn->query($projectsQuery);
$projectCount = $projectsResult->fetch_assoc()['project_count'];

// Get team leads count
$teamLeadsQuery = "SELECT COUNT(*) as lead_count FROM users WHERE role = 'TeamLead'";
$teamLeadsResult = $conn->query($teamLeadsQuery);
$teamLeadCount = $teamLeadsResult->fetch_assoc()['lead_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Console | Enterprise Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4361ee15;
            --secondary: #3f37c9;
            --success: #4cc9a4;
            --warning: #f9c74f;
            --danger: #f94144;
            --info: #4895ef;
            --purple: #7209b7;
            --dark: #1e293b;
            --light: #f8f9fa;
            --card-shadow: 0 10px 20px rgba(0,0,0,0.05);
            --hover-shadow: 0 15px 30px rgba(67, 97, 238, 0.1);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        .top-nav {
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            padding: 0.75rem 0;
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .notification-dropdown {
            min-width: 360px;
            padding: 0;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .stat-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            height: 100%;
            position: relative;
            z-index: 1;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1));
            z-index: -1;
            transition: all 0.4s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }
        
        .stat-card:hover::before {
            opacity: 0.8;
        }
        
        .stat-card .icon-bg {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.2);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .icon-bg {
            transform: rotate(10deg) scale(1.1);
        }
        
        .stat-card h3 {
            font-weight: 700;
            margin-bottom: 0;
            font-size: 1.8rem;
        }
        
        .action-card {
            border: none;
            border-radius: 16px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        
        .action-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
        }
        
        .action-card:hover::after {
            opacity: 1;
        }
        
        .action-card .btn {
            border-radius: 10px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .action-card .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        
        .action-card .card-icon {
            transition: all 0.4s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-bottom: 1rem;
            background-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        
        .action-card:hover .card-icon {
            transform: rotateY(180deg);
        }
        
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(249, 65, 68, 0.7);
            }
            70% {
                transform: scale(1.1);
                box-shadow: 0 0 0 10px rgba(249, 65, 68, 0);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(249, 65, 68, 0);
            }
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            border-radius: 24px;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(58, 12, 163, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .welcome-banner::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 60%);
            transform: rotate(-45deg);
            z-index: -1;
            animation: shine 6s infinite linear;
        }
        
        @keyframes shine {
            0% {
                transform: scale(1) rotate(-45deg) translateX(0);
            }
            50% {
                transform: scale(1.5) rotate(-45deg) translateX(-100%);
            }
            100% {
                transform: scale(1) rotate(-45deg) translateX(0);
            }
        }

        .welcome-banner h2 {
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }
        
        .welcome-banner h2::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 60px;
            height: 4px;
            background-color: var(--warning);
            border-radius: 4px;
        }

        .nav-btn {
            padding: 0.5rem;
            border-radius: 12px;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background-color: var(--primary-light);
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            border-radius: 12px;
            animation: fadeIn 0.3s ease;
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

        .btn-icon {
            width: 45px;
            height: 45px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
        }

        .btn-icon:hover {
            background-color: var(--primary-light);
            transform: translateY(-3px);
        }

        .user-menu {
            padding: 0.75rem;
            min-width: 220px;
        }

        .user-menu-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 10px;
            margin-bottom: 0.25rem;
        }

        .user-menu-item:hover {
            background-color: var(--primary-light);
            color: var(--primary);
            transform: translateX(5px);
        }

        .user-menu-item i {
            width: 20px;
            margin-right: 0.75rem;
        }
        
        .counter {
            font-weight: 700;
            display: inline-block;
        }
        
        /* Color definitions for stat cards */
        .bg-card-primary {
            background: linear-gradient(45deg, #4361ee, #3a0ca3);
            color: white;
        }
        
        .bg-card-success {
            background: linear-gradient(45deg, #4cc9a4, #1a936f);
            color: white;
        }
        
        .bg-card-info {
            background: linear-gradient(45deg, #4895ef, #3a86ff);
            color: white;
        }
        
        .bg-card-warning {
            background: linear-gradient(45deg, #f9c74f, #f8961e);
            color: white;
        }
        
        /* Animated background shapes */
        .shapes-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            opacity: 0.05;
            border-radius: 50%;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            background-color: var(--primary);
            top: -150px;
            right: -150px;
            animation: float 20s infinite alternate;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            background-color: var(--success);
            bottom: -100px;
            left: -100px;
            animation: float 15s infinite alternate-reverse;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            background-color: var(--warning);
            top: 60%;
            right: 10%;
            animation: float 18s infinite alternate;
        }
        
        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg
                @keyframes float {
                0% {
                    transform: translate(0, 0) rotate(0deg);
                }
                50% {
                    transform: translate(20px, 20px) rotate(10deg);
                }
                100% {
                    transform: translate(-20px, 10px) rotate(-10deg);
                }
            }
            
            /* Animate counting numbers */
            @keyframes countUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .count-animation {
                animation: countUp 1s ease forwards;
            }
            
            /* Card hover effects */
            .card-content {
                transition: all 0.4s ease;
            }
            
            .action-card:hover .card-content {
                transform: translateY(-5px);
            }
            
            /* Glassmorphism effect */
            .glass-effect {
                background: rgba(255, 255, 255, 0.7);
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            /* Add loading animation */
            .loading-animation {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: white;
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                transition: all 0.5s ease;
            }
            
            .loading-animation.fade-out {
                opacity: 0;
                visibility: hidden;
            }
            
            .loader {
                width: 50px;
                height: 50px;
                border: 5px solid var(--primary-light);
                border-top: 5px solid var(--primary);
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /* Custom scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
            }
            
            ::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 10px;
            }
            
            ::-webkit-scrollbar-thumb {
                background: #c5c5c5;
                border-radius: 10px;
            }
            
            ::-webkit-scrollbar-thumb:hover {
                background: #a8a8a8;
            }
            
            /* Bottom navigation for mobile */
            .mobile-nav {
                display: none;
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                background: white;
                padding: 0.75rem 0;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
                z-index: 1000;
            }
            
            @media (max-width: 768px) {
                .mobile-nav {
                    display: flex;
                }
                
                .container-fluid {
                    padding-bottom: 70px;
                }
                
                .welcome-banner {
                    padding: 2rem;
                }
            }
    </style>
</head>
<body>
    <!-- Loading Animation -->
    <div class="loading-animation">
        <div class="loader"></div>
    </div>

    <!-- Background Shapes -->
    <div class="shapes-container">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg top-nav sticky-top animate__animated animate__fadeInDown">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="fas fa-shield-alt me-2"></i>Enterprise Console
            </a>
            
            <div class="d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <button class="btn btn-icon position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($totalNotifications > 0): ?>
                        <span class="position-absolute bg-danger text-white notification-badge rounded-circle">
                            <?php echo $totalNotifications; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <h6 class="dropdown-header">Notifications</h6>
                        <?php if ($extensionCount > 0): ?>
                        <div class="notification-item">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <span><?php echo $extensionCount; ?> pending project extensions</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($meetingCount > 0): ?>
                        <div class="notification-item">
                            <i class="fas fa-calendar-check text-info me-2"></i>
                            <span><?php echo $meetingCount; ?> meetings require attention</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($totalNotifications === 0): ?>
                        <div class="notification-item text-muted">
                            <i class="fas fa-check-circle me-2"></i>
                            No new notifications
                        </div>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="manager_notifications.php" class="dropdown-item text-primary text-center">
                            View All Notifications
                        </a>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle"></i>
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
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">
        <!-- Welcome Banner -->
        <div class="welcome-banner shadow animate__animated animate__fadeIn">
            <h2 class="mb-2 fw-bold">Welcome to Enterprise Management</h2>
            <p class="mb-0 opacity-75">Monitor organizational performance and optimize team efficiency</p>
        </div>

        <!-- Statistics Grid -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="card stat-card bg-card-primary">
                    <div class="card-body">
                        <div class="icon-bg">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <h6 class="card-title text-white-50 mb-3">Total Teams</h6>
                        <h3 class="card-text mb-0 counter" data-count="<?php echo $teamCount; ?>">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                <div class="card stat-card bg-card-success">
                    <div class="card-body">
                        <div class="icon-bg">
                            <i class="fas fa-project-diagram text-white"></i>
                        </div>
                        <h6 class="card-title text-white-50 mb-3">Active Projects</h6>
                        <h3 class="card-text mb-0 counter" data-count="<?php echo $projectCount; ?>">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                <div class="card stat-card bg-card-info">
                    <div class="card-body">
                        <div class="icon-bg">
                            <i class="fas fa-user-tie text-white"></i>
                        </div>
                        <h6 class="card-title text-white-50 mb-3">Team Leads</h6>
                        <h3 class="card-text mb-0 counter" data-count="<?php echo $teamLeadCount; ?>">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                <div class="card stat-card bg-card-warning">
                    <div class="card-body">
                        <div class="icon-bg">
                            <i class="fas fa-bell text-white"></i>
                        </div>
                        <h6 class="card-title text-white-50 mb-3">Pending Actions</h6>
                        <h3 class="card-text mb-0 counter" data-count="<?php echo $totalNotifications; ?>">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="row g-4">
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="card-icon bg-primary-light">
                            <i class="fas fa-users-gear fa-2x text-primary"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Team Management</h5>
                            <p class="card-text text-muted mb-4">Oversee team structures</p>
                            <a href="manage_teams.php" class="btn btn-primary">Manage Teams</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="card-icon bg-success-light">
                            <i class="fas fa-tasks fa-2x text-success"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Project Oversight</h5>
                            <p class="card-text text-muted mb-4">Monitor project progress</p>
                            <a href="manage_projects.php" class="btn btn-success">View Projects</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.7s">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="card-icon bg-info-light">
                            <i class="fas fa-comments fa-2x text-info"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Team Discussions</h5>
                            <p class="card-text text-muted mb-4">View and participate in discussions</p>
                            <a href="manager_discussion.php" class="btn btn-info text-white">Open Discussions</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.8s">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="card-icon bg-warning-light">
                            <i class="fas fa-calendar-alt fa-2x text-warning"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Meeting Hub</h5>
                            <p class="card-text text-muted mb-4">Schedule and coordinate team meetings</p>
                            <a href="manager_meetings.php" class="btn btn-warning text-white">View Meetings</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 0.9s">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="card-icon" style="background-color: rgba(114, 9, 183, 0.1);">
                            <i class="fas fa-folder-tree fa-2x" style="color: var(--purple);"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Document Center</h5>
                            <p class="card-text text-muted mb-4">Manage and organize team documents</p>
                            <a href="manager_file_sharing.php" class="btn" style="background: var(--purple); color: white;">Manage Documents</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 1s">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="card-icon bg-danger-light">
                            <i class="fas fa-list-check fa-2x text-danger"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Project Assignment</h5>
                            <p class="card-text text-muted mb-4">Delegate and track project assignments</p>
                            <a href="assign_project.php" class="btn btn-danger">Assign Projects</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 animate__animated animate__fadeInUp" style="animation-delay: 1.1s">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="card-icon bg-success-light">
                            <i class="fas fa-chart-line fa-2x text-success"></i>
                        </div>
                    




                            <div class="card-content">
                            <h5 class="card-title">Analytics Dashboard</h5>
                            <p class="card-text text-muted mb-4">View project performance metrics and reports</p>
                            <a href="project_performance.php" class="btn btn-success">View Analytics</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation 
    <div class="mobile-nav justify-content-around">
        <a href="manage_teams.php" class="btn-icon text-primary">
            <i class="fas fa-users"></i>
        </a>
        <a href="manage_projects.php" class="btn-icon text-success">
            <i class="fas fa-tasks"></i>
        </a>
        <a href="manager_notifications.php" class="btn-icon text-danger position-relative">
            <i class="fas fa-bell"></i>
            <?php if ($totalNotifications > 0): ?>
            <span class="position-absolute top-0 end-0 bg-danger text-white notification-badge rounded-circle" style="transform: scale(0.7);">
                <?php echo $totalNotifications; ?>
            </span>
            <?php endif; ?>
        </a>
        <a href="#" class="btn-icon text-dark" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-ellipsis-h"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            <a href="manager_discussion.php" class="dropdown-item">
                <i class="fas fa-comments me-2 text-info"></i> Discussions
            </a>
            <a href="manager_meetings.php" class="dropdown-item">
                <i class="fas fa-calendar-alt me-2 text-warning"></i> Meetings
            </a>
            <a href="manager_file_sharing.php" class="dropdown-item">
                <i class="fas fa-folder-tree me-2" style="color: var(--purple);"></i> Documents
            </a>
            <a href="project_performance.php" class="dropdown-item">
                <i class="fas fa-chart-line me-2 text-success"></i> Analytics
            </a>
            <div class="dropdown-divider"></div>
            <a href="../logout.php" class="dropdown-item text-danger">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </div>
    </div> -->

    <!-- Bootstrap JS and other scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
    
    <script>
        // Loading animation
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                document.querySelector(".loading-animation").classList.add("fade-out");
            }, 800);
            
            // Start counter animations
            animateCounters();
            
            // Initialize tooltip
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Enhanced card animations
            animateCards();
        });
        
        // Animated counters
        function animateCounters() {
            const counters = document.querySelectorAll('.counter');
            
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-count'));
                const duration = 2000; // 2 seconds
                const increment = target / (duration / 50); // Update every 50ms
                let current = 0;
                
                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        setTimeout(updateCounter, 50);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                // Delay start based on position
                setTimeout(updateCounter, parseInt(counter.closest('.animate__fadeInUp').style.animationDelay) * 1000);
            });
        }
        
        // Enhanced card animations with GSAP
        function animateCards() {
            // Card hover effects
            document.querySelectorAll('.action-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    gsap.to(this, { 
                        y: -8, 
                        boxShadow: '0 15px 30px rgba(67, 97, 238, 0.15)',
                        duration: 0.4,
                        ease: "power2.out"
                    });
                    
                    // Rotate icon
                    const icon = this.querySelector('.card-icon');
                    gsap.to(icon, { 
                        rotationY: 180, 
                        duration: 0.6, 
                        ease: "back.out(1.7)" 
                    });
                });
                
                card.addEventListener('mouseleave', function() {
                    gsap.to(this, { 
                        y: 0, 
                        boxShadow: '0 10px 20px rgba(0,0,0,0.05)',
                        duration: 0.4,
                        ease: "power2.out"
                    });
                    
                    // Rotate icon back
                    const icon = this.querySelector('.card-icon');
                    gsap.to(icon, { 
                        rotationY: 0, 
                        duration: 0.6, 
                        ease: "back.out(1.7)" 
                    });
                });
            });
            
            // Stat card hover effects
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    gsap.to(this, { 
                        y: -8, 
                        boxShadow: '0 15px 30px rgba(67, 97, 238, 0.15)',
                        duration: 0.4,
                        ease: "power2.out"
                    });
                    
                    // Animate icon
                    const icon = this.querySelector('.icon-bg');
                    gsap.to(icon, { 
                        rotation: 10, 
                        scale: 1.1,
                        duration: 0.4, 
                        ease: "back.out(1.7)" 
                    });
                });
                
                card.addEventListener('mouseleave', function() {
                    gsap.to(this, { 
                        y: 0, 
                        boxShadow: '0 10px 20px rgba(0,0,0,0.05)',
                        duration: 0.4,
                        ease: "power2.out"
                    });
                    
                    // Animate icon back
                    const icon = this.querySelector('.icon-bg');
                    gsap.to(icon, { 
                        rotation: 0, 
                        scale: 1,
                        duration: 0.4, 
                        ease: "back.out(1.7)" 
                    });
                });
            });
            
            // Notification item hover effects
            document.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('mouseenter', function() {
                    gsap.to(this, { 
                        x: 5, 
                        backgroundColor: '#f8f9fa',
                        duration: 0.3,
                        ease: "power1.out"
                    });
                });
                
                item.addEventListener('mouseleave', function() {
                    gsap.to(this, { 
                        x: 0, 
                        backgroundColor: 'white',
                        duration: 0.3,
                        ease: "power1.out"
                    });
                });
            });
        }
        
        // Refresh notifications every 5 minutes
        setInterval(() => {
            fetch(window.location.href)
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    
                    // Update notification count
                    const newNotificationCount = doc.querySelector('.notification-badge')?.textContent || "0";
                    const currentBadge = document.querySelector('.notification-badge');
                    
                    if (currentBadge) {
                        if (newNotificationCount === "0") {
                            currentBadge.style.display = 'none';
                        } else {
                            currentBadge.textContent = newNotificationCount;
                            currentBadge.style.display = 'block';
                        }
                    }
                    
                    // Update notification dropdown
                    const newDropdown = doc.querySelector('.notification-dropdown');
                    if (newDropdown) {
                        document.querySelector('.notification-dropdown').innerHTML = newDropdown.innerHTML;
                    }
                    
                    // Animate any newly added notifications
                    document.querySelectorAll('.notification-item').forEach(item => {
                        item.classList.add('animate__animated', 'animate__fadeIn');
                        setTimeout(() => {
                            item.classList.remove('animate__animated', 'animate__fadeIn');
                        }, 1000);
                    });
                });
        }, 300000);
        
        // Add smooth parallax effect to background shapes
        document.addEventListener('mousemove', (e) => {
            const shapes = document.querySelectorAll('.shape');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            shapes.forEach(shape => {
                const speed = parseFloat(shape.getAttribute('data-speed') || 0.05);
                const offsetX = (x - 0.5) * speed * 100;
                const offsetY = (y - 0.5) * speed * 100;
                
                gsap.to(shape, {
                    x: offsetX,
                    y: offsetY,
                    duration: 1,
                    ease: "power1.out"
                });
            });
        });
    </script>
</body>
</html>
