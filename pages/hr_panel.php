<?php
session_start();
require '../includes/db_connect.php';

// Check for HR role
if ($_SESSION['role'] != 'HR') {
    echo "<script>window.location.href = 'error.php';</script>";
}

// Add debugging
$user_query = "SELECT user_id, username, email, role FROM users";
$user_result = $conn->query($user_query);

// Debug output
if ($user_result) {
    $first_row = $user_result->fetch_assoc();
    echo "<!-- Debug Output: ";
    print_r($first_row);
    echo " -->";
    // Reset pointer
    $user_result->data_seek(0);
}

// Add error checking
if (!$user_result) {
    die("Query failed: " . $conn->error);
}

// Handle success messages
if (isset($_SESSION['message'])) {
    echo "<script>alert('" . $_SESSION['message'] . "');</script>";
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --danger-color: #e63946;
            --warning-color: #fca311;
            --success-color: #52b788;
            --gray-color: #adb5bd;
            --card-shadow: 0 8px 24px rgba(149, 157, 165, 0.2);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: var(--dark-color);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
        }
        
        .navbar-brand {
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .back-btn {
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .card-header h4 {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0;
        }
        
        .card-body {
            padding: 1.8rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.7rem 1rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .btn {
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var (--warning-color);
            color: white;
        }
        
        .btn-warning:hover {
            background-color: #f8a100;
            border-color: #f8a100;
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #d90429;
            border-color: #d90429;
        }
        
        .btn-sm {
            padding: 0.35rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table th {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
            padding: 1rem;
            border: none;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .table tr:hover {
            background-color: rgba(67, 97, 238, 0.03);
        }
        
        .badge {
            padding: 0.5rem 0.8rem;
            font-weight: 500;
            border-radius: 6px;
        }
        
        .badge.bg-primary {
            background-color: rgba(67, 97, 238, 0.1) !important;
            color: var(--primary-color);
        }
        
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btns .btn {
            border-radius: 6px;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background-color: #fff3f3;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
            color: #dc3545;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .validation-error {
            padding: 1rem;
            background-color: #fff3f3;
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }

        .validation-error:last-child {
            margin-bottom: 0;
        }

        .validation-error h6 {
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .validation-error ul {
            color: #666;
            margin-bottom: 0;
        }

        .validation-error ul li {
            margin-bottom: 0.25rem;
        }

        .validation-error ul li:last-child {
            margin-bottom: 0;
        }

        .modal-footer .btn {
            min-width: 100px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="hr_dashboard.php">
                <i class="fas fa-building me-2"></i>Company Name
            </a>
            <a href="hr_dashboard.php" class="btn back-btn text-white">
                <i class="fas fa-home me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-user-plus me-2 text-primary"></i>Create New User</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../actions/user_action.php" class="needs-validation" novalidate id="addUserForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-user text-primary"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" id="username" name="username" required placeholder="Enter username">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-envelope text-primary"></i>
                                        </span>
                                        <input type="email" class="form-control border-start-0" id="email" name="email" required placeholder="Enter email address">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-id-badge text-primary"></i>
                                        </span>
                                        <select class="form-select border-start-0" id="role" name="role" required>
                                            <option value="">Select Role</option>
                                            <option value="HR">HR</option>
                                            <option value="TeamLead">Team Lead</option>
                                            <option value="Manager">Manager</option>
                                            <option value="TeamMember">Team Member</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- <div class="col-md-6 mb-3">
                                    <label for="domain" class="form-label">Domain</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-code-branch text-primary"></i>
                                        </span>
                                        <select class="form-select border-start-0" id="domain" name="domain" required>
                                            <option value="">Select Domain</option>
                                            <option value="Frontend">Frontend Development</option>
                                            <option value="Backend">Backend Development</option>
                                            <option value="FullStack">Full Stack Development</option>
                                            <option value="DevOps">DevOps</option>
                                            <option value="Quality Assurance">Quality Assurance</option>
                                            <option value="UI/UX">UI/UX</option>
                                            <option value="Mobile Development">Mobile Development</option>
                                            <option value="Data Science">Data Science</option>
                                        </select>
                                    </div>
                                </div> -->
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="fas fa-lock text-primary"></i>
                                        </span>
                                        <input type="password" class="form-control border-start-0" id="password" name="password" required placeholder="Enter password">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="create" class="btn btn-primary mt-2">
                                <i class="fas fa-plus-circle me-2"></i>Create User
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>User Management</h4>
                        <div class="input-group" style="width: 300px;">
                            <input type="text" class="form-control" placeholder="Search users..." id="userSearch">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-primary"></i>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag me-2"></i>ID</th>
                                        <th><i class="fas fa-user me-2"></i>Username</th>
                                        <th><i class="fas fa-envelope me-2"></i>Email</th>
                                        <th><i class="fas fa-id-badge me-2"></i>Role</th>
                                        <!-- <th><i class="fas fa-code-branch me-2"></i>Domain</th> -->
                                        <th><i class="fas fa-cog me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    while ($user = $user_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['user_id'] ?? '') ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <span class="text-primary"><?= strtoupper(substr($user['username'] ?? '', 0, 1)) ?></span>
                                                    </div>
                                                    <?= htmlspecialchars($user['username'] ?? '') ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?= htmlspecialchars($user['role'] ?? '') ?></span>
                                            </td>
       
                                            <td>
                                                <div class="action-btns">
                                                    <a href="edit_user.php?id=<?= $user['user_id'] ?? '' ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $user['user_id'] ?? 0 ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this HTML just before the closing </body> tag -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>Validation Error
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="errorModalBody">
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmDelete(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = `../actions/user_action.php?delete=${userId}`;
            }
        }
        
        // Simple search functionality
        document.getElementById('userSearch').addEventListener('keyup', function() {
            let input = this.value.toLowerCase();
            let rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                if(text.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
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

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('addUserForm');
            const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value;
                    const email = document.getElementById('email').value;
                    let isValid = true;
                    let errorMessages = [];

                    // Enhanced username validation
                    const usernameRegex = /^[A-Z][a-z]{2,}$/;
                    if (!usernameRegex.test(username)) {
                        isValid = false;
                        errorMessages.push(`
                            <div class="validation-error">
                                <h6 class="text-danger"><i class="fas fa-user-times me-2"></i>Username Requirements:</h6>
                                <ul class="mb-0 ps-4">
                                    <li>Must start with a capital letter</li>
                                    <li>Followed by lowercase letters only</li>
                                    <li>No numbers or special characters allowed</li>
                                    <li>Minimum 3 characters</li>
                                </ul>
                            </div>
                        `);
                    }

                    // Email validation
                    if (!email.includes('@')) {
                        isValid = false;
                        errorMessages.push(`
                            <div class="validation-error">
                                <h6 class="text-danger"><i class="fas fa-envelope-times me-2"></i>Email Error:</h6>
                                <p class="mb-0 ps-4">Email must contain @ symbol</p>
                            </div>
                        `);
                    }

                    if (!isValid) {
                        e.preventDefault();
                        document.getElementById('errorModalBody').innerHTML = errorMessages.join('<hr class="my-3">');
                        errorModal.show();
                    }
                });
            }
        });
    </script>
</body>
</html>