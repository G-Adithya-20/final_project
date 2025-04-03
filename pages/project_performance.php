<?php
session_start();
require '../includes/db_connect.php';

// Check if user is HR
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'HR' && $_SESSION['role'] !== 'Manager')) {
    // header('Location: login.php');
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

// Get time interval from filter (default 1 month)
$interval = isset($_GET['interval']) ? $_GET['interval'] : '1m';

// Calculate date range based on interval
$end_date = date('Y-m-d');
switch($interval) {
    case '3m':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        break;
    case '6m':
        $start_date = date('Y-m-d', strtotime('-6 months'));
        break;
    case '1y':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        break;
    default: // 1m
        $start_date = date('Y-m-d', strtotime('-1 month'));
}

// Validate dates
if (!$start_date || !$end_date) {
    die('Invalid date range');
}

// Get team performance data
$team_query = "
    SELECT 
        t.team_id,
        t.team_name,
        u.username as team_lead,
        COUNT(p.project_id) as total_projects,
        COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.points ELSE 0 END), 0) as total_points,
        COALESCE(SUM(CASE WHEN p.status = 'verified' THEN 1 ELSE 0 END), 0) as verified_projects,
        COALESCE(AVG(CASE 
            WHEN p.status = 'verified' AND p.actual_end_date IS NOT NULL 
            THEN DATEDIFF(p.actual_end_date, p.start_date) 
            ELSE NULL 
        END), 0) as avg_completion_days,
        COALESCE(COUNT(DISTINCT pe.extension_id), 0) as extension_requests,
        COALESCE(AVG(CASE WHEN p.status = 'verified' 
            THEN p.points ELSE NULL END), 0) as avg_points_per_project
    FROM teams t
    LEFT JOIN users u ON t.team_lead_id = u.user_id
    LEFT JOIN projects p ON t.team_id = p.team_id
    LEFT JOIN project_extensions pe ON p.project_id = pe.project_id
    WHERE p.created_at BETWEEN ? AND ?
    GROUP BY t.team_id, t.team_name, u.username
    ORDER BY total_points DESC";

