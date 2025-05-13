<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['TeamMember', 'TeamLead'])) {
    echo "<script>window.location.href = 'error.php';</script>";
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $is_project_file = isset($_POST['is_project_file']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $team_id = $_SESSION['team_id'];

    $query = "INSERT INTO file_shares (title, description, team_id, user_id, username, is_project_file) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiisi", $title, $description, $team_id, $user_id, $username, $is_project_file);

    if ($stmt->execute()) {
        echo "<script>window.location.href = 'file_sharing.php';</script>";
    } else {
        $error = "Failed to create file share. Please try again.";
    }
}

// Determine dashboard URL based on role
$dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start File Share | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --text-color: #2c3e50;
            --text-light: #6c757d;
            --bg-light: #f8f9fa;
            --border-color: #e2e8f0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-color);
            line-height: 1.6;
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: 700;
            font-size: 1.5rem;
            margin-right: 2rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.15);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .back-btn i {
            font-size: 0.875rem;
        }

        .form-container {
            max-width: 800px;
            margin: 2.5rem auto;
        }

        .form-card {
            background: #ffffff;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 2.5rem;
        }

        .form-title {
            color: var(--text-color);
            font-weight: 700;
            margin-bottom: 2rem;
            font-size: 1.75rem;
            text-align: center;
            position: relative;
        }

        .form-title::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 2px;
            margin: 0.75rem auto 0;
        }

        .form-label {
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.85rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
        }

        .form-text {
            color: var(--text-light);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background-color: rgba(52, 152, 219, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(52, 152, 219, 0.1);
        }

        .form-check-input {
            width: 1.25rem;
            height: 1.25rem;
            margin-top: 0;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            font-weight: 500;
            cursor: pointer;
        }

        .btn-submit {
            background-color: var(--primary-color);
            border: none;
            color: #ffffff;
            padding: 0.85rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 1rem;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.2);
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(52, 152, 219, 0.25);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-left: 5px solid #f8b4b4;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #b91c1c;
        }

        .alert i {
            font-size: 1.25rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        /* Animation for form elements */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-card > * {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .form-card > *:nth-child(1) { animation-delay: 0.1s; }
        .form-card > *:nth-child(2) { animation-delay: 0.2s; }
        .form-card > *:nth-child(3) { animation-delay: 0.3s; }

        /* Make submit button full width on mobile */
        @media (max-width: 576px) {
            .form-card {
                padding: 1.5rem;
            }
            
            .btn-submit {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <span class="navbar-brand">Start File Share</span>
            <a href="file_sharing.php" class="back-btn">
                <i class="fas fa-arrow-left me-1"></i>
                <span>Back to Files</span>
            </a>
        </div>
    </nav>

    <div class="container form-container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h2 class="form-title">Create New File Share</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required
                           placeholder="Enter a descriptive title for your file share">
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required
                              placeholder="Describe what this file contains and its purpose"></textarea>
                    <div class="form-text">A clear description helps team members understand the content better.</div>
                </div>

                <div class="checkbox-wrapper">
                    <input type="checkbox" class="form-check-input" id="is_project_file" name="is_project_file">
                    <label class="form-check-label" for="is_project_file">
                        Mark as project file
                    </label>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-plus me-2"></i>
                        Create File Share
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>