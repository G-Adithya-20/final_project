<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_stmt->execute()) {
                    $message = "Password successfully updated!";
                } else {
                    $error = "Error updating password. Please try again.";
                }
                $update_stmt->close();
            } else {
                $error = "New password must be at least 6 characters long.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
    $stmt->close();
}

// Determine back button URL based on role
$back_url = '';
switch(strtolower($_SESSION['role'])) {
    case 'manager':
        $back_url = 'manager_dashboard.php';
        break;
    case 'teamlead':
        $back_url = 'teamlead_dashboard.php';
        break;
    case 'teammember':
        $back_url = 'teammember_dashboard.php';
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Team Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #34495e;
            --background-color: #f4f7f6;
            --text-color: #2c3e50;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .auth-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f6f8f9 0%, #e5ebee 100%);
            padding: 2rem 0;
        }

        .password-card {
            max-width: 500px;
            width: 100%;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: white;
        }

        .password-card-header {
            background: linear-gradient(to right, var(--primary-color), #3498db);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }

        .password-card-header h2 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .password-card-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #e0e4e7;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }

        .input-group-text {
            background-color: transparent;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(to right, var(--primary-color), #3498db);
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(74, 144, 226, 0.3);
        }

        .password-requirements {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-top: 0.5rem;
        }

        .alert {
            border-radius: 8px;
        }

        @media (max-width: 576px) {
            .password-card {
                margin: 1rem;
            }
        }

        .password-card-header a {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .password-card-header a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="password-card">
            <div class="password-card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-key me-2"></i>Change Password</h2>
                    <a href="login.php" class="text-white text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to Login
                    </a>
                </div>
            </div>
            <div class="password-card-body">
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?= $message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="current_password" class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                            <button class="btn" type="button" onclick="togglePassword('current_password')">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="new_password" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <button class="btn" type="button" onclick="togglePassword('new_password')">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-requirements">
                            Password must be at least 6 characters long
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <button class="btn" type="button" onclick="togglePassword('confirm_password')">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-lock me-2"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>