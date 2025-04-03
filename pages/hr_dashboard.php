<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HR') {
    echo "<script>window.location.href = 'login.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Command Center</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4F46E5, #6366F1);
            --secondary-gradient: linear-gradient(135deg, #F43F5E, #EC4899);
            --success-gradient: linear-gradient(135deg, #059669, #10B981);
            --info-gradient: linear-gradient(135deg, #2563EB, #3B82F6);
            --warning-gradient: linear-gradient(135deg, #D97706, #F59E0B);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: 1px solid rgba(255, 255, 255, 0.2);
            --card-shadow-hover: 0 25px 35px rgba(0, 0, 0, 0.08);
            --nav-height: 80px;
            
            --primary: #4F46E5;
            --primary-light: rgba(99, 102, 241, 0.1);
            --success: #059669;
            --success-light: rgba(16, 185, 129, 0.1);
            --info: #2563EB;
            --info-light: rgba(59, 130, 246, 0.1);
            --warning: #D97706;
            --warning-light: rgba(245, 158, 11, 0.1);
            
            --body-bg: #F9FAFB;
            --card-bg: #FFFFFF;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-muted: #6B7280;
            
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-primary);
            min-height: 100vh;
            padding-top: 0; /* Remove padding-top since navbar is no longer fixed */
        }
        
        /* Navbar styles */
        .navbar {
            background: var(--primary-gradient);
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: static; /* Change from fixed to static */
            height: auto; /* Remove fixed height */
        }

        .navbar-brand {
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: white !important;
            display: flex;
            align-items: center;
        }

        .navbar-logo {
            width: 45px;
            height: 45px;
            background: var(--glass-bg);
            border: var(--glass-border);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-right: 12px;
        }
        
        /* Main content container */
        .page-container {
            padding: 2.5rem 0;
        }

        .content-container {
            margin-top: 0; /* Remove margin since navbar is static */
        }
        
        /* Enhanced dashboard header */
        .dashboard-header {
            background: linear-gradient(to right, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.8));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            padding: 3rem;
            margin-bottom: 3.5rem;
            position: relative;
        }
        
        .dashboard-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }
        
        .dashboard-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            background: var(--primary-gradient);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }
        
        .dashboard-subtitle {
            color: var(--text-secondary);
            font-weight: 400;
            font-size: 1.05rem;
            max-width: 600px;
        }
        
        /* Stats cards */
        .stats-section {
            margin-bottom: 3rem;
        }
        
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .stats-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0;
            font-size: 1.25rem;
        }
        
        .stats-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        /* Enhanced stats cards */
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: var(--shadow);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .stat-card .card-body {
            padding: 2.5rem;
            z-index: 2;
            position: relative;
        }
        
        .stat-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .stat-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            font-size: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .stat-icon::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 18px;
            opacity: 0.2;
            z-index: -1;
        }
        
        .stat-primary {
            color: var(--primary);
            background: var(--primary-light);
        }
        
        .stat-primary::before {
            background: var(--primary-gradient);
        }
        
        .stat-success {
            color: var(--success);
            background: var(--success-light);
        }
        
        .stat-success::before {
            background: var(--success-gradient);
        }
        
        .stat-warning {
            color: var(--warning);
            background: var(--warning-light);
        }
        
        .stat-warning::before {
            background: var(--warning-gradient);
        }
        
        .stat-info {
            color: var(--info);
            background: var(--info-light);
        }
        
        .stat-info::before {
            background: var(--info-gradient);
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: -1px;
            line-height: 1.1;
            margin-bottom: 0.5rem;
            background: var(--primary-gradient);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-value.success-value {
            background: var(--success-gradient);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-value.warning-value {
            background: var(--warning-gradient);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-value.info-value {
            background: var(--info-gradient);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0;
            font-size: 1.05rem;
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }
        
        .stat-card.success::after {
            background: var(--success-gradient);
        }
        
        .stat-card.warning::after {
            background: var(--warning-gradient);
        }
        
        .stat-card.info::after {
            background: var(--info-gradient);
        }
        
        /* Features section */
        .features-section {
            margin-bottom: 2rem;
        }
        
        .section-header {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            position: relative;
            display: inline-block;
            font-size: 1.25rem;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }
        
        .section-subtitle {
            color: var(--text-muted);
            max-width: 700px;
        }
        
        /* Enhanced feature cards */
        .feature-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: var(--shadow);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: visible;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }
        
        .feature-card .card-body {
            padding: 2.5rem;
            z-index: 2;
            position: relative;
        }
        
        .feature-icon-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .feature-icon {
            width: 85px;
            height: 85px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 25px;
            font-size: 1.75rem;
            position: relative;
            z-index: 2;
            margin-bottom: 1.5rem;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 20px;
            opacity: 0.2;
            z-index: -1;
        }
        
        .feature-title {
            font-size: 1.4rem;
            margin-bottom: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .feature-text {
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: 1.05rem;
        }
        
        .feature-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .feature-card:hover::after {
            opacity: 1;
        }
        
        .feature-card:nth-child(2)::after {
            background: var(--success-gradient);
        }
        
        .feature-card:nth-child(3)::after {
            background: var(--warning-gradient);
        }
        
        .feature-card:nth-child(4)::after {
            background: var(--info-gradient);
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: radial-gradient(rgba(79, 70, 229, 0.05) 0%, rgba(79, 70, 229, 0) 70%);
            z-index: 1;
        }
        
        .feature-card:nth-child(2)::before {
            background: radial-gradient(rgba(5, 150, 105, 0.05) 0%, rgba(5, 150, 105, 0) 70%);
        }
        
        .feature-card:nth-child(3)::before {
            background: radial-gradient(rgba(217, 119, 6, 0.05) 0%, rgba(217, 119, 6, 0) 70%);
        }
        
        .feature-card:nth-child(4)::before {
            background: radial-gradient(rgba(37, 99, 235, 0.05) 0%, rgba(37, 99, 235, 0) 70%);
        }
        
        .arrow-icon {
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover .arrow-icon {
            transform: translateX(5px);
        }
        
        /* Feature links enhancement */
        .feature-link {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .feature-link::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--primary-gradient);
            opacity: 0.1;
            border-radius: 50px;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .feature-link:hover::before {
            transform: scaleX(1);
        }
        
        .feature-link:hover {
            color: var(--primary);
        }
        
        /* Enhanced logout button */
        .logout-btn {
            background: var(--glass-bg);
            border: var(--glass-border);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.9rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(4px);
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logout-btn i {
            margin-right: 0.5rem;
        }
        
        /* Visual elements */
        .shape-decorator {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.05) 0%, rgba(79, 70, 229, 0) 70%);
            pointer-events: none;
            z-index: 0;
            opacity: 0.3;
            filter: blur(100px);
        }
        
        .shape-1 {
            top: -100px;
            right: -100px;
        }
        
        .shape-2 {
            bottom: -150px;
            left: -150px;
            width: 400px;
            height: 400px;
        }
    </style>
</head>
<body>
    <!-- Background shapes -->
    <div class="shape-decorator shape-1"></div>
    <div class="shape-decorator shape-2"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <div class="navbar-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                HR Command Center
            </a>
            <div class="ms-auto">
                <a href="../logout.php" class="btn logout-btn">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container page-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Welcome to HR Command Center</h1>
            <p class="dashboard-subtitle">Streamline workforce management, optimize team structures, and leverage data-driven insights for strategic HR decisions.</p>
        </div>
        
        <!-- Statistics Section -->
        <div class="stats-section">
            <div class="stats-header">
                <div>
                    <h4 class="stats-title">Organization Overview</h4>
                    <p class="stats-subtitle">Real-time metrics on organizational structure</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="card-body">
                            <div class="stat-icon-wrapper">
                                <div class="stat-icon stat-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <h3 class="stat-value">
                                <?php
                                $query = "SELECT COUNT(*) as count FROM users";
                                $result = $conn->query($query);
                                $row = $result->fetch_assoc();
                                echo $row['count'];
                                ?>
                            </h3>
                            <p class="stat-label">Total Users</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card success h-100">
                        <div class="card-body">
                            <div class="stat-icon-wrapper">
                                <div class="stat-icon stat-success">
                                    <i class="fas fa-project-diagram"></i>
                                </div>
                            </div>
                            <h3 class="stat-value success-value">
                                <?php
                                $query = "SELECT COUNT(*) as count FROM teams";
                                $result = $conn->query($query);
                                $row = $result->fetch_assoc();
                                echo $row['count'];
                                ?>
                            </h3>
                            <p class="stat-label">Active Teams</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card warning h-100">
                        <div class="card-body">
                            <div class="stat-icon-wrapper">
                                <div class="stat-icon stat-warning">
                                    <i class="fas fa-tasks"></i>
                                </div>
                            </div>
                            <h3 class="stat-value warning-value">
                                <?php
                                $query = "SELECT COUNT(*) as count FROM projects";
                                $result = $conn->query($query);
                                $row = $result->fetch_assoc();
                                echo $row['count'];
                                ?>
                            </h3>
                            <p class="stat-label">Total Projects</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card info h-100">
                        <div class="card-body">
                            <div class="stat-icon-wrapper">
                                <div class="stat-icon stat-info">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <h3 class="stat-value info-value">
                                <?php
                                $query = "SELECT COUNT(*) as count FROM tasks WHERE status != 'verified'";
                                $result = $conn->query($query);
                                $row = $result->fetch_assoc();
                                echo $row['count'];
                                ?>
                            </h3>
                            <p class="stat-label">Active Tasks</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="features-section">
            <div class="section-header">
                <h4 class="section-title">HR Operations</h4>
                <p class="section-subtitle">Access powerful HR management tools and resources to elevate your organization</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="feature-icon stat-primary">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h5 class="feature-title">Workforce Management</h5>
                            <p class="feature-text">Manage user accounts across the organization.</p>
                            <a href="hr_panel.php" class="feature-link">
                                Access Tools <i class="fas fa-arrow-right ms-2 arrow-icon"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="feature-icon stat-success">
                                <i class="fas fa-people-group"></i>
                            </div>
                            <h5 class="feature-title">Team Configuration</h5>
                            <p class="feature-text">Create and structure teams.</p>
                            <a href="create_team.php" class="feature-link">
                                Configure Teams <i class="fas fa-arrow-right ms-2 arrow-icon"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="feature-icon stat-warning">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h5 class="feature-title">Project Analytics</h5>
                            <p class="feature-text">View project performance comparsion,current status and reports.</p>
                            <a href="project_performance.php" class="feature-link">
                                View Analytics <i class="fas fa-arrow-right ms-2 arrow-icon"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="feature-icon stat-info">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h5 class="feature-title">Performance Insights</h5>
                            <p class="feature-text">Review and assess team members contributions.</p>
                            <a href="performance_analysis.php" class="feature-link">
                                Explore Insights <i class="fas fa-arrow-right ms-2 arrow-icon"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>