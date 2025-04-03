<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

// Add the function at the top of your file
function formatCustomDate($date_string) {
    // Handle the specific format "2025-03-12 22"
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2})$/', $date_string, $matches)) {
        $year = $matches[1];
        $month_num = $matches[2];
        $day = $matches[3];
        
        // Get month name
        $month_names = [
            '01' => 'January', '02' => 'February', '03' => 'March', 
            '04' => 'April', '05' => 'May', '06' => 'June',
            '07' => 'July', '08' => 'August', '09' => 'September',
            '10' => 'October', '11' => 'November', '12' => 'December'
        ];
        
        $month_name = $month_names[$month_num] ?? 'Unknown';
        
        // Return formatted date
        return $day . '/' . $month_num . '/' . $year . ' (' . $month_name . ')';
    }
    
    // For other formats, try using strtotime
    $date_obj = strtotime($date_string);
    if ($date_obj === false) {
        return "Invalid date format";
    }
    
    return date('d/m/Y (F)', $date_obj);
}

// Validate and sanitize input parameters
$user_id = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_VALIDATE_INT) : 0;
if (!$user_id) {
    // header("Location: performance_analysis.php");
    // exit();
    echo "<script>window.location.href = 'performance_analysis.php';</script>";
}

$period = isset($_GET['period']) ? $_GET['period'] : '1month';
$valid_periods = ['1month', '3months', '6months', '1year'];
if (!in_array($period, $valid_periods)) {
    $period = '1month';
}

// Get time period
$current_date = date('Y-m-d');
switch($period) {
    case '3months':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        break;
    case '6months':
        $start_date = date('Y-m-d', strtotime('-6 months'));
        break;
    case '1year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-1 month'));
}

// Get member details with error handling
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    // header("Location: performance_analysis.php");
    // exit();
    echo "<script>window.location.href = 'performance_analysis.php';</script>";
}

// Get detailed task history with status changes
$query = "
    SELECT 
        t.task_id,
        t.task_title,
        t.task_description,
        t.status,
        t.points,
        t.created_at,
        t.due_date,
        COALESCE(t.status, 'not_viewed') as current_status,
        GROUP_CONCAT(
            CONCAT(
                th.old_status, ':', 
                th.new_status, ':', 
                th.changed_at, ':', 
                COALESCE(th.notes, '')
            ) 
            ORDER BY th.changed_at ASC SEPARATOR '||'
        ) as status_history
    FROM tasks t
    LEFT JOIN task_status_history th ON t.task_id = th.task_id
    WHERE t.assigned_to = ?
    AND t.created_at BETWEEN ? AND ?
    GROUP BY t.task_id
    ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("iss", $user_id, $start_date, $current_date);
$stmt->execute();
$result = $stmt->get_result();
$task_history = $result->fetch_all(MYSQLI_ASSOC);

// Calculate performance metrics
$total_tasks = count($task_history);
$verified_tasks = 0;
$total_points = 0;
$earned_points = 0;
$status_counts = array_fill_keys(['not_viewed', 'in_study', 'in_progress', 'completed', 'verified'], 0);
$progression_data = [];

foreach ($task_history as $task) {
    $total_points += $task['points'];
    if ($task['current_status'] === 'verified') {
        $verified_tasks++;
        $earned_points += $task['points'];
    }
    $status_counts[$task['current_status']]++;
    
    // Calculate progression data
    $date = date('Y-m-d', strtotime($task['created_at']));
    if (!isset($progression_data[$date])) {
        $progression_data[$date] = ['total' => 0, 'completed' => 0];
    }
    $progression_data[$date]['total']++;
    if ($task['current_status'] === 'verified') {
        $progression_data[$date]['completed']++;
    }
}

$completion_rate = $total_tasks > 0 ? ($verified_tasks / $total_tasks) * 100 : 0;
$points_rate = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;

// Status color mapping
$status_colors = [
    'not_viewed' => 'danger',
    'in_study' => 'warning',
    'in_progress' => 'primary',
    'completed' => 'success',
    'verified' => 'purple'
];

