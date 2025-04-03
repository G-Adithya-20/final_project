<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'HR') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$success_message = '';
$error_message = '';

// Handle team creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_name = $_POST['team_name'];
    $team_lead_id = $_POST['team_lead_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into teams table
        $query = "INSERT INTO teams (team_name, team_lead_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("si", $team_name, $team_lead_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create team: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->commit();
        $success_message = "Team created successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Fetch available team leads
$team_leads_query = "
    SELECT u.user_id, u.username 
    FROM users u 
    LEFT JOIN teams t ON u.user_id = t.team_lead_id
    WHERE u.role = 'TeamLead' 
    AND t.team_id IS NULL";
$team_leads_result = $conn->query($team_leads_query);

// Fetch all teams with their team leads
$teams_query = "
    SELECT t.team_id, t.team_name, u.username AS team_lead 
    FROM teams t 
    JOIN users u ON t.team_lead_id = u.user_id";
$teams_result = $conn->query($teams_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4C6FFF;
            --primary-hover: #3A5AE0;
            --secondary-color: #6C757D;
            --success-color: #10B981;
            --danger-color: #EF4444;
            --warning-color: #F59E0B;
            --info-color: #3B82F6;
            --light-bg: #F9FAFB;
            --dark-bg: #1E293B;
            --border-color: #E5E7EB;
            --text-primary: #1E293B;
            --text-secondary: #64748B;
            --text-light: #94A3B8;
            --box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
            min-height: 100vh;
        }

        /* Navbar Styles */
        .navbar {
            background: linear-gradient(120deg, #4C6FFF, #6E8DFF);
            padding: 1rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }

        .navbar .btn-outline-light {
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.7);
        }

        .navbar .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }

        .card-header h5 {
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Form Styles */
        .form-label {
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(76, 111, 255, 0.15);
        }

        .input-group-text {
            background-color: transparent;
            border-right: none;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        /* Button Styles */
        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(76, 111, 255, 0.2);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 8px;
        }

        /* Table Styles */
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table th {
            background-color: rgba(76, 111, 255, 0.08);
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background-color: rgba(76, 111, 255, 0.03);
        }

        /* Badge Styles */
        .badge {
            padding: 0.6rem 1rem;
            font-weight: 500;
            border-radius: 50px;
            font-size: 0.8rem;
        }

        .badge.bg-primary {
            background-color: rgba(76, 111, 255, 0.1) !important;
            color: var(--primary-color);
        }

        /* Alert Styles */
        .alert {
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--danger-color);
            color: var(--danger-color);
        }

        .alert-warning {
            background-color: rgba(245, 158, 11, 0.1);
            border-left: 4px solid var(--warning-color);
            color: var(--warning-color);
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        /* Team Card Styles */
        .team-card {
            border-radius: 10px;
            background-color: white;
            border: 1px solid var(--border-color);
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .team-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .team-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(76, 111, 255, 0.1);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 0.75rem;
        }

        /* Input-with-icon style */
        .input-with-icon {
            position: relative;
        }

        .input-with-icon .form-control {
            padding-left: 2.5rem;
        }

        .input-with-icon .icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }

        /* Custom scroll bar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-layer-group me-2"></i>Team Management
            </a>
            <a href="hr_dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-home me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container fade-in">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 fs-4"></i>
                    <div>
                        <strong>Success!</strong> <?= htmlspecialchars($success_message) ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                    <div>
                        <strong>Error!</strong> <?= htmlspecialchars($error_message) ?>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Create Team Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Create New Team
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($team_leads_result->num_rows > 0): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="team_name" class="form-label">Team Name</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-users icon"></i>
                                        <input type="text" class="form-control" id="team_name" name="team_name" placeholder="Enter team name" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="team_lead_id" class="form-label">Team Lead</label>
                                    <div class="input-with-icon">
                                        <i class="fas fa-user-tie icon"></i>
                                        <select class="form-select" id="team_lead_id" name="team_lead_id" required>
                                            <option value="">Select Team Lead</option>
                                            <?php while ($lead = $team_leads_result->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($lead['user_id']) ?>">
                                                    <?= htmlspecialchars($lead['username']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Create Team
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                                    <div>
                                        All team leads are currently assigned to teams.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats Card -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>Team Statistics
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <h3 class="mb-1 fw-bold text-primary">
                                        <?= $teams_result->num_rows ?>
                                    </h3>
                                    <p class="mb-0 text-secondary">Teams</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded-3 text-center">
                                    <h3 class="mb-1 fw-bold text-primary">
                                        <?= $team_leads_result->num_rows ?>
                                    </h3>
                                    <p class="mb-0 text-secondary">Available Leads</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teams List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title">
                            <i class="fas fa-list me-2 text-primary"></i>Existing Teams
                        </h5>
                        <div class="input-with-icon" style="width: 250px;">
                            <i class="fas fa-search icon"></i>
                            <input type="text" class="form-control form-control-sm" id="teamSearch" placeholder="Search teams...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                        <th><i class="fas fa-layer-group me-1"></i>Team Name</th>
                                        <th><i class="fas fa-user-tie me-1"></i>Team Lead</th>
                                        <th><i class="fas fa-cog me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $team_count = 0;
                                    while ($team = $teams_result->fetch_assoc()): 
                                        $team_count++;
                                        $avatar_letter = strtoupper(substr($team['team_name'], 0, 1));
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($team['team_id']) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="team-avatar">
                                                        <?= $avatar_letter ?>
                                                    </div>
                                                    <?= htmlspecialchars($team['team_name']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-user-tie me-1"></i>
                                                    <?= htmlspecialchars($team['team_lead']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="add_members.php?team_id=<?= htmlspecialchars($team['team_id']) ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-users me-1"></i>
                                                    Manage Members
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if ($team_count === 0): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <i class="fas fa-info-circle me-2 text-secondary"></i>
                                                No teams found. Create your first team!
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Simple search functionality
        document.getElementById('teamSearch').addEventListener('keyup', function() {
            let input = this.value.toLowerCase();
            let rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                if(text.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>