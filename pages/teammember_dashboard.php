<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamMember') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$notificationCount = 0;

// Count unviewed tasks - matching the notifications page query
$taskQuery = "SELECT COUNT(*) as count 
    FROM tasks t 
    JOIN users u ON t.assigned_by = u.user_id 
    WHERE t.assigned_to = ? AND t.status = 'not_viewed'";
$stmt = $conn->prepare($taskQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$notificationCount += $row['count'];

// Count upcoming meetings - matching the notifications page query
$meetingQuery = "SELECT COUNT(*) as count 
    FROM meetings 
    WHERE team_id = ? 
    AND (status = 'scheduled' OR status = 'in_progress')";
$stmt = $conn->prepare($meetingQuery);
$stmt->bind_param("i", $team_id);
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
    <title>Team Member Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Inter & Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #475569;
            --background: #f8fafc;
            --accent: #0ea5e9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            --hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --border-radius: 16px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
            color: #1e293b;
            padding-top: 80px;
        }

        /* Improved Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 0.75rem 0;
            height: 80px;
            display: flex;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.025em;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .navbar-brand i {
            color: rgba(255, 255, 255, 0.9);
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.92) !important;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            border-radius: 8px;
        }

        .nav-link:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-1px);
        }

        /* Enhanced Notification Link */
        .notification-link {
            position: relative;
            padding: 0.6rem;
            color: rgba(255, 255, 255, 0.92);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
        }

        .notification-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 0.2rem 0.45rem;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 2px solid white;
        }

        /* Improved Dashboard Header */
        .dashboard-header {
            background: linear-gradient(145deg, #ffffff, #f9fafb);
            padding: 2.75rem 3rem;
            border-radius: var(--border-radius);
            margin-bottom: 2.5rem;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .dashboard-title {
            color: var(--primary-dark);
            font-weight: 700;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            position: relative;
        }

        .dashboard-subtitle {
            color: var(--secondary);
            font-weight: 500;
            margin: 0.9rem 0 0;
            font-size: 1.15rem;
            opacity: 0.9;
        }

        /* Enhanced Menu Cards */
        .menu-card {
            background: #ffffff;
            border-radius: var(--border-radius);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--accent));
            opacity: 0;
            transition: all 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--hover-shadow);
            border-color: rgba(203, 213, 225, 0.5);
        }

        .menu-card:hover::before {
            opacity: 1;
        }

        .menu-icon {
            color: var(--accent);
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            background: rgba(14, 165, 233, 0.08);
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(14, 165, 233, 0.15);
        }

        .menu-card:hover .menu-icon {
            background: rgba(14, 165, 233, 0.15);
            transform: scale(1.05) translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.1);
        }

        .menu-title {
            color: var(--primary-dark);
            font-weight: 600;
            font-size: 1.2rem;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            letter-spacing: -0.01em;
        }

        /* Enhanced User Menu */
        .btn-icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
            border: none;
        }

        .btn-icon:hover {
            background-color: rgba(255, 255, 255, 0.18);
            transform: translateY(-2px);
        }

        .user-menu {
            padding: 0.85rem;
            border: none;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border-radius: 14px;
            min-width: 240px;
            background: white;
            margin-top: 10px;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .user-menu-item {
            padding: 0.9rem 1rem;
            display: flex;
            align-items: center;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 10px;
            font-weight: 500;
            margin: 0.2rem 0;
        }

        .user-menu-item:hover {
            background-color: #f1f5f9;
            color: var(--primary);
        }

        .user-menu-item i {
            width: 22px;
            margin-right: 0.85rem;
            font-size: 1.05rem;
        }

        .user-menu-item.text-danger:hover {
            color: var(--danger);
            background-color: rgba(239, 68, 68, 0.08);
        }

        .dropdown-divider {
            margin: 0.6rem 0;
            border-top: 1px solid #e2e8f0;
        }
        
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            margin-top: 80px;
            padding: 0 1.5rem;
        }

        /* Mobile Responsive Improvements */
        @media (max-width: 991px) {
            .navbar-collapse {
                background: linear-gradient(145deg, var(--primary), var(--primary-dark));
                padding: 1.2rem;
                border-radius: 0 0 16px 16px;
                margin-top: 0.5rem;
                box-shadow: 0 15px 20px -5px rgba(0, 0, 0, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .navbar-nav {
                padding: 0.5rem 0;
            }

            .nav-item {
                margin: 0.6rem 0;
            }

            .notification-link {
                display: inline-flex;
                margin: 0.5rem 0;
            }

            .dropdown {
                display: block;
                margin: 0.5rem 0;
            }

            .user-menu {
                position: static !important;
                width: 100%;
                margin-top: 0.5rem;
                transform: none !important;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 2rem;
            }
            
            .menu-card {
                margin-bottom: 1rem;
            }
            
            .dashboard-title {
                font-size: 1.6rem;
            }

            .dashboard-subtitle {
                font-size: 1rem;
            }

            .content-container {
                padding: 0 1rem;
            }
        }

        /* Enhanced Visual Elements */
        .card-body {
            padding: 2rem 1.5rem;
        }

        /* Optional: Add smooth transitions for overall page */
        * {
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="fas fa-users"></i>
                Team Member Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item me-3">
                        <a class="notification-link" href="teammember_notifications.php">
                            <i class="fas fa-bell"></i>
                            <?php if ($notificationCount > 0): ?>
                                <span class="notification-badge"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <span class="nav-link">
                            <i class="fas fa-user-circle me-2"></i>Team Member
                        </span>
                    </li>
                    <li class="nav-item">
                        <div class="dropdown">
                            <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cog fa-lg text-white"></i>
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
    <div class="container content-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Welcome to Your Dashboard</h1>
            <p class="dashboard-subtitle">Manage your tasks and team activities effectively</p>
        </div>

        <!-- Dashboard Menu Grid -->
        <div class="row g-4">
            <div class="col-lg-3 col-md-6 col-sm-6">
                <a href="t_member_mytasks.php" class="text-decoration-none">
                    <div class="menu-card card">
                        <div class="card-body text-center">
                            <div class="menu-icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h5 class="menu-title">My Tasks</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <a href="view_meetings.php" class="text-decoration-none">
                    <div class="menu-card card">
                        <div class="card-body text-center">
                            <div class="menu-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <h5 class="menu-title">View Meetings</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <a href="discussion.php" class="text-decoration-none">
                    <div class="menu-card card">
                        <div class="card-body text-center">
                            <div class="menu-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h5 class="menu-title">View Discussions</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-lg-3 col-md-6 col-sm-6">
                <a href="file_sharing.php" class="text-decoration-none">
                    <div class="menu-card card">
                        <div class="card-body text-center">
                            <div class="menu-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h5 class="menu-title">File Share</h5>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>