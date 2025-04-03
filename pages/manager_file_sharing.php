<?php
session_start();
require '../includes/db_connect.php';

// Ensure only managers can access this page
if ($_SESSION['role'] != 'Manager') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

// Prepare SQL query to fetch manager's file shares
$query = "SELECT share_id, title, description, username, created_at, is_project_file, team_id 
          FROM file_shares 
          WHERE (team_id IS NULL AND user_id = ?) 
             OR is_project_file = 1;
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$shares = [];
while ($row = $result->fetch_assoc()) {
    $shares[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager File Repository | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-bg: #f8fafc;
            --card-shadow: 0 10px 20px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: #444;
            overflow-x: hidden;
        }

        /* Header & Navigation */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1rem 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .navbar .back-btn {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        .navbar .back-btn:hover {
            color: #ffffff;
            background-color: rgba(255,255,255,0.1);
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: 700;
            margin-left: 1rem;
            letter-spacing: 0.5px;
        }

        /* Main Content */
        .main-container {
            max-width: 1300px;
            margin: 2.5rem auto;
            padding: 0 2rem;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .header-actions::after {
            content: '';
            position: absolute;
            bottom: -1.25rem;
            left: 0;
            width: 100%;
            height: 1px;
            background: linear-gradient(to right, rgba(67, 97, 238, 0.3), rgba(67, 97, 238, 0.01));
        }

        .page-title {
            color: var(--secondary-color);
            font-weight: 700;
            margin: 0;
            position: relative;
            padding-left: 1rem;
            display: flex;
            align-items: center;
        }
        
        .page-title::before {
            content: '';
            width: 4px;
            height: 100%;
            background: var(--primary-color);
            position: absolute;
            left: 0;
            border-radius: 4px;
        }

        .btn-upload {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.25);
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.35);
            background: linear-gradient(135deg, #3a56e4, #2f0896);
        }

        /* File Grid */
        .share-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2rem;
        }

        .share-card {
            background: #ffffff;
            border-radius: 12px;
            border: none;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .share-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: #e0e0e0;
            z-index: 1;
        }

        .share-card.manager-file::before {
            background: linear-gradient(to right, var(--danger-color), #ff8a80);
        }

        .share-card.project-file::before {
            background: linear-gradient(to right, var(--success-color), #66ff99);
        }

        .share-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }

        .share-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }

        .share-content {
            padding: 1.75rem;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .share-title {
            color: #333;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            line-height: 1.4;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .share-title::before {
            content: '\f15b';
            font-family: 'Font Awesome 6 Free';
            font-weight: 400;
            color: var(--primary-color);
            font-size: 1rem;
        }

        .share-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 1.25rem;
            line-height: 1.6;
            flex-grow: 1;
        }

        .share-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .meta-item i {
            color: var(--primary-color);
            opacity: 0.8;
        }

        .badge-custom {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .badge-custom:hover {
            transform: translateY(-2px);
        }

        .badge-manager {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .badge-project {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin: 2rem auto;
            max-width: 600px;
        }

        .empty-state i {
            font-size: 5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .share-card {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .share-grid .share-card:nth-child(1) { animation-delay: 0.1s; }
        .share-grid .share-card:nth-child(2) { animation-delay: 0.2s; }
        .share-grid .share-card:nth-child(3) { animation-delay: 0.3s; }
        .share-grid .share-card:nth-child(4) { animation-delay: 0.4s; }
        .share-grid .share-card:nth-child(5) { animation-delay: 0.5s; }
        .share-grid .share-card:nth-child(6) { animation-delay: 0.6s; }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-container {
                padding: 0 1rem;
            }
            
            .header-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .btn-upload {
                width: 100%;
                justify-content: center;
            }
            
            .share-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a href="manager_dashboard.php" class="back-btn">
                <i class="fas fa-home me-2"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="navbar-brand"><i class="fas fa-folder-open me-2"></i>File Repository</span>
        </div>
    </nav>

    <div class="container main-container">
        <div class="header-actions">
            <h1 class="page-title">Manager File Repository</h1>
            <a href="manager_start_file_share.php" class="btn btn-upload">
                <i class="fas fa-upload"></i>
                Upload New File
            </a>
        </div>

        <?php if (empty($shares)): ?>
            <div class="empty-state">
                <i class="fas fa-file-upload"></i>
                <h3>No Files Shared Yet</h3>
                <p>Your repository is empty. Start sharing files with your team today!</p>
                <a href="manager_start_file_share.php" class="btn btn-upload">
                    <i class="fas fa-upload me-2"></i>
                    Upload Your First File
                </a>
            </div>
        <?php else: ?>
            <div class="share-grid">
                <?php foreach ($shares as $share): ?>
                    <div class="share-card <?= $share['team_id'] === null ? 'manager-file' : ($share['is_project_file'] ? 'project-file' : '') ?>">
                        <a href="file_versions.php?share_id=<?= urlencode($share['share_id']) ?>" class="share-link">
                            <div class="share-content">
                                <h2 class="share-title">
                                    <?= htmlspecialchars($share['title']) ?>
                                </h2>
                                <p class="share-description">
                                    <?= htmlspecialchars($share['description']) ?>
                                </p>
                                <div class="share-meta">
                                    <span class="meta-item">
                                        <i class="far fa-user"></i>
                                        <?= htmlspecialchars($share['username']) ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="far fa-calendar"></i>
                                        <?= date('M j, Y', strtotime($share['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="mt-3">
                                    <?php if ($share['team_id'] === null): ?>
                                        <span class="badge-custom badge-manager">
                                            <i class="fas fa-lock"></i>
                                            Manager File
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($share['is_project_file']): ?>
                                        <span class="badge-custom badge-project">
                                            <i class="fas fa-project-diagram"></i>
                                            Project File
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>