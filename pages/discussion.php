<?php
 session_start(); 
 require '../includes/db_connect.php';
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Discussions</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --secondary: #2b2d42;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
            --text-primary: #2b2d42;
            --text-secondary: #64748b;
            --border-radius: 12px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .navbar {
            background-color: var(--card-bg);
            box-shadow: var(--card-shadow);
            padding: 0.75rem 0;
        }

        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
        }

        .back-btn {
            color: var(--secondary);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 0.5rem 0.75rem;
            border-radius: var(--border-radius);
        }

        .back-btn:hover {
            color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
        }

        .back-btn i {
            font-size: 0.875rem;
        }

        .page-header {
            margin-bottom: 2rem;
            position: relative;
        }

        .page-title {
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-weight: 400;
            font-size: 0.95rem;
        }

        .start-discussion-btn {
            background-color: var(--primary);
            border: none;
            border-radius: var(--border-radius);
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.15);
            transition: all 0.3s ease;
        }

        .start-discussion-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(67, 97, 238, 0.2);
        }

        .start-discussion-btn i {
            transition: transform 0.3s ease;
        }

        .start-discussion-btn:hover i {
            transform: rotate(90deg);
        }

        .discussions-container {
            margin-top: 1.5rem;
        }

        .discussion-card {
            background: var(--card-bg);
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 1.25rem;
            overflow: hidden;
            position: relative;
        }

        .discussion-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.07), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .discussion-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: var(--primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .discussion-card:hover::before {
            opacity: 1;
        }

        .card-body {
            padding: 1.5rem;
        }

        .discussion-title {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 0.75rem;
            transition: color 0.3s ease;
        }

        .discussion-card:hover .discussion-title {
            color: var(--primary);
        }

        .discussion-meta {
            color: var(--text-secondary);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: inline-flex;
            align-items: center;
            margin-right: 1.25rem;
        }

        .meta-item i {
            margin-right: 0.4rem;
            font-size: 0.875rem;
            color: var(--primary);
        }

        .discussion-description {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 0;
        }

        .loading-spinner {
            display: none;
            width: 3rem;
            height: 3rem;
        }

        .no-discussions {
            text-align: center;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            color: var(--text-secondary);
        }

        .no-discussions i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
            opacity: 0.7;
        }

        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --background: #f8f9fc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
            line-height: 1.6;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            background: linear-gradient(135deg, #3a66db, #2952c8);
            height: 120px;
            padding: 2.5rem 0;
            display: flex;
            align-items: center;
        }

        .navbar-brand {
            font-size: 1.6rem;
            font-weight: 600;
            color: white;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <?php 
    if ($_SESSION['role'] != 'TeamMember' && $_SESSION['role'] != 'TeamLead') {
        echo "<script>window.location.href = 'error.php';</script>";
    }
    
    $team_id = $_SESSION['team_id'];
    $query = "SELECT discussion_id, issue, username, started_at, description FROM discussions WHERE team_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $discussions = [];
    while ($row = $result->fetch_assoc()) {
        $discussions[] = $row;
    }
    $stmt->close();
    
    // Determine dashboard URL based on role
    $dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-comments me-2"></i>
                Team Discussions
            </div>
            <a href="teamlead_dashboard.php" class="btn-back">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2 class="page-title">Recent Discussions</h2>
                <p class="page-subtitle">Connect and collaborate with your team members</p>
            </div>
            <a href="start_discussion.php" class="btn btn-primary start-discussion-btn">
                <i class="fas fa-plus me-2"></i>Start Discussion
            </a>
        </div>

        <!-- Loading Spinner -->
        <div class="text-center loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Discussions List -->
        <div class="discussions-container">
            <?php if (empty($discussions)): ?>
                <div class="no-discussions">
                    <i class="fas fa-comments mb-3 d-block"></i>
                    <h4>No discussions yet</h4>
                    <p>Be the first to start a discussion with your team!</p>
                </div>
            <?php else: ?>
                <?php foreach ($discussions as $discussion): ?>
                    <a href="chat_page.php?discussion_id=<?= urlencode($discussion['discussion_id']) ?>" 
                       class="text-decoration-none">
                        <div class="card discussion-card">
                            <div class="card-body">
                                <h5 class="discussion-title">
                                    <?= htmlspecialchars($discussion['issue']) ?>
                                </h5>
                                <div class="discussion-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($discussion['username']) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="far fa-calendar-alt"></i>
                                        <span><?= htmlspecialchars($discussion['started_at']) ?></span>
                                    </div>
                                </div>
                                <p class="discussion-description">
                                    <?= htmlspecialchars($discussion['description']) ?>
                                </p>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>