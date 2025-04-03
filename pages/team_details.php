<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a Team Lead
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
}

// Get team details based on the team lead's team
$team_lead_id = $_SESSION['user_id'];
$team_query = "SELECT t.team_id, t.team_name 
               FROM teams t 
               WHERE t.team_lead_id = $team_lead_id";
$team_result = mysqli_query($conn, $team_query);
$team = mysqli_fetch_assoc($team_result);

// Updated query to include all task statuses
$members_query = "SELECT 
    u.user_id, 
    u.username, 
    COUNT(DISTINCT t.task_id) as total_tasks,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN t.status = 'in_study' THEN 1 ELSE 0 END) as in_study_tasks,
    SUM(CASE WHEN t.status = 'not_viewed' THEN 1 ELSE 0 END) as not_viewed_tasks,
    SUM(CASE WHEN t.status = 'verified' THEN 1 ELSE 0 END) as verified_tasks,
    SUM(t.points) as total_points
FROM team_members tm
JOIN users u ON tm.user_id = u.user_id
LEFT JOIN tasks t ON t.assigned_to = u.user_id
WHERE tm.team_id = {$team['team_id']}
GROUP BY u.user_id, u.username";

$members_result = mysqli_query($conn, $members_query);

// Rerun the query to reset the pointer if needed
mysqli_data_seek($members_result, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Details - <?php echo htmlspecialchars($team['team_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #4f46e5;
            --success-color: #22c55e;
            --info-color: #0ea5e9;
            --warning-color: #eab308;
            --danger-color: #ef4444;
            --verified-color: #8b5cf6;
            --light-bg: #f0f2f5;
            --dark-text: #1e293b;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --card-border-radius: 20px;
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(120deg, #f0f2f5 0%, #e5e7eb 100%);
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><path fill="%236366f1" fill-opacity="0.02" d="M45.3,-59.2C62.2,-52.8,81.6,-43.7,89.5,-28.7C97.3,-13.7,93.6,7.2,86.4,26.3C79.2,45.4,68.4,62.8,53.1,72.5C37.8,82.2,18.9,84.3,0.7,83.3C-17.5,82.3,-34.9,78.3,-48.8,68.1C-62.7,57.9,-73,41.5,-77.9,23.5C-82.7,5.5,-82.1,-14.1,-74.9,-30.2C-67.8,-46.2,-54.1,-58.7,-39.2,-66C-24.2,-73.3,-8.1,-75.4,4.4,-81.2C16.9,-87,33.5,-96.6,45.3,-59.2Z" transform="translate(100 100)"/></svg>') no-repeat center center fixed;
            background-size: cover;
            opacity: 0.5;
            z-index: -1;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 2rem 0;
            margin-bottom: 3rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            color: white;
        }
        
        .page-title {
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 0;
        }
        
        .team-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--card-border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05),
                        0 10px 15px rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.7);
            overflow: hidden;
            transition: all 0.4s ease;
        }
        
        .team-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.2);
        }
        
        .team-card .card-body {
            padding: 1.5rem;
        }
        
        .team-card .card-title {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--dark-text);
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(99, 102, 241, 0.2);
            margin-bottom: 1.5rem;
        }
        
        .chart-container {
            position: relative;
            height: 280px;  /* Increased height */
            width: 100%;    /* Explicit width */
            margin: 0.5rem auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chart-container canvas {
            max-width: 100%;
            max-height: 100%;
        }
        
        .member-stats {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            border: 1px solid rgba(99, 102, 241, 0.1);
            border-radius: 15px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.8);
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
            color: var(--dark-text);
        }
        
        .stat-value {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .btn-back {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-details {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-details:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }
        
        .team-icon {
            font-size: 1.8rem;
            margin-right: 0.8rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .user-icon {
            font-size: 1.4rem;
            margin-right: 0.8rem;
            color: var(--primary-color);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .col-md-4 {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .col-md-4:nth-child(2) { animation-delay: 0.2s; }
        .col-md-4:nth-child(3) { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="page-title">
                    <i class="fas fa-users team-icon"></i><?php echo htmlspecialchars($team['team_name']); ?> Team
                </h1>
                <a href="teamlead_dashboard.php" class="btn btn-back">
                    <i class="fas fa-home me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <script>
                // Get CSS variables for colors once
                const globalStyle = getComputedStyle(document.documentElement);
                const darkText = globalStyle.getPropertyValue('--dark-text').trim();
                
                // Create a function to initialize charts
                function initializeChart(userId, taskData, username) {
                    const ctx = document.getElementById('taskChart' + userId).getContext('2d');
                    const hasData = taskData.some(value => value > 0);
                    
                    if(userId == 10) {
                        console.log('Chart initialization for ' + username, {
                            hasData,
                            taskData
                        });
                    }

                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Completed', 'In Progress', 'In Study', 'Not Viewed', 'Verified'],
                            datasets: [{
                                data: hasData ? taskData : [1],
                                backgroundColor: hasData ? [
                                    '#22c55e',
                                    '#0ea5e9',
                                    '#eab308',
                                    '#ef4444',
                                    '#8b5cf6'
                                ] : ['#e2e8f0'],
                                borderWidth: 2,
                                borderColor: '#ffffff',
                                weight: 1,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '60%',
                            radius: '90%',
                            animation: {
                                animateScale: true,
                                animateRotate: true
                            },
                            plugins: {
                                legend: { 
                                    display: hasData,
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 12,
                                        padding: 15,
                                        font: {
                                            size: 11,
                                            weight: '600'
                                        },
                                        color: darkText,
                                        usePointStyle: true,
                                        pointStyle: 'circle'
                                    }
                                },
                                tooltip: {
                                    enabled: hasData,
                                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                    padding: 12,
                                    titleFont: { size: 13 },
                                    bodyFont: { size: 12 },
                                    callbacks: {
                                        label: function(context) {
                                            if (!hasData) return '';
                                            const label = context.label || '';
                                            const value = context.formattedValue || '';
                                            return ` ${label}: ${value} tasks`;
                                        }
                                    }
                                }
                            },
                            onClick: (event, activeElements) => {
                                if (activeElements.length > 0) {
                                    const chartIndex = activeElements[0].index;
                                    const statuses = ['completed', 'in_progress', 'in_study', 'not_viewed', 'verified'];
                                    window.location.href = `user_task_details.php?user_id=${userId}&status=${statuses[chartIndex]}`;
                                }
                            }
                        }
                    });
                }
            </script>
            
            <?php 
            while($member = mysqli_fetch_assoc($members_result)) { 
                $total = $member['total_tasks'] > 0 ? $member['total_tasks'] : 1;
                $completion_percentage = round(($member['completed_tasks'] + $member['verified_tasks']) / $total * 100);
            ?>
            <div class="col-md-4 mb-4">
                <div class="card team-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-user user-icon"></i><?php echo htmlspecialchars($member['username']); ?>
                        </h5>
                        <div class="chart-container">
                            <canvas id="taskChart<?php echo $member['user_id']; ?>"></canvas>
                        </div>
                        <div class="member-stats">
                            <div class="stat-item">
                                <span>Total Tasks:</span>
                                <span class="stat-value"><?php echo $member['total_tasks']; ?></span>
                            </div>
                            <div class="stat-item">
                                <span>Completion:</span>
                                <span class="stat-value"><?php echo $completion_percentage; ?>%</span>
                            </div>
                            <div class="stat-item">
                                <span>Total Points:</span>
                                <span class="stat-value"><?php echo $member['total_points']; ?></span>
                            </div>
                        </div>
                        <a href="user_task_details.php?user_id=<?php echo $member['user_id']; ?>" 
                           class="btn btn-details">
                            <i class="fas fa-tasks me-2"></i>Task Details
                        </a>
                    </div>
                </div>
            </div>
            <script>
                initializeChart(
                    <?php echo $member['user_id']; ?>,
                    [
                        <?php echo (int)$member['completed_tasks']; ?>,
                        <?php echo (int)$member['in_progress_tasks']; ?>,
                        <?php echo (int)$member['in_study_tasks']; ?>,
                        <?php echo (int)$member['not_viewed_tasks']; ?>,
                        <?php echo (int)$member['verified_tasks']; ?>
                    ],
                    '<?php echo htmlspecialchars($member['username']); ?>'
                );
            </script>
            <?php } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>