$stmt = $conn->prepare($team_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$team_result = $stmt->get_result();
$teams_data = $team_result->fetch_all(MYSQLI_ASSOC);

// Update the status query to include project titles and team info
$status_query = "
    SELECT 
        t.team_name,
        p.status,
        COUNT(*) as count,
        GROUP_CONCAT(p.title) as project_titles,
        GROUP_CONCAT(p.description) as project_descriptions,
        GROUP_CONCAT(p.team_id) as team_ids
    FROM projects p
    JOIN teams t ON p.team_id = t.team_id
    WHERE p.created_at BETWEEN ? AND ?
    GROUP BY t.team_name, p.status";

$stmt = $conn->prepare($status_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$status_result = $stmt->get_result();
$status_data = $status_result->fetch_all(MYSQLI_ASSOC);

// Initialize and populate projects_by_status array
$projects_by_status = [];
foreach ($status_data as $item) {
    $titles = explode(',', $item['project_titles']);
    $descriptions = explode(',', $item['project_descriptions']);
    $team_ids = explode(',', $item['team_ids']);
    
    foreach ($titles as $idx => $title) {
        $status = $item['status'];
        if (!isset($projects_by_status[$status])) {
            $projects_by_status[$status] = [];
        }
        $projects_by_status[$status][] = [
            'title' => $title,
            'description' => $descriptions[$idx] ?? '',
            'team' => $item['team_name']
        ];
    }
}

// Get timeline data
$timeline_query = "
    SELECT 
        DATE_FORMAT(p.actual_end_date, '%Y-%m') as month,
        COUNT(*) as completed_projects,
        AVG(p.points) as avg_points
    FROM projects p
    WHERE p.status = 'verified' 
    AND p.actual_end_date BETWEEN ? AND ?
    GROUP BY month
    ORDER BY month";

$stmt = $conn->prepare($timeline_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$timeline_result = $stmt->get_result();
$timeline_data = $timeline_result->fetch_all(MYSQLI_ASSOC);

// Handle CSV export
if (isset($_POST['export'])) {
    $export_data = array();
    foreach ($teams_data as $team) {
        $export_data[] = array(
            'Team Name' => $team['team_name'],
            'Team Lead' => $team['team_lead'],
            'Total Projects' => $team['total_projects'],
            'Verified Projects' => $team['verified_projects'],
            'Total Points' => $team['total_points'],
            'Avg Points per Project' => round($team['avg_points_per_project'], 1),
            'Avg Completion Days' => round($team['avg_completion_days'], 1),
            'Extension Requests' => $team['extension_requests']
        );
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="team_performance_report.csv"');
    $output = fopen('php://output', 'w');
    
    fputcsv($output, array_keys($export_data[0]));
    foreach ($export_data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Performance Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f72585;
            --info-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background: linear-gradient(135deg, #f6f8ff 0%, #f1f4ff 100%);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 0% 0%, rgba(67, 97, 238, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 100% 0%, rgba(76, 201, 240, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(67, 97, 238, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 0% 100%, rgba(76, 201, 240, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.374zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83zM22.343 0L13.857 8.485 15.272 9.9l7.9-7.9h-.83L25.172 0h-2.83zM32 0l-1.415 1.414 1.414 1.414L34.414 0H32zM37.657 0l-1.414 1.414L42.728 8l.485-.485L40 4.243 38.586 2.828 37.172 1.414 35.757 0h1.9zm-6.485 0L29.757 1.414 31.172 2.83 32.584 1.414 31.172 0h-2zM22.343 0L0 22.343l1.414 1.414L22.343 2.828l5.657 5.657L32 4.485 29.172 1.657 22.343 0zm5.657 0l3.657 3.657L27.8 0h.2zM0 5.373l1.414 1.414L8 0H5.172L0 5.172v.2zm0 5.656l3.657 3.657L8 10.343 0 2.343v8.686zM0 16.686l6.485 6.485L8 21.657 0 13.657v3.03zm0 5.657l7.9 7.9L8 27.8 0 19.8v2.543z' fill='%234361ee' fill-opacity='0.02' fill-rule='evenodd'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: -1;
            opacity: 0.3;
        }

        .dashboard-container {
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        /* Update card styles for better contrast with new background */
        .performance-card, .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .page-header {
            background: linear-gradient(135deg, 
                rgba(67, 97, 238, 0.95), 
                rgba(63, 55, 201, 0.95));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 0; /* Increased padding */
            min-height: 120px; /* Added min-height */
            width: 100%; /* Added full width */
        }

        .container-fluid {
            width: 100%;
            padding-right: 2.5rem; /* Increased padding */
            padding-left: 2.5rem; /* Increased padding */
        }

        /* Update dashboard container width */
        .dashboard-container {
            width: 100%;
            max-width: 100%;
            margin: 0;
            padding: 0 2rem;
        }

        /* Fix header row alignment */
        .page-header .row {
            width: 100%;
            margin: 0;
            align-items: center;
            justify-content: space-between;
        }

        /* Add subtle animation to background */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        body {
            animation: gradientShift 15s ease infinite;
            background-size: 400% 400%;
        }
        
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
        .stat-box p {
            margin-bottom: 5px;
            font-size: 0.75rem;
            color: #6c757d;
        }
        
        .stat-box h4 {
            margin: 0;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .metric-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            background: linear-gradient(to right, var(--success-color), var(--info-color));
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
        }
        
        .chart-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .chart-card .card-body {
            padding: 20px;
        }
        
        .chart-card .card-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
            display: flex;
            align-items: center;
        }
        
        .chart-card .card-title i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            margin: 20px 0;
        }
        
        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            background-image: linear-gradient(to right, var(--info-color), var(--success-color));
            border-radius: 5px;
        }
        
        .extension-badge {
            font-size: 0.75rem;
            background-color: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            color: #6c757d;
        }
        
        .back-button {
            padding: 10px 20px;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            background-color: white;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            float: right; /* Added float right */
        }
        
        .back-button:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(-5px);
        }
        
        .filter-buttons .btn {
            border-radius: 8px;
            padding: 8px 16px;
            margin-right: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .filter-buttons .btn.active {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 3px 6px rgba(67, 97, 238, 0.3);
        }
        
        .export-btn {
            background: linear-gradient(to right, #20bf55, #01baef);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 3px 6px rgba(32, 191, 85, 0.3);
        }
        
        .export-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 10px rgba(32, 191, 85, 0.4);
        }
        
        .chart-legend {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 15px;
            font-size: 0.8rem;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            margin-right: 5px;
        }
        
        .chart-tooltip {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: none;
        }
        
        .status-labels {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }
        
        .status-label {
            display: flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .status-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .no-data-alert {
            background-color: rgba(67, 97, 238, 0.1);
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            border-radius: 8px;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .card-bg-pattern {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            opacity: 0.05;
            background-image: radial-gradient(circle, #000 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .slide-up {
            animation: slideUp 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .status-legend {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .legend-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .legend-info {
            display: flex;
            flex-direction: column;
        }

        .legend-label {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .legend-desc {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        /* Update the chart options */
        #statusChart {
            margin-bottom: 1rem;
        }

        /* Enhanced Status Distribution Styles */
        .status-legend {
            margin-top: 2.5rem;
            padding: 2.5rem;
            background: linear-gradient(145deg, #ffffff, #f8faff);
            border-radius: 24px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                inset 0 -3px 6px rgba(67, 97, 238, 0.05);
            position: relative;
            overflow: hidden;
        }

        .status-legend::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%234361ee' fill-opacity='0.02' fill-rule='evenodd'/%3E%3C/svg%3E");
            z-index: 0;
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .legend-item {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid transparent;
            box-shadow: 
                0 4px 15px rgba(0, 0, 0, 0.05),
                inset 0 2px 4px rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .legend-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .status-dot {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: white;
            position: relative;
            overflow: hidden;
            background-size: 200% 200%;
            animation: gradientShift 3s ease infinite;
        }

        .status-dot::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.2) 0%, transparent 100%);
            animation: shimmer 2s infinite;
        }

        .legend-info {
            flex: 1;
        }

        .legend-label {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .legend-desc {
            color: #64748b;
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .legend-meta {
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 500;
        }

        /* Status-specific Enhanced Gradients - Updated colors to match descriptions */
        .status-not-viewed {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);  /* Red */
        }

        .status-in-study {
            background: linear-gradient(135deg, #eab308 0%, #facc15 100%);  /* Yellow */
        }

        .status-in-progress {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);  /* Blue */
        }

        .status-completed {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);  /* Green */
        }

        .status-verified {
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);  /* Purple */
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) rotate(25deg); }
            100% { transform: translateX(100%) rotate(25deg); }
        }

        /* Updated chart container style */
        .chart-container {
            position: relative;
            height: 350px;
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(145deg, #ffffff, #f6f7ff);
            border-radius: 20px;
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.08),
                inset 0 -3px 6px rgba(67, 97, 238, 0.05);
        }

        /* Enhanced Chart Card Styles */
        .chart-card {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .chart-container {
            padding: 2rem;
            height: 400px; /* Increased height */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chart-container {
                padding: 1rem;
                height: 300px;
            }
        }

        /* Update just the status dot and icon alignment styles */
        .status-dot {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            margin-right: 15px; /* Added consistent margin */
        }

        .status-dot i {
            font-size: 1.25rem; /* Consistent icon size */
            position: relative;
            z-index: 2;
            margin: 0; /* Reset margins */
            line-height: 1; /* Ensure vertical centering */
        }

        .legend-item {
            display: flex;
            align-items: flex-start; /* Align to top */
            padding: 1.2rem;
            background: white;
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .legend-info {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            padding-top: 4px; /* Align text with icon */
        }

        /* Project Details Panel Styles */
        .project-details-panel {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .panel-header {
            padding: 1.5rem;
            background: linear-gradient(145deg, #ffffff, #f8faff);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .project-list-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .project-list-container::-webkit-scrollbar {
            width: 6px;
        }

        .project-list-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 3px;
        }

        .project-list-container::-webkit-scrollbar-thumb {
            background: rgba(67, 97, 238, 0.2);
            border-radius: 3px;
            transition: background 0.3s ease;
        }

        .project-list-container::-webkit-scrollbar-thumb:hover {
            background: rgba(67, 97, 238, 0.3);
        }

        .status-group {
            margin-bottom: 1.5rem;
        }

        .status-group:last-child {
            margin-bottom: 0;
        }

        .status-header {
            padding: 0.75rem;
            background: rgba(67, 97, 238, 0.05);
            border-radius: 8px;
            margin-bottom: 0.75rem;
            font-weight: 500;
        }

        .project-list {
            padding-left: 0.5rem;
        }

        .project-item {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .project-item:hover {
            background: rgba(67, 97, 238, 0.03);
            transform: translateX(5px);
            border-color: rgba(67, 97, 238, 0.1);
        }

        .project-title {
            font-weight: 500;
            margin-bottom: 0.35rem;
            color: var(--dark-color);
        }

        .project-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .details-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        /* Status indicator colors - update if needed */
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
            display: inline-block;
        }

        /* Enhanced Project Distribution Panel Styles */
        .project-details-panel {
            background: linear-gradient(145deg, #ffffff, #f8faff);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            height: 100%;
        }

        .panel-header {
            padding: 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .details-title {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .project-list-container {
            padding: 1.5rem;
            max-height: 600px;
            overflow-y: auto;
        }

        .status-group {
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.5s ease forwards;
        }

        .status-header {
            background: white;
            border-radius: 15px;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .status-header:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
            position: relative;
        }

        .status-indicator::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            background: inherit;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }

        .status-indicator.notviewed { background: #ef4444; }
        .status-indicator.instudy { background: #eab308; }
        .status-indicator.inprogress { background: #3b82f6; }
        .status-indicator.completed { background: #10b981; }
        .status-indicator.verified { background: #6366f1; }

        .project-list {
            display: grid;
            gap: 0.75rem;
            padding: 0.5rem;
        }

        .project-item {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .project-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .project-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-color);
        }

        .project-item:hover::before {
            opacity: 1;
        }

        .project-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .project-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .team-name {
            color: var(--primary-color);
            font-weight: 500;
        }

        .badge.rounded-pill {
            font-size: 0.75rem;
            padding: 0.35rem 0.75rem;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            font-weight: 600;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.5); opacity: 0; }
            100% { transform: scale(1); opacity: 0.3; }
        }

        /* Custom scrollbar for project list */
        .project-list-container::-webkit-scrollbar {
            width: 6px;
        }

        .project-list-container::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 3px;
        }

        .project-list-container::-webkit-scrollbar-thumb {
            background: rgba(67, 97, 238, 0.2);
            border-radius: 3px;
            transition: background 0.3s ease;
        }

        .project-list-container::-webkit-scrollbar-thumb:hover {
            background: rgba(67, 97, 238, 0.3);
        }

        /* Enhanced Header Styles */
        .page-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            padding: 2.5rem 0;
            margin-bottom: 3rem;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.15);
        }

        .page-header h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .date-range {
            opacity: 0.9;
            font-size: 1rem;
        }

        /* Enhanced Filter Buttons */
        .filter-buttons .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .filter-buttons .btn.active {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        /* Enhanced Project Distribution Panel */
        .status-header {
            font-size: 1.2rem;
            padding: 1.2rem 1.5rem;
            background: linear-gradient(145deg, #ffffff, #f8faff);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .status-header .badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
        }

        .project-item {
            background: white;
            border-radius: 14px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .project-item:hover {
            transform: translateX(8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .project-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
        }

        /* Status Indicators */
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 12px;
            position: relative;
        }

        .status-indicator::after {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: inherit;
            opacity: 0.3;
            animation: pulse 2s infinite;
        }

        /* Project List Container */
        .project-list-container {
            max-height: 600px;
            overflow-y: auto;
            padding: 1.5rem;
            scrollbar-width: thin;
            scrollbar-color: rgba(67, 97, 238, 0.2) transparent;
        }

        .project-details-panel {
            background: linear-gradient(145deg, #ffffff, #f8faff);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            height: 100%;
        }

        /* Animations */
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.5); opacity: 0; }
            100% { transform: scale(1); opacity: 0.3; }
        }

        /* Updated Status Distribution Styles */
        .status-dot {
            width: 36px; /* Reduced from 64px */
            height: 36px; /* Reduced from 64px */
            border-radius: 10px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem; /* Reduced from 1.75rem */
            color: white;
            margin-right: 12px;
        }

        .status-dot i {
            font-size: 1rem; /* Reduced from 1.25rem */
        }

        .legend-item {
            padding: 1rem; /* Reduced from 1.5rem */
            gap: 0.75rem; /* Reduced from 1.25rem */
        }

        .legend-label {
            font-size: 1rem; /* Reduced from 1.25rem */
            margin-bottom: 0.25rem; /* Reduced from 0.5rem */
        }

        .legend-desc {
            font-size: 0.85rem; /* Reduced from 0.95rem */
            line-height: 1.4;
            margin-bottom: 0.25rem;
        }

        .legend-meta {
            font-size: 0.75rem; /* Reduced from 0.85rem */
        }

        /* Enhanced grid layout for better spacing */
        .legend-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Reduced from 280px */
            gap: 1rem; /* Reduced from 1.5rem */
        }

        .status-legend {
            padding: 1.5rem; /* Reduced from 2.5rem */
        }

        /* Status Distribution Panel adjustments */
        .project-details-panel {
            font-size: 0.9rem; /* Added to reduce overall text size */
        }

        .project-title {
            font-size: 0.9rem; /* Reduced from 1.1rem */
        }

        .project-meta {
            font-size: 0.8rem; /* Reduced from 0.85rem */
        }

        /* Project Distribution Panel adjustments */
        .project-details-panel {
            font-size: 0.85rem; /* Reduced base font size */
        }

        .panel-header .details-title {
            font-size: 1rem; /* Reduced from 1.25rem */
            margin-bottom: 0.3rem; /* Reduced spacing */
        }

        .panel-header p.small {
            font-size: 0.75rem; /* Smaller description text */
        }

        .status-header {
            font-size: 0.9rem; /* Reduced from 1.2rem */
            padding: 0.8rem 1rem; /* Reduced padding */
        }

        .status-header .badge {
            font-size: 0.75rem; /* Reduced from 1rem */
            padding: 0.35rem 0.7rem; /* Reduced padding */
        }

        .project-item {
            padding: 0.8rem; /* Reduced from 1.2rem */
            margin-bottom: 0.5rem; /* Reduced spacing */
        }

        .project-title {
            font-size: 0.85rem; /* Reduced from 0.95rem */
            margin-bottom: 0.3rem; /* Reduced spacing */
        }

        .project-meta {
            font-size: 0.75rem; /* Reduced from 0.8rem */
        }

        .project-meta i {
            font-size: 0.8rem; /* Reduced icon size */
        }

        .performance-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 3px solid rgba(67, 97, 238, 0.2); /* Increased thickness from 2px to 3px and opacity */
            border-radius: 15px; /* Increased border radius */
            box-shadow: 
                0 8px 24px rgba(149, 157, 165, 0.1),
                inset 0 2px 4px rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }

        .performance-card:hover {
            border-color: rgba(67, 97, 238, 0.5); /* Increased hover opacity */
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(149, 157, 165, 0.2);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="page-header animate__animated animate__fadeIn">
            <div class="row">
                <div class="col-md-7">
                    <h2><i class="bi bi-graph-up"></i> Project Performance Analysis</h2>
                    <span class="date-range">
                        <i class="bi bi-calendar-range"></i>
                        <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?>
                    </span>
                </div>
                <div class="col-md-4 text-end">
                    <div class="filter-buttons btn-group mb-2">
                        <a href="?interval=1m" class="btn btn-outline-light <?php echo $interval === '1m' ? 'active' : ''; ?>">1 Month</a>
                        <a href="?interval=3m" class="btn btn-outline-light <?php echo $interval === '3m' ? 'active' : ''; ?>">3 Months</a>
                        <a href="?interval=6m" class="btn btn-outline-light <?php echo $interval === '6m' ? 'active' : ''; ?>">6 Months</a>
                        <a href="?interval=1y" class="btn btn-outline-light <?php echo $interval === '1y' ? 'active' : ''; ?>">1 Year</a>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <form method="POST" class="d-inline-block">
                            <button type="submit" name="export" class="export-btn">
                                <i class="bi bi-download"></i> Export Report
                            </button>
                        </form>
                        <a href="hr_dashboard.php" class="btn btn-outline-light">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Performance Cards -->
        <div class="row mb-4">
            <?php if (empty($teams_data)): ?>
                <div class="col-12">
                    <div class="no-data-alert animate__animated animate__fadeIn">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        No team performance data available for the selected period.
                    </div>
                </div>
            <?php else: ?>
                <?php $i = 0; foreach ($teams_data as $team): $i++; ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="performance-card animate__animated animate__fadeIn" style="animation-delay: <?php echo $i * 0.1; ?>s">
                        <div class="card-bg-pattern"></div>
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-people-fill me-2"></i>
                                <?php echo htmlspecialchars($team['team_name']); ?>
                                
                                <?php if ($team['total_points'] > 0): ?>
                                    <span class="badge metric-badge"><?php echo $team['total_points']; ?> pts</span>
                                <?php endif; ?>
                            </h5>
                            <h6 class="card-subtitle text-muted">
                                <i class="bi bi-person-check"></i>
                                Lead: <?php echo htmlspecialchars($team['team_lead']); ?>
                            </h6>
                            
                            <div class="team-stats">
                                <div class="stat-box">
                                    <p>Projects</p>
                                    <h4><?php echo $team['total_projects']; ?></h4>
                                </div>
                                
                                <div class="stat-box">
                                    <p>Avg Points</p>
                                    <h4><?php echo round($team['avg_points_per_project'], 1); ?></h4>
                                </div>
                            </div>
                            
                            <p class="mb-1 small">Completion Rate</p>
                            <div class="progress">
                                <?php 
                                $completion_rate = $team['total_projects'] > 0 
                                    ? ($team['verified_projects'] / $team['total_projects'] * 100) 
                                    : 0;
                                ?>
                                <div class="progress-bar" role="progressbar" 
                                    style="width: <?php echo $completion_rate; ?>%" 
                                    aria-valuenow="<?php echo round($completion_rate); ?>" 
                                    aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted"><?php echo round($completion_rate); ?>% Complete</small>
                                <span class="extension-badge">
                                    <i class="bi bi-hourglass-split"></i>
                                    <?php echo $team['extension_requests']; ?> Extensions
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Charts Section -->
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="chart-card animate__animated animate__fadeIn mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-center">
                            <i class="bi bi-bar-chart-fill"></i>
                            Team Performance Comparison
                        </h5>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution Chart -->
            <div class="col-md-10">
                <div class="chart-card animate__animated animate__fadeIn mb-4">
                    <div class="card-body">
                        <h5 class="card-title text-center">
                            <i class="bi bi-pie-chart-fill"></i>
                            Project Status Distribution
                        </h5>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="status-legend h-100">
                                    <div class="legend-grid">
                                        <div class="legend-item">
                                            <div class="status-dot status-not-viewed">
                                                <i class="bi bi-hourglass"></i> <!-- Changed from bi-hourglass-start to bi-hourglass -->
                                            </div>
                                            <div class="legend-info">
                                                <span class="legend-label">Waiting to Start</span>
                                                <span class="legend-desc">Projects that have been created but not yet reviewed</span>
                                                <small class="text-muted">Status: Not Viewed</small>
                                            </div>
                                        </div>
                                        <div class="legend-item">
                                            <div class="status-dot status-in-study">
                                                <i class="bi bi-search"></i>
                                            </div>
                                            <div class="legend-info">
                                                <span class="legend-label">Analysis Phase</span>
                                                <span class="legend-desc">Projects being evaluated and planned</span>
                                                <small class="text-muted">Status: In Study</small>
                                            </div>
                                        </div>
                                        <div class="legend-item">
                                            <div class="status-dot status-in-progress">
                                                <i class="bi bi-gear-wide-connected"></i>
                                            </div>
                                            <div class="legend-info">
                                                <span class="legend-label">Active Development</span>
                                                <span class="legend-desc">Projects currently under active development</span>
                                                <small class="text-muted">Status: In Progress</small>
                                            </div>
                                        </div>
                                        <div class="legend-item">
                                            <div class="status-dot status-completed">
                                                <i class="bi bi-check2-all"></i>
                                            </div>
                                            <div class="legend-info">
                                                <span class="legend-label">Ready for Review</span>
                                                <span class="legend-desc">Projects completed and awaiting verification</span>
                                                <small class="text-muted">Status: Completed</small>
                                            </div>
                                        </div>
                                        <div class="legend-item">
                                            <div class="status-dot status-verified">
                                                <i class="bi bi-trophy"></i>
                                            </div>
                                            <div class="legend-info">
                                                <span class="legend-label">Successfully Delivered</span>
                                                <span class="legend-desc">Projects that have passed final verification by the manager</span>
                                                <small class="text-muted">Status: Verified</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="project-details-panel h-100">
                                    <div class="panel-header">
                                        <h6 class="details-title">Project Distribution</h6>
                                        <p class="text-muted small mb-0">Projects grouped by current status</p>
                                    </div>
                                    <div class="project-list-container">
                                        <?php
                                        // Existing PHP code for $projects_by_status remains unchanged
                                        foreach ($projects_by_status as $status => $projects): ?>
                                            <div class="status-group">
                                                <div class="status-header">
                                                    <div class="d-flex align-items-center">
                                                        <span class="status-indicator <?php echo $status; ?>"></span>
                                                        <?php echo ucfirst($status); ?>
                                                        <span class="badge rounded-pill bg-light text-dark ms-2">
                                                            <?php echo count($projects); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="project-list">
                                                    <?php foreach ($projects as $project): ?>
                                                        <div class="project-item">
                                                            <div class="project-title"><?php echo htmlspecialchars($project['title']); ?></div>
                                                            <div class="project-meta">
                                                                <i class="bi bi-people-fill"></i>
                                                                <span class="team-name"><?php echo htmlspecialchars($project['team']); ?></span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-10">
                <div class="chart-card animate__animated animate__fadeIn">
                    <div class="card-body">
                        <h5 class="card-title text-center">
                            <i class="bi bi-graph-up-arrow"></i>
                            Project Completion Trends
                        </h5>
                        <div class="chart-container">
                            <canvas id="timelineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Prepare data for charts
        const teamsData = <?php echo json_encode($teams_data); ?>;
        const statusData = <?php echo json_encode($status_data); ?>;
        const timelineData = <?php echo json_encode($timeline_data); ?>;

        // Function to add animation to elements
        function animateElements() {
            const elements = document.querySelectorAll('.animate__animated');
            elements.forEach((el, index) => {
                el.classList.add('animate__fadeIn');
                el.style.animationDelay = `${index * 0.1}s`;
            });
        }

        // Performance Charts
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        const performanceGradient = performanceCtx.createLinearGradient(0, 0, 0, 400);
        performanceGradient.addColorStop(0, 'rgba(54, 162, 235, 0.8)');
        performanceGradient.addColorStop(1, 'rgba(54, 162, 235, 0.2)');

        const daysGradient = performanceCtx.createLinearGradient(0, 0, 0, 400);
        daysGradient.addColorStop(0, 'rgba(255, 99, 132, 0.8)');
        daysGradient.addColorStop(1, 'rgba(255, 99, 132, 0.2)');

        new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: teamsData.map(team => team.team_name),
                datasets: [
                    {
                        label: 'Total Points',
                        data: teamsData.map(team => team.total_points),
                        backgroundColor: performanceGradient,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y',
                        barPercentage: 0.6,
                        categoryPercentage: 0.7,
                        borderRadius: 6
                    },
                    {
                        label: 'Avg Completion Days',
                        data: teamsData.map(team => team.avg_completion_days),
                        backgroundColor: daysGradient,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1',
                        barPercentage: 0.6,
                        categoryPercentage: 0.7,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animations: {
                    tension: {
                        duration: 1000,
                        easing: 'linear',
                        from: 0.8,
                        to: 0.2,
                        loop: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Total Points',
                            color: 'rgba(54, 162, 235, 1)',
                            font: { weight: 'bold' }
                        },
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: 'rgba(54, 162, 235, 0.8)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Average Days to Complete',
                            color: 'rgba(255, 99, 132, 1)',
                            font: { weight: 'bold' }
                        },
                        grid: {
                            drawOnChartArea: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: 'rgba(255, 99, 132, 0.8)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 14 },
                        padding: 15,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return `Total Points: ${context.raw}`;
                                } else {
                                    return `Avg Days: ${context.raw.toFixed(1)}`;
                                }
                            }
                        }
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusLabels = ['notviewed', 'instudy', 'inprogress', 'completed', 'verified'];
        const statusColors = [
            'rgba(227, 22, 22, 0.9)',    // Red - Not Viewed
            'rgba(234, 179, 8, 0.9)',    // Yellow - In Study
            'rgba(59, 130, 246, 0.9)',   // Blue - In Progress
            'rgba(4, 113, 77, 0.9)',   // Green - Completed
            'rgba(96, 98, 196, 0.9)'    // Purple - Verified
        ];
        
        const statusBorders = [
            'rgba(239, 68, 68, 1)',      // Red - Not Viewed
            'rgba(234, 179, 8, 1)',      // Yellow - In Study
            'rgba(59, 130, 246, 1)',     // Blue - In Progress
            'rgba(16, 185, 129, 1)',     // Green - Completed
            'rgba(99, 102, 241, 1)'      // Purple - Verified
        ];

        const statusLabelsFormatted = statusLabels.map(label => {
            return label.charAt(0).toUpperCase() + label.slice(1).replace(/([A-Z])/g, ' $1');
        });

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabelsFormatted,
                datasets: [{
                    data: statusLabels.map(status => 
                        statusData.reduce((sum, item) => 
                            sum + (item.status === status ? parseInt(item.count) : 0), 0)
                    ),
                    backgroundColor: statusColors,
                    borderColor: statusBorders,
                    borderWidth: 2,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 14 },
                        padding: 15,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.raw / total) * 100).toFixed(1);
                                return `${context.label}: ${context.raw} projects (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            }
        });

        // Timeline Chart
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        const projectsGradient = timelineCtx.createLinearGradient(0, 0, 0, 400);
        projectsGradient.addColorStop(0, 'rgba(75, 192, 192, 0.8)');
        projectsGradient.addColorStop(1, 'rgba(75, 192, 192, 0.1)');

        const pointsGradient = timelineCtx.createLinearGradient(0, 0, 0, 400);
        pointsGradient.addColorStop(0, 'rgba(153, 102, 255, 0.8)');
        pointsGradient.addColorStop(1, 'rgba(153, 102, 255, 0.1)');

        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: timelineData.map(item => {
                    const [year, month] = item.month.split('-');
                    return new Date(year, month - 1).toLocaleDateString('default', { 
                        month: 'short', 
                        year: 'numeric' 
                    });
                }),
                datasets: [
                    {
                        label: 'Completed Projects',
                        data: timelineData.map(item => item.completed_projects),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: projectsGradient,
                        yAxisID: 'y',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 5,
                        pointBackgroundColor: 'rgba(75, 192, 192, 1)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: 'white',
                        pointHoverBorderColor: 'rgba(75, 192, 192, 1)',
                        pointHoverBorderWidth: 2
                    },
                    {
                        label: 'Average Points',
                        data: timelineData.map(item => parseFloat(item.avg_points)),
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: pointsGradient,
                        yAxisID: 'y1',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 5,
                        pointBackgroundColor: 'rgba(153, 102, 255, 1)',
                        pointBorderColor: 'white',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: 'white',
                        pointHoverBorderColor: 'rgba(153, 102, 255, 1)',
                        pointHoverBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Number of Projects',
                            color: 'rgba(75, 192, 192, 1)',
                            font: { weight: 'bold' }
                        },
                        grid: {
                            display: true,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: 'rgba(75, 192, 192, 0.8)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Average Points',
                            color: 'rgba(153, 102, 255, 1)',
                            font: { weight: 'bold' }
                        },
                        grid: {
                            drawOnChartArea: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: 'rgba(153, 102, 255, 0.8)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: { size: 14 },
                        bodyFont: { size: 14 },
                        padding: 15,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return `Completed Projects: ${context.raw}`;
                                } else {
                                    return `Average Points: ${context.raw.toFixed(1)}`;
                                }
                            }
                        }
                    }
                },
                animations: {
                    tension: {
                        duration: 1000,
                        easing: 'linear',
                        from: 0.8,
                        to: 0.2,
                        loop: false
                    }
                }
            }
        });

        // Add event listeners for hover effects
        document.querySelectorAll('.performance-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('animate__pulse');
            });
            card.addEventListener('mouseleave', function() {
                this.classList.remove('animate__pulse');
            });
        });

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            animateElements();
        });

        // Handle responsive behavior
        window.addEventListener('resize', function() {
            const width = window.innerWidth;
            const chartCards = document.querySelectorAll('.chart-card');
            
            if (width < 768) {
                chartCards.forEach(card => {
                    const container = card.querySelector('.chart-container');
                    container.style.height = '250px';
                });
            } else {
                chartCards.forEach(card => {
                    const container = card.querySelector('.chart-container');
                    container.style.height = '350px';
                });
            }
        });

        // Export functionality
        document.querySelector('.export-btn').addEventListener('click', function(e) {
            // This is a placeholder - actual export functionality would be implemented server-side
            e.preventDefault();
            alert('Exporting report...');
            // You would typically submit the form here or make an AJAX request
        });
    </script>
</body>
</html>
