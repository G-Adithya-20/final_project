<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'Manager') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $is_project_file = isset($_POST['is_project_file']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    $query = "INSERT INTO file_shares (title, description, user_id, username, is_project_file) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $title, $description, $user_id, $username, $is_project_file);

    if ($stmt->execute()) {
        // header("Location: manager_file_sharing.php");
        // exit;
        echo "<script>window.location.href = 'manager_file_sharing.php';</script>";
    } else {
        $error = "Failed to create file share. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Manager File | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --dark: #1d3557;
            --light: #f8f9fa;
            --white: #ffffff;
            --gray: #6c757d;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f5ff;
            background-image: linear-gradient(135deg, #f5f7ff 0%, #e4ebff 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%234361ee' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            z-index: -1;
        }

        .navbar {
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: relative;
            z-index: 100;
        }

        .navbar .back-btn {
            color: var(--white);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            overflow: hidden;
        }

        .navbar .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
            border-radius: 8px;
        }

        .navbar .back-btn:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .navbar .back-btn:hover {
            color: var(--white);
        }

        .navbar-brand {
            color: var(--white);
            font-weight: 600;
            margin-left: 1rem;
            position: relative;
            font-size: 1.25rem;
        }

        .navbar-brand::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 40%;
            height: 2px;
            background-color: var(--white);
            transition: width 0.3s ease;
        }

        .navbar-brand:hover::after {
            width: 100%;
        }

        .main-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 1rem;
            position: relative;
        }

        .main-container::before {
            content: "";
            position: absolute;
            top: -80px;
            right: -80px;
            width: 200px;
            height: 200px;
            background-color: rgba(67, 97, 238, 0.07);
            border-radius: 50%;
            z-index: -1;
        }

        .main-container::after {
            content: "";
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 250px;
            height: 250px;
            background-color: rgba(67, 97, 238, 0.05);
            border-radius: 50%;
            z-index: -1;
        }

        .card {
            background: var(--white);
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(67, 97, 238, 0.15);
        }

        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--success) 100%);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.75rem;
            position: relative;
        }

        .card-title {
            color: var(--dark);
            font-weight: 600;
            font-size: 1.75rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title::before {
            content: "\f382";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: var(--primary);
            font-size: 1.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            color: var(--dark);
            font-weight: 500;
            margin-bottom: 0.75rem;
            display: block;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .form-control {
            border: 2px solid rgba(0,0,0,0.08);
            border-radius: 12px;
            padding: 0.875rem 1.25rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            background-color: var(--white);
        }

        .form-control:focus + .form-label {
            color: var(--primary);
        }

        textarea.form-control {
            min-height: 140px;
            resize: vertical;
        }

        .form-check {
            padding-left: 2.25rem;
            margin-top: 1.5rem;
            position: relative;
        }

        .form-check-input {
            border: 2px solid rgba(0,0,0,0.15);
            width: 1.35rem;
            height: 1.35rem;
            margin-left: -2.25rem;
            margin-top: 0.15rem;
            cursor: pointer;
            border-radius: 6px;
            position: relative;
            transition: all 0.2s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
        }

        .form-check-input:checked::before {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: var(--white);
            font-size: 0.75rem;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .form-check-label {
            cursor: pointer;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s ease;
            font-size: 0.95rem;
        }

        .form-check-input:checked ~ .form-check-label {
            color: var(--primary);
        }

        .btn-submit {
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            padding: 1rem 2.5rem;
            font-weight: 500;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--white);
            position: relative;
            overflow: hidden;
            z-index: 1;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, var(--primary-light) 0%, var(--primary) 100%);
            opacity: 0;
            z-index: -1;
            transition: opacity 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
            color: var(--white);
        }

        .btn-submit:hover::before {
            opacity: 1;
        }

        .btn-submit:active {
            transform: translateY(1px);
        }

        .btn-submit i {
            font-size: 1.1rem;
            transition: transform 0.3s ease;
        }

        .btn-submit:hover i {
            transform: translateY(-3px);
        }

        .error-alert {
            background-color: #fff5f5;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.1);
            animation: fadeIn 0.3s ease;
        }

        .error-alert i {
            font-size: 1.5rem;
        }

        .form-floating {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-floating .form-control {
            height: calc(3.5rem + 2px);
            padding: 1rem 1.25rem;
        }

        .form-floating textarea.form-control {
            height: auto;
        }

        .form-floating label {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            padding: 1rem 1.25rem;
            pointer-events: none;
            border: 1px solid transparent;
            transform-origin: 0 0;
            transition: opacity .1s ease-in-out,transform .1s ease-in-out;
            color: #6c757d;
        }

        .form-floating .form-control:focus,
        .form-floating .form-control:not(:placeholder-shown) {
            padding-top: 1.625rem;
            padding-bottom: .625rem;
        }

        .form-floating .form-control:focus ~ label,
        .form-floating .form-control:not(:placeholder-shown) ~ label {
            opacity: .65;
            transform: scale(.85) translateY(-.5rem) translateX(.15rem);
            color: var(--primary);
        }

        .form-floating textarea.form-control:focus ~ label,
        .form-floating textarea.form-control:not(:placeholder-shown) ~ label {
            transform: scale(.85) translateY(-1rem) translateX(.15rem);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .floating-label {
            position: absolute;
            pointer-events: none;
            left: 1.25rem;
            top: 0.875rem;
            transition: 0.2s ease all;
            color: #6c757d;
        }

        .form-control:focus ~ .floating-label,
        .form-control:not(:placeholder-shown) ~ .floating-label {
            top: -0.5rem;
            left: 1rem;
            font-size: 0.75rem;
            opacity: 1;
            background: white;
            padding: 0 0.5rem;
            color: var(--primary);
            font-weight: 500;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            right: 1.25rem;
            transform: translateY(-50%);
            color: #adb5bd;
            transition: color 0.3s ease;
        }

        .form-control:focus ~ .input-icon {
            color: var(--primary);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .was-validated .form-control:invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
        }

        .was-validated .form-control:valid:focus {
            border-color: #198754;
            box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.1);
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1.5rem auto;
            }
            
            .card {
                border-radius: 12px;
            }
            
            .card-header {
                padding: 1.5rem;
            }
            
            .card-title {
                font-size: 1.5rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }

            .btn-submit {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animated background shape */
        .bg-shape {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(67, 97, 238, 0.05) 0%, rgba(76, 201, 240, 0.05) 100%);
            animation: float 15s ease-in-out infinite;
            z-index: -1;
        }
        
        .bg-shape:nth-child(1) {
            top: -150px;
            right: -150px;
            animation-delay: 0s;
        }
        
        .bg-shape:nth-child(2) {
            bottom: -150px;
            left: -150px;
            width: 250px;
            height: 250px;
            animation-delay: -5s;
        }
        
        .bg-shape:nth-child(3) {
            top: 50%;
            right: -100px;
            width: 200px;
            height: 200px;
            animation-delay: -10s;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(10px, 10px) rotate(2deg); }
            50% { transform: translate(0, 15px) rotate(0deg); }
            75% { transform: translate(-10px, 5px) rotate(-2deg); }
            100% { transform: translate(0, 0) rotate(0deg); }
        }
    </style>
</head>
<body>
    <!-- Animated background shapes -->
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a href="manager_file_sharing.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Repository</span>
            </a>
            <span class="navbar-brand">File Upload</span>
        </div>
    </nav>

    <div class="container main-container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Upload New File</h1>
            </div>
            
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="error-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="form-group">
                        <input type="text" 
                               class="form-control" 
                               id="title" 
                               name="title" 
                               required
                               placeholder=" " 
                               autocomplete="off">
                        <label for="title" class="floating-label">File Title</label>
                        <i class="fas fa-file-alt input-icon"></i>
                    </div>

                    <div class="form-group">
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  required
                                  placeholder=" "
                                  rows="5"></textarea>
                        <label for="description" class="floating-label">Description</label>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" 
                               class="form-check-input" 
                               id="is_project_file" 
                               name="is_project_file">
                        <label class="form-check-label" for="is_project_file">
                            Mark as Project File
                        </label>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-upload"></i>
                            Create File Share
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
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