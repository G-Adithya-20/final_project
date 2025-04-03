<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR') {
    echo "<script>window.location.href = 'error.php';</script>";
}

// Get time period from filter
$period = isset($_GET['period']) ? $_GET['period'] : '1month';
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

// Updated query to correctly handle points and task statuses
$query = "
    SELECT 
        u.user_id,
        u.username,
        COUNT(DISTINCT t.task_id) as total_tasks,
        COALESCE(SUM(CASE WHEN t.status = 'verified' THEN t.points ELSE 0 END), 0) as verified_points,
        COALESCE(SUM(t.points), 0) as total_points,
        SUM(CASE WHEN t.status = 'verified' THEN 1 ELSE 0 END) as verified_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN t.status = 'in_study' THEN 1 ELSE 0 END) as in_study_tasks,
        SUM(CASE WHEN t.status = 'not_viewed' THEN 1 ELSE 0 END) as not_viewed_tasks,
        COALESCE(AVG(CASE 
            WHEN t.status = 'verified' THEN 100
            WHEN t.status = 'completed' THEN 75
            WHEN t.status = 'in_progress' THEN 50
            WHEN t.status = 'in_study' THEN 25
            ELSE 0 
        END), 0) as avg_performance
    FROM users u 
    LEFT JOIN tasks t ON u.user_id = t.assigned_to 
        AND (t.created_at BETWEEN ? AND ? OR ? = DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR))
    WHERE u.role = 'TeamMember'
    GROUP BY u.user_id, u.username
    ORDER BY verified_points DESC, avg_performance DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $start_date, $current_date, $start_date);
