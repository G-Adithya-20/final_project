<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamMember') {
    echo "<script>window.location.href = 'error.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];

// Fetch unviewed tasks
$taskQuery = "SELECT t.*, u.username as assigned_by_name 
    FROM tasks t 
    JOIN users u ON t.assigned_by = u.user_id 
    WHERE t.assigned_to = ? AND t.status = 'not_viewed'
    ORDER BY t.created_at DESC";
$stmt = $conn->prepare($taskQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$taskResult = $stmt->get_result();

// Fetch upcoming meetings
$meetingQuery = "SELECT * FROM meetings 
    WHERE team_id = ? 
    AND (status = 'scheduled' OR status = 'in_progress')
    ORDER BY scheduled_time ASC";
$stmt = $conn->prepare($meetingQuery);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$meetingResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Member Notifications | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #4a6cf7;
            --secondary-color: #6a4cfe;
            --background-color: #f4f7ff;
            --text-color-dark: #2c3e50;
            --text-color-light: #ffffff;
            --card-shadow: 0 10px 30px rgba(74, 108, 247, 0.1);
        }

        * {
            transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color-dark);
            line-height: 1.6;
            padding-top: 100px;  /* Reduced from 150px */
        }

        /* Updated Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, #3a66db, #2952c8);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            height: 100px;  /* Reduced from 150px */
            display: flex;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
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

        .notifications-container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .notification-card {
            background: var(--text-color-light);
            border-radius: 16px;
            border: none;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.75rem;
            overflow: hidden;
        }

        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(74, 108, 247, 0.15);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            background-color: #f9fafe;
            border-bottom: 1px solid rgba(74, 108, 247, 0.1);
        }

        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color-dark);
            margin: 0;
        }

        .notification-content {
            padding: 1.5rem;
        }

        .section-title {
            color: var(--primary-color);
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--primary-color);
        }

        .points-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-color-light);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #6c757d;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
        }

        .meta-item i {
            color: var(--primary-color);
            opacity: 0.7;
        }

        .meeting-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-scheduled {
            background-color: rgba(74, 108, 247, 0.1);
            color: var(--primary-color);
        }

        .status-in-progress {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .btn-view, .btn-join {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-color-light);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 5px 15px rgba(74, 108, 247, 0.25);
        }

        .btn-view:hover, .btn-join:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(74, 108, 247, 0.35);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--text-color-light);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
        }

        .empty-state i {
            font-size: 4rem;
            color: rgba(74, 108, 247, 0.2);
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            color: var(--text-color-dark);
            margin-bottom: 1rem;
        }

        .timer {
            color: #6c757d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .timer i {
            color: var(--primary-color);
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <!-- Rest of the HTML remains the same as the original -->
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-bell me-2"></i>
                Notifications
            </div>
            <a href="teammember_dashboard.php" class="btn-back">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container notifications-container">
        <!-- Tasks Section -->
        <h2 class="section-title">
            <i class="fas fa-tasks me-2"></i>
            New Tasks Assigned
        </h2>
        
        <?php if ($taskResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>No New Tasks</h3>
                <p class="text-muted">You're all caught up! There are no new tasks assigned to you.</p>
            </div>
        <?php else: ?>
            <?php while ($task = $taskResult->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($task['task_title']); ?></h5>
                    <span class="points-badge">
                        <i class="fas fa-star"></i>
                        <?php echo $task['points']; ?> Points
                    </span>
                </div>
                <div class="notification-content">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>Assigned by: <?php echo htmlspecialchars($task['assigned_by_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-align-left"></i>
                        <span><?php echo htmlspecialchars($task['task_description']); ?></span>
                    </div>
                    <?php if ($task['due_date']): ?>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Due: <?php echo date('F j, Y', strtotime($task['due_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="text-end mt-3">
                        <a href="t_member_mytasks.php" class="btn-view">
                            <i class="fas fa-eye"></i>
                            View Task Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Meetings Section -->
        <h2 class="section-title">
            <i class="fas fa-calendar-check me-2"></i>
            Team Meetings
        </h2>

        <?php if ($meetingResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Upcoming Meetings</h3>
                <p class="text-muted">There are currently no meetings scheduled for your team.</p>
            </div>
        <?php else: ?>
            <?php while ($meeting = $meetingResult->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($meeting['title']); ?></h5>
                    <span class="meeting-status <?php echo $meeting['status'] === 'scheduled' ? 'status-scheduled' : 'status-in-progress' ?>">
                        <i class="fas <?php echo $meeting['status'] === 'scheduled' ? 'fa-clock' : 'fa-play-circle' ?>"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $meeting['status'])); ?>
                    </span>
                </div>
                <div class="notification-content">
                    <div class="meta-item">
                        <i class="fas fa-align-left"></i>
                        <span><?php echo htmlspecialchars($meeting['description']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('F j, Y', strtotime($meeting['scheduled_time'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('g:i A', strtotime($meeting['scheduled_time'])); ?></span>
                    </div>
                    <?php if ($meeting['status'] === 'in_progress'): ?>
                    <div class="text-end mt-3">
                        <a href="<?php echo $meeting['meet_link']; ?>" class="btn-join" target="_blank">
                            <i class="fas fa-video"></i>
                            Join Meeting Now
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="timer" data-time="<?php echo $meeting['scheduled_time']; ?>">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Time remaining: Calculating...</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTimers() {
            const timers = document.querySelectorAll('.timer');
            timers.forEach(timer => {
                const scheduledTime = new Date(timer.dataset.time);
                const now = new Date();
                const timeLeft = scheduledTime - now;

                if (timeLeft > 0) {
                    const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    timer.querySelector('span').textContent = `Time remaining: ${hours}h ${minutes}m`;
                } else {
                    timer.querySelector('span').textContent = 'Meeting should have started';
                }
            });
        }

        setInterval(updateTimers, 60000);
        updateTimers();
    </script>
</body>
</html>