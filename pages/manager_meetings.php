<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager') {
    echo "<script>window.location.href = 'error.php';</script>";
}

// Handle delete meeting
if (isset($_POST['delete_meeting'])) {
    $meeting_id = $_POST['meeting_id'];
    $deleteStmt = $conn->prepare("DELETE FROM meetings WHERE meeting_id = ?");
    $deleteStmt->bind_param("i", $meeting_id);
    $deleteStmt->execute();
}

// Fetch meetings where manager presence is required
$stmt = $conn->prepare("
    SELECT m.*, u.username as creator_name, t.team_name 
    FROM meetings m 
    JOIN users u ON m.created_by = u.user_id 
    JOIN teams t ON m.team_id = t.team_id 
    WHERE m.manager_required = 1 
    ORDER BY m.scheduled_time DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Meetings</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary: #0f172a;
            --tertiary: #64748b;
            --accent: #10b981;
            --accent-hover: #059669;
            --danger: #f43f5e;
            --danger-hover: #e11d48;
            --warning: #f59e0b;
            --success: #10b981;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-light: #94a3b8;
            --border-radius: 16px;
            --transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background-color: var(--background);
            background-image: 
                radial-gradient(circle at 25% 10%, rgba(99, 102, 241, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 75% 90%, rgba(16, 185, 129, 0.05) 0%, transparent 40%);
            min-height: 100vh;
            padding: 2.5rem 0;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        .container {
            max-width: 1300px;
            padding: 0 2rem;
        }

        /* Glass card effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05),
                        0 8px 10px -6px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            transition: var(--transition);
        }

        .glass-card:hover {
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.1),
                        0 10px 15px -3px rgba(0, 0, 0, 0.05);
            transform: translateY(-5px);
        }

        /* Header styling */
        .dashboard-header {
            position: relative;
            margin-bottom: 3rem;
            padding: 2rem 2.5rem;
            border-radius: var(--border-radius);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            overflow: hidden;
        }

        .header-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: 
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            color: white;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            margin: 0;
            font-size: 2rem;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .page-subtitle {
            color: rgba(255, 255, 255, 0.85);
            margin-top: 0.5rem;
            font-weight: 400;
            font-size: 1rem;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
            color: white;
        }

        .meetings-wrapper {
            margin-bottom: 2rem;
        }

        /* Table styling */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            margin-top: -10px;
        }

        .modern-table th {
            color: var(--text-secondary);
            font-weight: 600;
            padding: 1rem 1.5rem;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1px;
            border-bottom: none;
            background: transparent;
        }

        .modern-table tbody tr {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 
                        0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: var(--transition);
            transform-origin: center;
        }

        .modern-table tbody tr:hover {
            transform: scale(1.005);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 
                        0 4px 6px -2px rgba(0, 0, 0, 0.04);
            background: linear-gradient(to right, var(--card-bg), rgba(245, 247, 250, 1));
            cursor: pointer;
        }

        .modern-table td {
            padding: 1.25rem 1.5rem;
            border: none;
            vertical-align: middle;
        }

        .modern-table td:first-child {
            border-top-left-radius: var(--border-radius);
            border-bottom-left-radius: var(--border-radius);
        }

        .modern-table td:last-child {
            border-top-right-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }

        /* Team cell */
        .team-cell {
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .team-icon {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        /* Title styling */
        .meeting-title {
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            flex-direction: column;
        }

        .title-description {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
            font-weight: 400;
        }

        /* Date styling */
        .date-cell {
            position: relative;
            padding-left: 2.5rem !important;
        }

        .date-badge {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            background: #f1f5f9;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .date-day {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
        }

        .date-month {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .time-text {
            font-weight: 500;
            margin-left: 0.75rem;
            color: var(--text-secondary);
        }

        /* Creator cell */
        .creator-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .creator-avatar {
            width: 32px;
            height: 32px;
            background: #e2e8f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        /* Status badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-in-progress {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--accent);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-scheduled {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        /* Action buttons */
        .action-cell {
            text-align: right;
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .btn-join {
            background-color: var(--accent);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.15);
        }

        .btn-join:hover {
            background-color: var(--accent-hover);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.2);
            transform: translateY(-2px);
            color: white;
        }

        .btn-delete {
            background-color: white;
            color: var(--danger);
            border: 1px solid rgba(244, 63, 94, 0.2);
            border-radius: 12px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .btn-delete:hover {
            background-color: var(--danger);
            color: white;
            box-shadow: 0 4px 8px rgba(244, 63, 94, 0.2);
            transform: translateY(-2px);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            color: var(--primary-light);
            opacity: 0.6;
        }

        .empty-state-title {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .empty-state-text {
            color: var(--text-secondary);
            max-width: 500px;
            margin: 0 auto;
        }

        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .slide-up {
            animation: slideUp 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive styles */
        @media (max-width: 1200px) {
            .date-badge {
                display: none;
            }
            
            .date-cell {
                padding-left: 1.5rem !important;
            }
        }
        
        @media (max-width: 992px) {
            .modern-table {
                display: block;
                overflow-x: auto;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .btn-back {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header glass-card fade-in">
            <div class="header-bg"></div>
            <div class="header-content">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-calendar-check me-2"></i>
                        Manager-Required Meetings
                    </h1>
                    <p class="page-subtitle">Review and manage meetings requiring your presence</p>
                </div>
                <a href="manager_dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Meetings Section -->
        <div class="meetings-wrapper slide-up">
            <?php if ($result->num_rows > 0): ?>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Team</th>
                            <th>Meeting Details</th>
                            <th>Scheduled For</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $delay = 0;
                        while ($meeting = $result->fetch_assoc()): 
                            $delay += 100;
                            $initials = strtoupper(substr($meeting['creator_name'], 0, 1));
                            $meetingDate = date('j', strtotime($meeting['scheduled_time']));
                            $meetingMonth = date('M', strtotime($meeting['scheduled_time']));
                            $meetingTime = date('h:i A', strtotime($meeting['scheduled_time']));
                        ?>
                        <tr class="animate__animated animate__fadeInUp" style="animation-delay: <?= $delay ?>ms;">
                            <td>
                                <div class="team-cell">
                                    <div class="team-icon">
                                        <i class="fas fa-users-gear"></i>
                                    </div>
                                    <?= htmlspecialchars($meeting['team_name']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="meeting-title">
                                    <?= htmlspecialchars($meeting['title']) ?>
                                    <span class="title-description">
                                        <?php 
                                            $description = htmlspecialchars($meeting['description']);
                                            echo (strlen($description) > 60) ? substr($description, 0, 60) . '...' : $description;
                                        ?>
                                    </span>
                                </div>
                            </td>
                            <td class="date-cell">
                                <div class="date-badge">
                                    <span class="date-day"><?= $meetingDate ?></span>
                                    <span class="date-month"><?= $meetingMonth ?></span>
                                </div>
                                <span class="time-text">
                                    <i class="far fa-clock me-1"></i> <?= $meetingTime ?>
                                </span>
                            </td>
                            <td>
                                <div class="creator-cell">
                                    <div class="creator-avatar"><?= $initials ?></div>
                                    <?= htmlspecialchars($meeting['creator_name']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($meeting['status'] === 'in_progress'): ?>
                                    <span class="status-badge status-in-progress">
                                        <i class="fas fa-circle-play"></i> In Progress
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-scheduled">
                                        <i class="fas fa-clock"></i> Scheduled
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="action-cell">
                                <div class="btn-group">
                                    <?php if ($meeting['status'] === 'in_progress'): ?>
                                        <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" class="btn btn-join">
                                            <i class="fas fa-video me-1"></i> Join Now
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this meeting?');">
                                        <input type="hidden" name="meeting_id" value="<?= $meeting['meeting_id'] ?>">
                                        <button type="submit" name="delete_meeting" class="btn btn-delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="glass-card empty-state">
                    <i class="fas fa-calendar-xmark empty-state-icon"></i>
                    <h3 class="empty-state-title">No Required Meetings Found</h3>
                    <p class="empty-state-text">When team members schedule meetings that require manager presence, they'll appear here. Check back later or head to your dashboard for other tasks.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <!-- Additional scripts for animations -->
    <script>
        // Add staggered animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.opacity = 1;
                }, 100 * (index + 1));
            });
        });
    </script>
</body>
</html>