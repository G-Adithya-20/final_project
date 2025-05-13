<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
}

$user_id = $_SESSION['user_id'];

// Fetch extension responses from last 2 days
$extensionQuery = "SELECT pe.*, p.title as project_title 
    FROM project_extensions pe 
    JOIN projects p ON pe.project_id = p.project_id 
    WHERE pe.requested_by = ? 
    AND (pe.status = 'approved' OR pe.status = 'rejected')
    AND pe.responded_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
    ORDER BY pe.responded_at DESC";
$stmt = $conn->prepare($extensionQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$extensionResult = $stmt->get_result();

// Fetch meetings
$meetingQuery = "SELECT * FROM meetings WHERE created_by = ? AND status = 'scheduled' ORDER BY scheduled_time ASC";
$stmt = $conn->prepare($meetingQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meetingResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Lead Notifications | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #2ecc71;
            --success-bg: rgba(46, 204, 113, 0.1);
            --danger-color: #e63946;
            --danger-bg: rgba(230, 57, 70, 0.1);
            --text-dark: #2d3748;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
            --body-bg: #f8fafc;
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--body-bg);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            backdrop-filter: blur(5px);
            background-color: rgba(255, 255, 255, 0.1);
        }

        .navbar .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: 700;
            margin-left: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }

        .navbar-brand i {
            font-size: 1.3rem;
            background-color: rgba(255, 255, 255, 0.2);
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .notifications-container {
            max-width: 1140px;
            margin: 3rem auto;
            padding: 0 1.5rem;
        }

        .notification-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: none;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.75rem;
            transition: var(--transition);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background-color: rgba(241, 245, 249, 0.5);
        }

        .notification-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
            letter-spacing: -0.3px;
        }

        .notification-content {
            padding: 1.5rem;
        }

        .section-title {
            color: var(--text-dark);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 2rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: -0.5px;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
            padding: 0.5rem 0;
        }

        .meta-item i {
            color: var(--primary-color);
            font-size: 1rem;
            width: 24px;
            text-align: center;
        }

        .status-approved {
            color: var(--success-color);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--success-bg);
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(46, 204, 113, 0.15);
        }

        .status-rejected {
            color: var(--danger-color);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--danger-bg);
            padding: 0.35rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(230, 57, 70, 0.15);
        }

        .timer {
            color: var(--primary-color);
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
            padding: 0.75rem 1rem;
            background-color: rgba(67, 97, 238, 0.08);
            border-radius: 8px;
        }

        .btn-join {
            background: linear-gradient(135deg, var(--success-color), #26a65b);
            border: none;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
            box-shadow: 0 4px 8px rgba(46, 204, 113, 0.2);
            letter-spacing: 0.5px;
        }

        .btn-join:hover {
            background: linear-gradient(135deg, #26a65b, var(--success-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(46, 204, 113, 0.3);
            color: #ffffff;
        }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 2rem;
            opacity: 0.7;
        }

        .empty-state h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }

        .empty-state p {
            color: var(--text-muted);
            max-width: 400px;
            margin: 0 auto;
            font-size: 1rem;
        }

        /* Subtle animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notification-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .notification-card:nth-child(2) {
            animation-delay: 0.1s;
        }
        
        .notification-card:nth-child(3) {
            animation-delay: 0.2s;
        }
        
        /* Dark mode toggle - for future implementation */
        .mode-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
            z-index: 100;
        }
        
        .mode-toggle:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
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
            <a href="teamlead_dashboard.php" class="back-btn">
                <i class="fas fa-home me-2"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </nav>

    <div class="container notifications-container">
        <!-- Project Extensions Section -->
        <h2 class="section-title">
            <i class="fas fa-project-diagram"></i>
            Project Extension Responses
        </h2>
        
        <?php if ($extensionResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>No Extension Responses</h3>
                <p class="text-muted">There are no recent project extension responses to display.</p>
            </div>
        <?php else: ?>
            <?php while ($extension = $extensionResult->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($extension['project_title']); ?></h5>
                    <span class="status-<?php echo strtolower($extension['status']); ?>">
                        <i class="fas fa-<?php echo $extension['status'] === 'approved' ? 'check-circle' : 'times-circle'; ?>"></i>
                        <?php echo ucfirst($extension['status']); ?>
                    </span>
                </div>
                <div class="notification-content">
                    <div class="meta-item">
                        <i class="fas fa-comment"></i>
                        <span><?php echo htmlspecialchars($extension['response_note']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>New Due Date: <?php echo date('F j, Y', strtotime($extension['new_due_date'])); ?></span>
                    </div>
                    <div class="meta-item">
    <i class="fas fa-clock"></i>
    <span>Responded: <?php echo date('F j, Y g:i A', strtotime($extension['responded_at'])); ?></span>
</div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Meetings Section -->
        <h2 class="section-title">
            <i class="fas fa-calendar-check"></i>
            Upcoming Meetings
        </h2>

        <?php if ($meetingResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Upcoming Meetings</h3>
                <p class="text-muted">You have no scheduled meetings at this time.</p>
            </div>
        <?php else: ?>
            <?php while ($meeting = $meetingResult->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($meeting['title']); ?></h5>
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
                    <div class="timer" data-time="<?php echo $meeting['scheduled_time']; ?>">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Time remaining: Calculating...</span>
                    </div>
                    <div class="text-end">
                        <a href="view_meetings.php" class="btn-join">
                            <i class="fas fa-video"></i>
                            Join Meeting
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Future dark mode toggle button (non-functional for now) -->
    <!-- <div class="mode-toggle">
        <i class="fas fa-moon"></i>
    </div> -->

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

        // Initial update and set interval
        updateTimers();
        setInterval(updateTimers, 60000);
        
        // Add subtle entrance animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.notification-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>

   
</body>
</html>