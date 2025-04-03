<?php 
session_start(); 
require '../includes/db_connect.php';

// Ensure only team members can access this page 
if ($_SESSION['role'] != 'Manager' ) {
    echo "<script>window.location.href = 'error.php';</script>";
}

// Get the team_id from session
$team_id = $_SESSION['team_id'];

// Prepare SQL query to fetch discussions for the current team
$query = "SELECT discussion_id, issue, username, started_at, description FROM discussions";
$stmt = $conn->prepare($query);
// $stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch discussions from the database
$discussions = [];
while ($row = $result->fetch_assoc()) {
    $discussions[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Discussions</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --primary-light: #6366f1;
            --secondary: #3b82f6;
            --background: #f9fafb;
            --card-bg: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --accent: #10b981;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
            padding-top: 2rem;
            padding-bottom: 2rem;
            color: var(--text-dark);
        }

        .page-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .page-header {
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            padding: 2.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }

        .page-title {
            color: white;
            font-weight: 700;
            margin: 0;
            font-size: 1.75rem;
            position: relative;
            z-index: 1;
        }

        .back-btn {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            backdrop-filter: blur(5px);
            position: relative;
            z-index: 1;
        }

        .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .discussions-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .discussion-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--secondary);
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: var(--text-dark);
            display: block;
            overflow: hidden;
        }

        .discussion-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            color: var(--text-dark);
            border-left-color: var(--primary);
        }

        .discussion-body {
            padding: 1.5rem;
        }

        .discussion-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .discussion-title::before {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: var(--secondary);
            border-radius: 50%;
        }

        .discussion-meta {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .discussion-description {
            color: var(--text-light);
            margin: 0;
            line-height: 1.6;
            /* Removed text truncation properties */
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 2rem 1.5rem;
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .discussion-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .discussions-list a {
            animation: fadeIn 0.5s ease forwards;
        }

        .discussions-list a:nth-child(1) { animation-delay: 0.1s; }
        .discussions-list a:nth-child(2) { animation-delay: 0.2s; }
        .discussions-list a:nth-child(3) { animation-delay: 0.3s; }
        .discussions-list a:nth-child(4) { animation-delay: 0.4s; }
        .discussions-list a:nth-child(5) { animation-delay: 0.5s; }
        .discussions-list a:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Team Discussions</h1>
            <a href="manager_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>

        <div class="discussions-list">
            <?php foreach ($discussions as $discussion): ?>
                <a href="manager_chat.php?discussion_id=<?= urlencode($discussion['discussion_id']) ?>" 
                   class="discussion-card">
                    <div class="discussion-body">
                        <h2 class="discussion-title">
                            <?= htmlspecialchars($discussion['issue']) ?>
                        </h2>
                        <div class="discussion-meta">
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <span><?= htmlspecialchars($discussion['username']) ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?= htmlspecialchars($discussion['started_at']) ?></span>
                            </div>
                        </div>
                        <p class="discussion-description">
                            <?= htmlspecialchars($discussion['description']) ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>