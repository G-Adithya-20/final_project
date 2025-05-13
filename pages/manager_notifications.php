<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    echo "<script>window.location.href = 'error.php';</script>";
}

// Fetch extension requests that are pending and less than 2 days old
$extensionQuery = "SELECT pe.*, p.title as project_title, u.username 
    FROM project_extensions pe 
    JOIN projects p ON pe.project_id = p.project_id 
    JOIN users u ON pe.requested_by = u.user_id 
    WHERE pe.status = 'pending' 
    AND pe.requested_date >= DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY)";
$extensionResult = $conn->query($extensionQuery);

// Fetch meetings requiring manager attention
$meetingQuery = "SELECT * FROM meetings 
    WHERE manager_required = 1 
    AND (status = 'scheduled' OR status = 'in_progress')";
$meetingResult = $conn->query($meetingQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Notifications | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --light: #f1f5f9;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light);
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .navbar .back-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }

        .navbar .back-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .section-title {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .notification-card {
            background: white;
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .notification-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .notification-header {
            background: linear-gradient(to right, rgba(67, 97, 238, 0.05), rgba(63, 55, 201, 0.05));
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .notification-title {
            font-size: 1.25rem;
            color: var(--primary);
        }

        .notification-content {
            padding: 1.5rem;
        }

        .meta-item {
            background: rgba(67, 97, 238, 0.05);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .meta-item i {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .status-pending {
            background: linear-gradient(135deg, var(--warning), #fbbf24);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
        }

        .btn-view, .btn-join {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-view {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
        }

        .btn-join {
            background: linear-gradient(135deg, var(--success), #34d399);
            color: white;
            border: none;
        }

        .btn-view:hover, .btn-join:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .timer {
            background: rgba(67, 97, 238, 0.05);
            color: var(--primary);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .empty-state i {
            font-size: 4rem;
            color: #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .expired {
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-bell"></i>
                Notifications
            </span>
            <div>
                <a href="manager_dashboard.php" class="btn btn-outline-light">
                    <i class="fas fa-home me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container notifications-container">
        <!-- Project Extensions Section -->
        <h2 class="section-title">
            <i class="fas fa-project-diagram"></i>
            Project Extension Requests
        </h2>
        
        <?php if ($extensionResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>No Extension Requests</h3>
                <p class="text-muted">There are no pending project extension requests at this time.</p>
            </div>
        <?php else: ?>
            <?php while ($extension = $extensionResult->fetch_assoc()): 
                $requestDate = new DateTime($extension['requested_date']);
                $now = new DateTime();
                $diff = $requestDate->diff($now)->days;
            ?>
            <div class="notification-card <?php echo ($diff >= 2) ? 'expired' : ''; ?>" 
                 id="extension-<?php echo $extension['extension_id']; ?>"
                 data-date="<?php echo $extension['requested_date']; ?>">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($extension['project_title']); ?></h5>
                    <span class="status-pending">
                        <i class="fas fa-clock"></i>
                        Pending Approval
                    </span>
                </div>
                <div class="notification-content">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>Requested by: <?php echo htmlspecialchars($extension['username']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>New Due Date: <?php echo date('F j, Y', strtotime($extension['new_due_date'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-comment"></i>
                        <span>Reason: <?php echo htmlspecialchars($extension['reason']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Requested: <?php echo date('F j, Y ', strtotime($extension['requested_date'])); ?></span>
                    </div>
                    <div class="text-end mt-3">
                        <a href="manage_projects.php" class="btn-view">
                            <i class="fas fa-eye"></i>
                            View Request
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Meetings Section -->
        <h2 class="section-title">
            <i class="fas fa-calendar-check"></i>
            Meetings Requiring Manager
        </h2>

        <?php if ($meetingResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Upcoming Meetings</h3>
                <p class="text-muted">There are no meetings requiring your attention at this time.</p>
            </div>
        <?php else: ?>
            <?php while ($meeting = $meetingResult->fetch_assoc()): ?>
            <div class="notification-card" id="meeting-<?php echo $meeting['meeting_id']; ?>">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($meeting['title']); ?></h5>
                    <span class="status-pending">
                        <i class="fas fa-user-tie"></i>
                        Manager Required
                    </span>
                </div>
                <div class="notification-content">
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

        function checkExpiredNotifications() {
            const extensionNotifications = document.querySelectorAll('[id^="extension-"]');
            extensionNotifications.forEach(notification => {
                const requestDate = new Date(notification.dataset.date);
                const now = new Date();
                const diffDays = Math.floor((now - requestDate) / (1000 * 60 * 60 * 24));
                
                if (diffDays >= 2) {
                    notification.classList.add('expired');
                }
            });
        }

        setInterval(updateTimers, 60000);
        updateTimers();

        setInterval(checkExpiredNotifications, 3600000);
        checkExpiredNotifications();
    </script>
</body>
</html>