// Format period for display
$period_display = [
    '1month' => 'Past Month',
    '3months' => 'Past 3 Months',
    '6months' => 'Past 6 Months',
    '1year' => 'Past Year'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Report - <?php echo htmlspecialchars($user_data['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --accent-color: #36b9cc;
            --light-bg: #f8f9fc;
            --dark-text: #5a5c69;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --timeline-color: #e3e6f0;
        }
        
        body {
            font-family: 'Nunito', 'Segoe UI', Roboto, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }
        
        /* Navbar */
        .top-bar {
            background: linear-gradient(135deg, var(--primary-color), #3458ca);
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem 0;  /* Increased from 1rem to 2rem */
            margin-bottom: 2rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.35rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        /* Timeline */
        .timeline {
            position: relative;
            padding: 1rem 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20px;
            height: 100%;
            width: 2px;
            background-color: var(--timeline-color);
        }
        
        .timeline-item {
            padding: 1.5rem;
            background-color: white;
            border-radius: 0.5rem;
            position: relative;
            margin-left: 3rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05);
            border-left: 4px solid var(--primary-color);
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -38px;
            top: 1.5rem;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: white;
            border: 3px solid var(--primary-color);
            z-index: 1;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .status-history {
            margin-top: 1rem;
            padding: 1rem;
            background-color: rgba(78, 115, 223, 0.05);
            border-radius: 0.5rem;
            border-left: 3px solid var(--timeline-color);
        }
        
        /* Metric Cards */
        .metric-card {
            margin-bottom: 1.5rem;
        }
        
        .metric-card .card-body {
            padding: 1.5rem;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }
        
        .metric-label {
            color: #858796;
            font-size: 0.875rem;
            margin-bottom: 0;
        }
        
        .metric-icon {
            background-color: rgba(78, 115, 223, 0.1);
            border-radius: 50%;
            width: 4rem;
            height: 4rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.75rem;
        }
        
        /* Progress bars */
        .progress {
            height: 1rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            background-color: rgba(78, 115, 223, 0.1);
        }
        
        .progress-bar {
            border-radius: 0.5rem;
        }
        
        /* User Profile */
        .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin-right: 1.5rem;
        }
        
        .user-info h1 {
            margin-bottom: 0.25rem;
            font-weight: 700;
            color: var(--dark-text);
        }
        
        .user-info p {
            margin-bottom: 0.5rem;
            color: #858796;
        }
        
        /* Period selector */
        .period-selector {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
        }
        
        .period-selector .btn {
            border-radius: 50rem;
            padding: 0.5rem 1.25rem;
            font-weight: 600;
            margin-left: 0.5rem;
            transition: all 0.2s;
            box-shadow: none;
        }
        
        .period-selector .btn.active {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.25);
        }
        
        /* Task details */
        .task-title {
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }
        
        .task-meta {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
            color: #858796;
            font-size: 0.875rem;
        }
        
        .task-meta i {
            margin-right: 0.5rem;
        }
        
        .task-meta span {
            margin-right: 1.5rem;
        }
        
        .task-description {
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .timeline-item {
                margin-left: 2rem;
            }
            
            .timeline-item::before {
                left: -28px;
            }
            
            .timeline::before {
                left: 10px;
            }
        }
        
        /* Custom color for verified status */
        .bg-purple {
            background-color: #6f42c1 !important;
            color: white !important;
        }
        .text-purple {
            color: #6f42c1 !important;
        }
        
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Performance Dashboard</h4>
                <div>
                    <a href="performance_analysis.php?period=<?php echo urlencode($period); ?>" 
                       class="btn btn-light btn-lg px-4 d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i>
                        <span>Back to Team Performance Analysis</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- User Profile Header -->
        <div class="user-profile">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user_data['username'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h1><?php echo htmlspecialchars($user_data['username']); ?></h1>
                <p><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user_data['email']); ?></p>
                <span class="badge bg-primary"><?php echo $period_display[$period]; ?></span>
            </div>
        </div>
        
        <!-- Period Selector -->
        <div class="period-selector">
            <?php foreach ($valid_periods as $p): ?>
                <a href="?user_id=<?php echo $user_id; ?>&period=<?php echo $p; ?>" 
                   class="btn <?php echo ($p === $period) ? 'active' : 'btn-outline-primary'; ?>">
                    <?php echo $period_display[$p]; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Performance Summary Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Task Completion</h6>
                                <div class="metric-value"><?php echo number_format($completion_rate, 1); ?>%</div>
                                <p class="metric-label">
                                    <?php echo $verified_tasks; ?> of <?php echo $total_tasks; ?> tasks completed
                                </p>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $completion_rate; ?>%" 
                                         aria-valuenow="<?php echo $completion_rate; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            <div class="metric-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Points Earned</h6>
                                <div class="metric-value"><?php echo number_format($points_rate, 1); ?>%</div>
                                <p class="metric-label">
                                    <?php echo $earned_points; ?> of <?php echo $total_points; ?> points
                                </p>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo $points_rate; ?>%" 
                                         aria-valuenow="<?php echo $points_rate; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            <div class="metric-icon">
                                <i class="bi bi-trophy"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Tasks</h6>
                                <div class="metric-value"><?php echo $total_tasks; ?></div>
                                <p class="metric-label">
                                    <?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d', strtotime($current_date)); ?>
                                </p>
                                <div class="chart-container" style="position: relative; height:40px; width:100%">
                                    <canvas id="taskTrend"></canvas>
                                </div>
                            </div>
                            <div class="metric-icon">
                                <i class="bi bi-list-task"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold">Task Status Distribution</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position: relative; height:300px;">
                            <canvas id="statusDistribution"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">Task Timeline</h6>
                <span class="badge bg-primary"><?php echo $total_tasks; ?> Tasks</span>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($task_history as $task): ?>
                        <div class="timeline-item">
                            <h5 class="task-title"><?php echo htmlspecialchars($task['task_title']); ?></h5>
                            <div class="task-meta">
                                <span><i class="bi bi-calendar"></i> Created: <?php echo date('M d, Y', strtotime($task['created_at'])); ?></span>
                                <?php if ($task['due_date']): ?>
                                    <span><i class="bi bi-clock"></i> Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?></span>
                                <?php endif; ?>
                                <span><i class="bi bi-star-fill"></i> Points: <?php echo $task['points']; ?></span>
                            </div>
                            <p class="task-description"><?php echo htmlspecialchars($task['task_description']); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="status-badge bg-<?php echo $status_colors[$task['current_status']]; ?>">
                                    <i class="bi bi-check-circle-fill me-1"></i> <?php echo ucfirst(str_replace('_', ' ', $task['current_status'])); ?>
                                </span>
                            </div>

                            <?php if ($task['status_history']): ?>
                                <div class="status-history">
                                    <h6 class="text-primary mb-3"><i class="bi bi-clock-history me-2"></i>Status History</h6>
                                    <?php
                                    $history_items = explode('||', $task['status_history']);
                                    foreach ($history_items as $item) {
                                        list($old_status, $new_status, $changed_at, $notes) = array_pad(explode(':', $item), 4, '');
                                        if ($old_status && $new_status):
                                    ?>
                                        <div class="mb-2 ps-2 border-start border-2">
                                            <div class="fw-bold text-dark">
                                                <?php echo ucfirst(str_replace('_', ' ', $old_status)); ?> → 
                                                <span class="text-<?php echo $status_colors[$new_status]; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $new_status)); ?>
                                                    

                                                </span>
                                            </div>
                                            <div class="text-muted small">
                                                
                                            <div class="text-muted small">
    <i class="bi bi-clock me-1"></i><?php echo formatCustomDate($changed_at); ?>
</div> </div>
                                          
                                        </div>
                                    <?php
                                        endif;
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($task_history) === 0): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">No tasks found for this period</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Status Distribution Chart
        const statusLabels = ['Not Viewed', 'In Study', 'In Progress', 'Completed', 'Verified'];
        const statusData = [
            <?php echo implode(',', array_values($status_counts)); ?>  // Fixed array.values to array_values
        ];
        const statusColors = [
            '#dc3545',  // red (not viewed)
            '#ffc107',  // yellow (in study)
            '#0d6efd',  // blue (in progress)
            '#198754',  // green (completed)
            '#6f42c1'   // purple (verified)
        ];
        
        // Status Distribution Chart
        new Chart(document.getElementById('statusDistribution'), {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusData,
                    backgroundColor: statusColors,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 14
                        },
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} tasks (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        // Task Trend Mini Chart (placeholder data - in a real app this would use actual time-series data)
        new Chart(document.getElementById('taskTrend'), {
            type: 'line',
            data: {
                labels: Array(<?php echo min(8, count($task_history)); ?>).fill(''),
                datasets: [{
                    data: [
                        <?php 
                            $counts = array_slice(array_column($task_history, 'points'), 0, 8);
                            echo implode(',', $counts ? $counts : [0, 0, 0, 0, 0, 0, 0, 0]);
                        ?>
                    ],
                    fill: {
                        target: 'origin',
                        above: 'rgba(78, 115, 223, 0.1)'
                    },
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    borderColor: '#4e73df',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        display: false,
                        min: 0
                    }
                },
                elements: {
                    line: {
                        tension: 0.4
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>