$stmt->execute();
$result = $stmt->get_result();
$performance_data = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals for status chart
$total_verified = array_sum(array_column($performance_data, 'verified_tasks'));
$total_completed = array_sum(array_column($performance_data, 'completed_tasks'));
$total_in_progress = array_sum(array_column($performance_data, 'in_progress_tasks'));
$total_in_study = array_sum(array_column($performance_data, 'in_study_tasks'));
$total_not_viewed = array_sum(array_column($performance_data, 'not_viewed_tasks'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Performance Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4dd4ac;
            --warning-color: #ffbe0b;
            --danger-color: #ff5a5f;
            --neutral-color: #6c757d;
            --gradient-start: #4361ee;
            --gradient-end: #3a0ca3;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 3.5rem 0;  /* Increased from 2.5rem to 3.5rem */
            border-radius: 0 0 25px 25px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        
        .metric-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .metric-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .metric-label {
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--neutral-color);
            letter-spacing: 1px;
        }
        
        .filter-section {
            background-color: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-select, .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gradient-end), var(--gradient-start));
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-secondary {
            background-color: white;
            color: var(--dark-color);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary:hover {
            background-color: var(--light-color);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .btn-info {
            background-color: var(--accent-color);
            border: none;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #3a7fc1;
            color: white;
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .table thead th {
            background-color: rgba(243, 246, 249, 0.6);
            color: var(--neutral-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            padding: 1.2rem 1rem;
            border: none;
        }
        
        .table tbody td {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(243, 246, 249, 0.8);
        }
        
        .performance-score {
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        
        .score-high {
            background-color: rgba(96, 98, 196, 0.15);        /* Verified - Purple (100) */
            color: rgba(96, 98, 196, 1);
        }
        
        .score-medium {
            background-color: rgba(4, 113, 77, 0.15);         /* Completed - Green (>=75) */
            color: rgba(4, 113, 77, 1);
        }
        
        .score-low {
            background-color: rgba(59, 130, 246, 0.15);      /* In Progress - Blue (>=50) */
            color: rgba(59, 130, 246, 1);
        }
        
        .score-lowest {
            background-color: rgba(234, 179, 8, 0.15);       /* In Study - Yellow (>=25) */
            color: rgba(234, 179, 8, 1);
        }
        
        .score-zero {
            background-color: rgba(227, 22, 22, 0.15);       /* Not Viewed - Red (0) */
            color: rgba(227, 22, 22, 1);
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            padding: 1rem;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Responsive fixes */
        @media (max-width: 768px) {
            .metric-value {
                font-size: 1.8rem;
            }
            
            .chart-container {
                height: 300px;
            }
        }
        /* Enhanced table styling */
        .table-container {
            border-radius: 15px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(58, 12, 163, 0.1));
        }
        
        .table thead th {
            color: #2d3748;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 1.2px;
            padding: 1rem 1.5rem;
            border: none;
            white-space: nowrap;
        }
        
        .table tbody td {
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
            font-size: 0.95rem;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.02);
            transition: all 0.2s ease;
        }
        
        .avatar-initials {
            width: 40px !important;
            height: 40px !important;
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(58, 12, 163, 0.1));
            color: var(--primary-color);
            font-size: 1.1rem;
            transition: all 0.2s ease;
        }
        
        tr:hover .avatar-initials {
            transform: scale(1.05);
        }
        
        .username-cell {
            font-weight: 600;
            color: #2d3748;
        }
        
        .metric-cell {
            font-variant-numeric: tabular-nums;
            font-weight: 500;
        }
        
        .btn-details {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--accent-color), #3a7fc1);
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-details:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(72, 149, 239, 0.2);
        }
    </style>
</head>
<body>
   

    <!-- Page Header -->
    <header class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">Team Performance Analysis</h1>
                    <p class="mb-0 opacity-75">Track, measure, and optimize your team's productivity</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <button type="button" class="btn btn-light" onclick="exportReport()">
                            <i class="fas fa-file-export me-2"></i>Export Report
                        </button>
                        <a href="hr_dashboard.php" class="btn btn-light">
                            <i class="fas fa-home me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container py-4">
        <!-- Filter Section -->
        <div class="filter-section mb-4">
            <form method="GET" class="row align-items-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label fw-bold">Time Period</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="far fa-calendar-alt"></i>
                        </span>
                        <select name="period" class="form-select" onchange="this.form.submit()">
                            <option value="1month" <?php echo $period == '1month' ? 'selected' : ''; ?>>Last Month</option>
                            <option value="3months" <?php echo $period == '3months' ? 'selected' : ''; ?>>Last 3 Months</option>
                            <option value="6months" <?php echo $period == '6months' ? 'selected' : ''; ?>>Last 6 Months</option>
                            <option value="1year" <?php echo $period == '1year' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-8 text-md-end">
                    <p class="text-muted mb-0">
                        Showing data from <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($current_date)); ?>
                    </p>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="dashboard-card">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="metric-value"><?php echo count($performance_data); ?></div>
                        <div class="metric-label">Team Members</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dashboard-card">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="metric-value"><?php echo $total_verified; ?></div>
                        <div class="metric-label">Verified Tasks</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dashboard-card">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="metric-value"><?php echo $total_in_progress; ?></div>
                        <div class="metric-label">Tasks In Progress</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dashboard-card">
                    <div class="metric-card">
                        <div class="metric-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="metric-value"><?php echo $total_in_study; ?></div>
                        <div class="metric-label">Tasks Under Study</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Performance Scores</h5>
                        <button class="btn btn-sm btn-light" type="button" disabled>
                            <i class="fas fa-expand-alt me-1"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Task Status Distribution</h5>
                        <button class="btn btn-sm btn-light" type="button" disabled>
                            <i class="fas fa-expand-alt me-1"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="taskStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Replace the existing table section with this enhanced version -->
        <div class="dashboard-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Team Performance Details</h5>
                <div class="d-flex gap-2">
                    <span class="text-muted small">
                        <i class="fas fa-circle-info me-1"></i>
                        Showing <?php echo count($performance_data); ?> members
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Team Member</th>
                                <th class="text-center">Total Tasks</th>
                                <th class="text-center">Verified Tasks</th>
                                <th class="text-center">Points Earned</th>
                                <th class="text-center">Total Points</th>
                                <th class="text-center">Performance</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($performance_data as $data): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-initials rounded-circle me-3 d-flex align-items-center justify-content-center">
                                            <span><?php echo substr($data['username'], 0, 1); ?></span>
                                        </div>
                                        <span class="username-cell"><?php echo htmlspecialchars($data['username']); ?></span>
                                    </div>
                                </td>
                                <td class="text-center metric-cell"><?php echo $data['total_tasks']; ?></td>
                                <td class="text-center metric-cell"><?php echo $data['verified_tasks']; ?></td>
                                <td class="text-center metric-cell"><?php echo $data['verified_points']; ?></td>
                                <td class="text-center metric-cell"><?php echo $data['total_points']; ?></td>
                                <td class="text-center">
                                    <?php 
                                    $score = number_format($data['avg_performance'], 1);
                                    $score_class = $score == 100 ? 'score-high' : 
                                                ($score >= 75 ? 'score-medium' : 
                                                ($score >= 50 ? 'score-low' : 
                                                ($score >= 25 ? 'score-lowest' : 'score-zero')));
                                    echo "<span class='performance-score $score_class'>$score%</span>";
                                    ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-details" onclick="viewDetails(<?php echo $data['user_id']; ?>)">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Performance Chart with improved styling
        new Chart(
            document.getElementById('performanceChart'),
            {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($performance_data, 'username')); ?>,
                    datasets: [{
                        label: 'Performance Score (%)',
                        data: <?php echo json_encode(array_map(function($item) { 
                            return round($item['avg_performance'], 1); 
                        }, $performance_data)); ?>,
                        backgroundColor: function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            return value == 100 ? 'rgba(96, 98, 196, 0.9)' :     // Verified - Purple
                                   value >= 75 ? 'rgba(4, 113, 77, 0.9)' :       // Completed - Green
                                   value >= 50 ? 'rgba(59, 130, 246, 0.9)' :     // In Progress - Blue
                                   value >= 25 ? 'rgba(234, 179, 8, 0.9)' :      // In Study - Yellow
                                               'rgba(227, 22, 22, 0.9)';         // Not Viewed - Red
                        },
                        borderRadius: 8,
                        borderWidth: 0
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
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return `Performance: ${context.raw}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                display: true,
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            }
        );

        // Task Status Chart with improved styling
        new Chart(
            document.getElementById('taskStatusChart'),
            {
                type: 'doughnut',
                data: {
                    labels: ['Verified', 'Completed', 'In Progress', 'In Study', 'Not Viewed'],
                    datasets: [{
                        data: [
                            <?php echo "$total_verified, $total_completed, $total_in_progress, $total_in_study, $total_not_viewed"; ?>
                        ],
                        backgroundColor: [
                            'rgba(96, 98, 196, 0.9)',    // Purple - Verified
                            'rgba(4, 113, 77, 0.9)',     // Green - Completed
                            'rgba(59, 130, 246, 0.9)',    // Blue- In Progress
                            'rgba(234, 179, 8, 0.9)',    // Yellow - In Study
                            'rgba(227, 22, 22, 0.9)'      // Red - Not Viewed
                        ],
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    }
                }
            }
        );

        function viewDetails(userId) {
            window.location.href = `member_details.php?user_id=${userId}&period=<?php echo $period; ?>`;
        }

        function exportReport() {
            // Get the data from the table
            const table = document.querySelector('table');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            // Process header row - skip the Action column
            const headers = Array.from(rows[0].querySelectorAll('th'))
                .slice(0, -1) // Remove the Action column
                .map(header => escapeCsvField(header.textContent.trim()));
            
            // Process data rows
            const csvRows = rows.slice(1).map(row => {
                const cells = Array.from(row.querySelectorAll('td'))
                    .slice(0, -1) // Remove the Action column
                    .map((cell, index) => {
                        // For the username cell, get just the text content without the initials
                        if (index === 0) {
                            const usernameSpan = cell.querySelector('span.fw-medium');
                            return escapeCsvField(usernameSpan ? usernameSpan.textContent.trim() : cell.textContent.trim());
                        }
                        
                        // If the cell contains a span with performance score, get just the number
                        const scoreSpan = cell.querySelector('.performance-score');
                        const value = scoreSpan ? scoreSpan.textContent.replace('%', '') : cell.textContent;
                        return escapeCsvField(value.trim());
                    });
                return cells.join(',');
            });
            
            // Combine headers and rows
            const csv = [headers.join(','), ...csvRows].join('\n');
            
            // Create and trigger download
            const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            const fileName = `team_performance_report_${new Date().toISOString().split('T')[0]}.csv`;
            
            downloadLink.href = url;
            downloadLink.download = fileName;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            window.URL.revokeObjectURL(url);
        }

        // Helper function to escape CSV fields
        function escapeCsvField(field) {
            // If the field contains commas, quotes, or newlines, wrap it in quotes and escape existing quotes
            if (/[",\n]/.test(field)) {
                return `"${field.replace(/"/g, '""')}"`;
            }
            return field;
        }
    </script>
</body>
</html>