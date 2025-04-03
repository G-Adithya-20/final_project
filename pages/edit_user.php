<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'HR') {
    echo "<script>window.location.href = 'error.php';</script>";
}

if (!isset($_GET['id'])) {
    echo "<script>window.location.href = 'hr_management.php';</script>";
}

$user_id = $_GET['id'];
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "<script>window.location.href = 'hr_management.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Profile</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3949ab;
            --primary-light: #e8eaf6;
            --secondary-color: #3f37c9;
            --accent-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4CAF50;
            --danger-color: #f44336;
            --border-color: #e0e0e0;
            --card-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #444;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Navigation styling */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 2rem;
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-brand i {
            font-size: 1.8rem;
        }
        
        .btn-back {
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border: 2px solid rgba(255,255,255,0.2);
            background-color: rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-back:hover {
            background-color: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        /* Main content area */
        .main-content {
            margin-top: 90px;
            padding: 2rem 0;
            flex: 1;
        }
        
        /* Card styling */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 2.5rem;
        }
        
        .card:hover {
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
        }
        
        .card-header h4 {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        /* User profile section */
        .user-profile {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 0;
            background-color: var(--primary-light);
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .avatar-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .avatar-circle i {
            font-size: 2.5rem;
            color: var(--primary-color);
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1.4rem;
            margin-bottom: 0.3rem;
        }
        
        .user-email {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .user-role {
            display: inline-block;
            padding: 0.4rem 1.2rem;
            border-radius: 50px;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        /* Form controls */
        .form-label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.8rem;
        }
        
        .form-control, .form-select {
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.15);
        }
        
        .input-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group-text {
            background-color: white;
            border-color: var(--border-color);
        }
        
        /* Button styling */
        .btn {
            border-radius: 50px;
            padding: 0.8rem 1.8rem;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }
        
        .btn-secondary {
            background-color: #eaecef;
            border-color: #eaecef;
            color: #555;
        }
        
        .btn-secondary:hover {
            background-color: #d2d6dc;
            border-color: #d2d6dc;
            color: #333;
            transform: translateY(-2px);
        }
        
        .icon-circle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            border-radius: 50%;
            height: 46px;
            width: 46px;
            margin-right: 15px;
        }
        
        .icon-circle i {
            font-size: 1.2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 2rem;
        }
        
        /* Custom field styling */
        .custom-field {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .custom-field .field-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .custom-field .form-control,
        .custom-field .form-select {
            padding-left: 45px;
        }
        
        /* Page header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0;
        }
        
        /* Footer styling */
        .footer {
            background-color: white;
            padding: 1.5rem 0;
            text-align: center;
            margin-top: auto;
            border-top: 1px solid var(--border-color);
        }
        
        .footer p {
            color: #777;
            margin-bottom: 0;
        }
        
        /* Custom helpers */
        .section-divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 2rem 0;
        }
        
        /* User info cards */
        .info-card {
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            background-color: white;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .info-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .info-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        
        .info-card-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0;
        }
        
        .info-card-content {
            color: #666;
        }
        
        .bg-primary-light {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .bg-warning-light {
            background-color: rgba(255, 152, 0, 0.1);
            color: #ff9800;
        }
        
        .bg-danger-light {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--danger-color);
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: #6c757d;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .navbar-container {
                padding: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem 0;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .user-profile {
                padding: 1.5rem 0;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .custom-field .field-icon {
                left: 12px;
            }
            
            .custom-field .form-control,
            .custom-field .form-select {
                padding-left: 40px;
            }
        }

        /* Enhanced Info Card Styles */
        .info-section {
            border: 3px solid rgba(67, 97, 238, 0.2);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            background: #fff;
        }

        .info-section-title {
            position: absolute;
            top: -12px;
            left: 20px;
            background: #fff;
            padding: 0 10px;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .info-card {
            padding: 1rem;
            background: rgba(67, 97, 238, 0.03);
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .info-label {
            font-size: 0.75rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-size: 0.9rem;
            color: #2d3748;
            font-weight: 500;
        }

        .form-control {
            font-size: 0.9rem;
        }

        .form-label {
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-building"></i> Company Portal
            </a>
            <a href="hr_panel.php" class="btn-back btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Back to HR Panel
            </a>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="hr_panel.php">HR Management</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit User</li>
                </ol>
            </nav>
            
            <div class="page-header">
                <h1 class="page-title">Edit User Profile</h1>
            </div>
            
            <div class="row">
                <!-- Left column with user information -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-body p-0">
                            <div class="user-profile">
                                <div class="avatar-circle">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h4 class="user-name"><?= htmlspecialchars($user['username']) ?></h4>
                                <p class="user-email"><?= htmlspecialchars($user['email']) ?></p>
                                <span class="user-role">
                                    <i class="fas fa-user-tag me-1"></i>
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </div>
                            
                            <div class="px-4 pb-4">
                                <h5 class="mb-3">User Information</h5>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">User ID</span>
                                    <span class="fw-medium">#<?= htmlspecialchars($user['user_id']) ?></span>
                                </div>
                                
                                
                            </div>
                        </div>
                    </div>
                    
                    <!-- Information cards -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-card-icon bg-primary-light">
                                <i class="fas fa-info"></i>
                            </div>
                            <h5 class="info-card-title">System Roles</h5>
                        </div>
                        <p class="info-card-content">
                            User roles determine access levels and permissions throughout the system.
                        </p>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-card-icon bg-warning-light">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h5 class="info-card-title">Password Notice</h5>
                        </div>
                        <p class="info-card-content">
                            Leave the password field empty if you don't want to change the current password.
                        </p>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-card-header">
                            <div class="info-card-icon bg-danger-light">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h5 class="info-card-title">Security</h5>
                        </div>
                        <p class="info-card-content">
                            All user changes are logged for security and audit purposes.
                        </p>
                    </div>
                </div>
                
                <!-- Right column with edit form -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="icon-circle">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <h4>Edit User Details</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="../actions/user_action.php" class="needs-validation" novalidate>
                                <div class="info-section">
                                    <div class="info-section-title">User Information</div>
                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['user_id']) ?>">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-label">Username</div>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </span>
                                                    <input type="text" class="form-control border-start-0" id="username" name="username" 
                                                           value="<?= htmlspecialchars($user['username']) ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-label">Email Address</div>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0">
                                                        <i class="fas fa-envelope text-primary"></i>
                                                    </span>
                                                    <input type="email" class="form-control border-start-0" id="email" name="email" 
                                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            
                                <div class="info-section">
                                    <div class="info-section-title">Role & Security</div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-label">User Role</div>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0">
                                                        <i class="fas fa-user-tag text-primary"></i>
                                                    </span>
                                                    <select class="form-select border-start-0" id="role" name="role" required>
                                                        <?php foreach(['HR', 'TeamLead', 'Manager', 'TeamMember'] as $role): ?>
                                                            <option value="<?= $role ?>" <?= $user['role'] == $role ? 'selected' : '' ?>>
                                                                <?= $role ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="info-card">
                                                <div class="info-label">New Password</div>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-white border-end-0">
                                                        <i class="fas fa-lock text-primary"></i>
                                                    </span>
                                                    <input type="password" class="form-control border-start-0" id="password" name="password"
                                                           placeholder="Leave blank to keep current">
                                                </div>
                                                <small class="text-muted mt-1 d-block">
                                                    <i class="fas fa-info-circle"></i> Minimum 4 characters
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            
                                <div class="action-buttons">
                                    <a href="hr_panel.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" name="update" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="container">
            <p>© <?= date('Y') ?> Company Portal | HR Management System</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Form validation script
    (function () {
        'use strict'
        
        // Fetch all forms with needs-validation class
        var forms = document.querySelectorAll('.needs-validation')
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
    })()
    </script>
</body>
</html>