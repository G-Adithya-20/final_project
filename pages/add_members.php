<?php
session_start();
require '../includes/db_connect.php';

// Check if user is either Manager or HR
if ($_SESSION['role'] != 'Manager' && $_SESSION['role'] != 'HR') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$team_id = $_GET['team_id'] ?? null;
if (!$team_id) {
    // header("Location: create_team.php");
    echo "<script>window.location.href = 'create_team.php';</script>";
}

$success_message = '';
$error_message = '';

// Add member to the team
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    $user_id = $_POST['user_id'];
    
    // Check if user is already in any team
    $check_query = "SELECT team_id FROM team_members WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    if ($check_stmt) {
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "This team member is already assigned to another team!";
        } else {
            // Add member to the team
            $query = "INSERT INTO team_members (team_id, user_id) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ii", $team_id, $user_id);
                if ($stmt->execute()) {
                    $success_message = "Team member added successfully!";
                } else {
                    $error_message = "Failed to add team member.";
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// Remove member from the team
if (isset($_GET['remove_member_id'])) {
    $member_id = $_GET['remove_member_id'];
    
    $query = "DELETE FROM team_members WHERE member_id = ? AND team_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $member_id, $team_id);
        if ($stmt->execute()) {
            $success_message = "Team member removed successfully!";
        } else {
            $error_message = "Failed to remove team member.";
        }
        $stmt->close();
    }
}

// Fetch team details
$team_query = "SELECT team_name FROM teams WHERE team_id = ?";
$team_stmt = $conn->prepare($team_query);
$team_stmt->bind_param("i", $team_id);
$team_stmt->execute();
$team_result = $team_stmt->get_result();
$team_name = $team_result->fetch_assoc()['team_name'];
$team_stmt->close();

// Fetch current team members
$members_query = "
    SELECT tm.member_id, u.username, u.user_id 
    FROM team_members tm
    JOIN users u ON tm.user_id = u.user_id
    WHERE tm.team_id = ?";
$stmt = $conn->prepare($members_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$members_result = $stmt->get_result();

// Fetch available team members
$users_query = "
    SELECT u.user_id, u.username 
    FROM users u
    LEFT JOIN team_members tm ON u.user_id = tm.user_id
    WHERE u.role = 'TeamMember' 
    AND tm.team_id IS NULL";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Team Members</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #eaefff;
            --secondary-color: #3a0ca3;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --dark-color: #2d3748;
            --light-color: #f8fafc;
            --border-radius: 12px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f7f9fc;
            color: #333;
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.4rem;
            letter-spacing: 0.5px;
        }
        
        .navbar .btn-outline-light {
            border-radius: 30px;
            padding: 8px 20px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .navbar .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .container {
            max-width: 1200px;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eaeaea;
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 2rem;
            background-color: white;
        }
        
        .card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .card-header h5 {
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-header h5 i {
            color: var(--primary-color);
            margin-right: 10px;
        }
        
        .card-body {
            padding: 1.75rem;
        }
        
        .team-name-badge {
            display: inline-block;
            background-color: var(--primary-light);
            color: var(--primary-color);
            font-weight: 500;
            padding: 6px 16px;
            border-radius: 30px;
            margin-bottom: 1.5rem;
        }
        
        .form-select, .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            font-size: 0.95rem;
            transition: var(--transition);
        }
        
        .form-select:focus, .form-control:focus {
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            border-color: var(--primary-color);
        }
        
        .btn {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 500;
            letter-spacing: 0.3px;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 0.875rem;
            border-radius: 8px;
        }
        
        .alert {
            border-radius: var(--border-radius);
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.15);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(231, 76, 60, 0.15);
            color: var(--danger-color);
        }
        
        .alert-warning {
            background-color: rgba(243, 156, 18, 0.15);
            color: var(--warning-color);
        }
        
        .alert-info {
            background-color: rgba(52, 152, 219, 0.15);
            color: var(--info-color);
        }
        
        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table th {
            background-color: #f7f9fc;
            color: var(--dark-color);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 16px;
            border-bottom: 1px solid #edf2f7;
        }
        
        .table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #edf2f7;
            color: #4a5568;
            font-size: 0.95rem;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover td {
            background-color: #f7faff;
        }
        
        .user-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            font-weight: 600;
            margin-right: 12px;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: #718096;
            margin-bottom: 0;
        }
        
        @media (max-width: 768px) {
            .card {
                margin-bottom: 1.5rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .table th, .table td {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users-gear me-2"></i>Team Management
            </a>
            <a href="create_team.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Teams
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h4 class="mb-0">Manage Team: <span class="text-primary"><?= htmlspecialchars($team_name) ?></span></h4>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Add Member Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-user-plus"></i>Add Team Member
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="team-name-badge">
                            <i class="fas fa-users me-2"></i><?= htmlspecialchars($team_name) ?>
                        </div>
                        
                        <?php if ($users_result->num_rows > 0): ?>
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="user_id" class="form-label small text-muted">Select Available Member</label>
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value="">Choose a team member</option>
                                        <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($user['user_id']) ?>">
                                                <?= htmlspecialchars($user['username']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_member" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Add to Team
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <p>No available team members to add.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Team Members List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-users"></i>Current Team Members
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($members_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px">ID</th>
                                            <th>Username</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($member = $members_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($member['member_id']) ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar">
                                                            <?= strtoupper(substr($member['username'], 0, 1)) ?>
                                                        </div>
                                                        <span><?= htmlspecialchars($member['username']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-danger btn-sm" 
                                                            onclick="confirmRemove(<?= $member['member_id'] ?>)">
                                                        <i class="fas fa-user-minus me-1"></i>
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <p>No members in this team yet.</p>
                                <small class="text-muted mt-2">Add members using the form on the left.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmRemove(memberId) {
            if (confirm('Are you sure you want to remove this member from the team?')) {
                window.location.href = `add_members.php?team_id=<?= $team_id ?>&remove_member_id=${memberId}`;
            }
        }
        
        // Auto-dismiss alerts after 5 seconds
        window.addEventListener('load', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    var bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>