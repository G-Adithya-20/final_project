<?php
session_start(); 
require '../includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Meetings | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #2ecc71;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #2b2d42;
            --text-color: #2b2d42;
            --text-muted: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            color: var(--text-color);
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

        .meetings-container {
            max-width: 1000px;
            margin: 3rem auto;
        }

        .page-title {
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 10px;
        }

        .meeting-card {
            background: #ffffff;
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
            transition: var(--transition);
            overflow: hidden;
        }

        .meeting-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .meeting-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background-color: rgba(67, 97, 238, 0.03);
        }

        .meeting-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .meeting-content {
            padding: 1.5rem;
        }

        .meeting-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px dashed rgba(0,0,0,0.1);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .meta-item i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .meeting-description {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            line-height: 1.8;
            font-size: 0.95rem;
        }

        .meeting-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-scheduled {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .status-in-progress {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .btn-meeting {
            padding: 0.75rem 1.75rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .btn-start {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #ffffff;
        }

        .btn-start:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-join {
            background: linear-gradient(135deg, var(--success-color), #27ae60);
            color: #ffffff;
        }

        .btn-join:hover {
            background: linear-gradient(135deg, #27ae60, var(--success-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(46, 204, 113, 0.3);
        }

        .btn-waiting {
            background-color: #f1f3f5;
            border-color: #dee2e6;
            color: #6c757d;
            box-shadow: none;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: #ffffff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .empty-state i {
            font-size: 5rem;
            color: #e9ecef;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #e9ecef, #ced4da);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .empty-state h3 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .empty-state p {
            max-width: 400px;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            .meeting-meta {
                gap: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .meeting-status {
                padding: 0.4rem 1rem;
                font-size: 0.75rem;
            }
            
            .btn-meeting {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php
    if (!isset($_SESSION['user_id'])) {
        echo "<script>window.location.href = 'error.php';</script>";
        exit();
    }

    // Determine dashboard URL based on role
    $dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';

    // Fetch meetings based on team_id
    $stmt = $conn->prepare("
        SELECT m.*, u.username as creator_name 
        FROM meetings m 
        JOIN users u ON m.created_by = u.user_id 
        WHERE m.team_id = ? 
        ORDER BY m.scheduled_time DESC
    ");
    $stmt->bind_param("i", $_SESSION['team_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-calendar-alt me-2"></i>
                View Meetings
            </div>
            <a href="<?php echo $dashboardUrl; ?>" class="btn-back">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container meetings-container">
        <h2 class="page-title">Upcoming & Active Meetings</h2>
        
        <?php if ($result->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Meetings Scheduled</h3>
                <p class="text-muted">There are currently no meetings scheduled for your team. Check back later or contact your team lead.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php while ($meeting = $result->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="meeting-card">
                            <div class="meeting-header">
                                <h5 class="meeting-title"><?= htmlspecialchars($meeting['title']) ?></h5>
                                <span class="meeting-status <?= $meeting['status'] === 'scheduled' ? 'status-scheduled' : 'status-in-progress' ?>">
                                    <i class="fas <?= $meeting['status'] === 'scheduled' ? 'fa-clock' : 'fa-play-circle' ?>"></i>
                                    <?= ucfirst(str_replace('_', ' ', $meeting['status'])) ?>
                                </span>
                            </div>
                            <div class="meeting-content">
                                <div class="meeting-meta">
                                    <div class="meta-item">
                                        <i class="far fa-calendar-alt"></i>
                                        <?= date('F j, Y', strtotime($meeting['scheduled_time'])) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="far fa-clock"></i>
                                        <?= date('g:i A', strtotime($meeting['scheduled_time'])) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="far fa-user-circle"></i>
                                        Created by <?= htmlspecialchars($meeting['creator_name']) ?>
                                    </div>
                                </div>
                                
                                <p class="meeting-description"><?= htmlspecialchars($meeting['description']) ?></p>
                                
                                <div class="text-end">
                                    <?php if ($_SESSION['role'] === 'TeamLead'): ?>
                                        <?php if ($meeting['status'] === 'scheduled'): ?>
                                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                               onclick="startMeeting(<?= $meeting['meeting_id'] ?>)"
                                               class="btn btn-meeting btn-start">
                                                <i class="fas fa-play"></i>
                                                Start Meeting
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                               class="btn btn-meeting btn-join">
                                                <i class="fas fa-video"></i>
                                                Join Meeting
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($meeting['status'] === 'in_progress'): ?>
                                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                               class="btn btn-meeting btn-join">
                                                <i class="fas fa-video"></i>
                                                Join Meeting
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-meeting btn-waiting" disabled>
                                                <i class="fas fa-clock"></i>
                                                Waiting to Start
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function startMeeting(meetingId) {
            fetch('update_meeting_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `meeting_id=${meetingId}&status=in_progress`
            });
        }
    </script>
</body>
</html>