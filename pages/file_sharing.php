<?php
 session_start();
 require '../includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Sharing</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #3498db;
            --primary-dark: #2980b9;
            --secondary-color: #2c3e50;
            --accent-color: #2ecc71;
            --light-bg: #f8f9fa;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.08);
            --hover-shadow: 0 15px 35px rgba(0,0,0,0.12);
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --border-radius: 12px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, #3a66db, #2952c8);
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 120px;
            display: flex;
            align-items: center;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .navbar-brand {
            font-size: 1.4rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .back-btn:hover {
            color: #ffffff;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(-3px);
        }

        .share-container {
            max-width: 1000px;
            margin: 3rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            margin-bottom: 2.5rem;
            position: relative;
        }
        
        .page-header h1 {
            font-weight: 700;
            color: var(--secondary-color);
            position: relative;
            padding-bottom: 0.75rem;
        }
        
        .page-header h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .share-card {
            background: #ffffff;
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.8rem;
            transition: all 0.4s ease;
            text-decoration: none;
            display: block;
            color: inherit;
            overflow: hidden;
            position: relative;
        }

        .share-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
            color: inherit;
        }

        .share-card.project-file {
            border-left: 5px solid var(--accent-color);
        }
        
        .share-card.project-file:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 5px;
            background: var(--accent-color);
            opacity: 0.7;
        }

        .share-content {
            padding: 1.75rem;
        }

        .share-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            transition: color 0.3s;
        }
        
        .share-card:hover .share-title {
            color: var(--primary-color);
        }

        .share-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.25rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }
        
        .share-card:hover .meta-item {
            color: var(--text-primary);
        }
        
        .meta-item i {
            color: var(--primary-color);
            opacity: 0.8;
        }

        .share-description {
            color: var(--text-secondary);
            margin-bottom: 0;
            line-height: 1.7;
            font-size: 0.95rem;
            transition: color 0.3s;
        }
        
        .share-card:hover .share-description {
            color: var(--text-primary);
        }

        .project-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--accent-color);
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .share-card:hover .project-badge {
            background-color: rgba(46, 204, 113, 0.25);
        }

        .btn-share {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2589c9 100%);
            border: none;
            padding: 0.85rem 1.75rem;
            color: white;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            border-radius: 50px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-share:hover {
            background: linear-gradient(135deg, #2589c9 0%, var(--primary-color) 100%);
            box-shadow: 0 6px 18px rgba(52, 152, 219, 0.4);
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-share i {
            transition: transform 0.3s;
        }
        
        .btn-share:hover i {
            transform: rotate(90deg);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: #ffffff;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }
        
        .empty-state i {
            font-size: 3.5rem;
            color: #e0e0e0;
            margin-bottom: 1.5rem;
        }
        
        .empty-state h3 {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        @media (max-width: 768px) {
            .share-meta {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .btn-share {
                padding: 0.75rem 1.5rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <?php
    if ($_SESSION['role'] != 'TeamMember' && $_SESSION['role'] != 'TeamLead') {
        echo "<script>window.location.href = 'error.php';</script>";
    }

    // Determine dashboard URL based on role
    $dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';

    $team_id = $_SESSION['team_id'];
    $query = "SELECT share_id, title, description, username, created_at, is_project_file 
              FROM file_shares WHERE team_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $shares = [];
    while ($row = $result->fetch_assoc()) {
        $shares[] = $row;
    }
    $stmt->close();
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-share-alt"></i>
                File Sharing
            </div>
            <a href="<?php echo $dashboardUrl; ?>" class="btn-back">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container share-container">
        <div class="d-flex justify-content-between align-items-center page-header">
            <h1 class="h3 mb-0">Team Files</h1>
            <a href="start_file_share.php" class="btn btn-share">
                <i class="fas fa-plus"></i>
                Share New File
            </a>
        </div>

        <?php if (count($shares) > 0): ?>
            <?php foreach ($shares as $share): ?>
                <a href="file_versions.php?share_id=<?= urlencode($share['share_id']) ?>" 
                class="share-card <?= $share['is_project_file'] ? 'project-file' : '' ?>">
                    <div class="share-content">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h3 class="share-title"><?= htmlspecialchars($share['title']) ?></h3>
                            <?php if ($share['is_project_file']): ?>
                                <span class="project-badge">
                                    <i class="fas fa-project-diagram"></i>
                                    Project File
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="share-meta">
                            <span class="meta-item">
                                <i class="far fa-user"></i>
                                <?= htmlspecialchars($share['username']) ?>
                            </span>
                            <span class="meta-item">
                                <i class="far fa-calendar"></i>
                                <?= date('F j, Y', strtotime($share['created_at'])) ?>
                            </span>
                            <span class="meta-item">
                                <i class="far fa-clock"></i>
                                <?= date('g:i A', strtotime($share['created_at'])) ?>
                            </span>
                        </div>
                        
                        <p class="share-description">
                            <?= htmlspecialchars($share['description']) ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-folder-open"></i>
                <h3>No files shared yet</h3>
                <p>Your team hasn't shared any files. Click the button above to be the first one to share a file with your team.</p>
                <a href="start_file_share.php" class="btn btn-share">
                    <i class="fas fa-plus"></i>
                    Share New File
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>