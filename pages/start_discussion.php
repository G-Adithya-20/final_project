<?php
 session_start(); 
 require '../includes/db_connect.php';
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Discussion</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #8b5cf6;
            --accent: #06b6d4;
            --dark: #111827;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-800: #1f2937;
            --danger: #ef4444;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: var(--text-primary);
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(79, 70, 229, 0.2);
            position: relative;
            overflow: hidden;
        }

        .navbar::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
            transform: rotate(15deg);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
            color: white;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .back-btn {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.6rem 1.25rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .back-btn i {
            transition: transform 0.3s ease;
        }

        .back-btn:hover i {
            transform: translateX(-3px);
        }

        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
        }

        .section-heading {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--gray-800);
            position: relative;
            display: inline-block;
        }

        .section-heading::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40%;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 10px;
        }

        .discussion-card {
            background: white;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .discussion-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.05), rgba(139, 92, 246, 0.05));
            border-bottom: 1px solid var(--gray-200);
            padding: 1.5rem 2rem;
        }

        .card-header h4 {
            color: var(--gray-800);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            color: var(--gray-800);
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: 1px solid var(--gray-300);
            padding: 0.875rem 1.25rem;
            border-radius: 12px;
            background-color: var(--gray-100);
            transition: all 0.3s ease;
            font-size: 0.95rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02) inset;
        }

        .form-control:focus {
            border-color: var(--primary);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--gray-400);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover));
            border: none;
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            border-radius: 12px;
            letter-spacing: 0.3px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-primary i {
            transition: transform 0.3s;
        }

        .btn-primary:hover i {
            transform: rotate(90deg);
        }

        .error-alert {
            background-color: #fef2f2;
            border-left: 4px solid var(--danger);
            color: #b91c1c;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.1);
        }

        .error-alert i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Textarea enhancement */
        textarea.form-control {
            min-height: 160px;
            line-height: 1.6;
        }

        /* Focus states for accessibility */
        .form-control:focus-visible, 
        .btn:focus-visible,
        .back-btn:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .card-body, .card-header {
                padding: 1.5rem;
            }
            
            .back-btn {
                padding: 0.5rem 1rem;
            }
            
            .navbar-brand {
                font-size: 1.125rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Ensure only Team Members or Team Leads can access this page
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['TeamMember', 'TeamLead'])) {
        echo "<script>window.location.href = 'error.php';</script>";
    }

    // Initialize error variable
    $error = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Collect form data
        $issue = $_POST['issue'];
        $description = $_POST['description'];
        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        $team_id = $_SESSION['team_id'];

        // Prepare SQL query
        $query = "
            INSERT INTO discussions (team_id, issue, description, user_id, username, started_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issis", $team_id, $issue, $description, $user_id, $username);

        if ($stmt->execute()) {
            echo "<script>window.location.href = 'discussion.php';</script>";
        } else {
            $error = "Failed to start the discussion. Please try again.";
        }
    }

    // Determine dashboard URL based on role
    $dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <div class="navbar-brand">
                <i class="fas fa-plus-circle me-2"></i>
                Start New Discussion
            </div>
            <a href="discussion.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Discussions
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="page-container animate-fade-in">
        <h2 class="section-heading">Create Discussion</h2>
        
        <div class="discussion-card">
            <div class="card-header">
                <h4><i class="fas fa-comments me-2"></i>New Team Discussion</h4>
            </div>
            
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="error-alert mb-4" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="start_discussion.php" method="POST">
                    <div class="mb-4">
                        <label for="issue" class="form-label">Issue Title</label>
                        <input type="text" class="form-control" id="issue" name="issue" 
                            placeholder="Enter a clear, concise title for the discussion" required>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                            rows="6" placeholder="Describe the issue or topic in detail..." required></textarea>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i>Create Discussion
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>