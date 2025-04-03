<?php
session_start();
require '../includes/db_connect.php';


// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'login.php';</script>";
}

$message = '';
$message_type = '';

// Fetch all teams
$teams_query = "SELECT team_id, team_name FROM teams";
$teams_result = $conn->query($teams_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $team_id = (int)$_POST['team_id'];
    $start_date = $_POST['start_date'];
    $due_date = $_POST['due_date'];
    $points = (int)$_POST['points'];
    
    $current_date = date('Y-m-d');
    
    // New validation for description length
    $word_count = str_word_count($description);
    if ($word_count < 20) {
        $message = "Description must be at least 20 words! (Current: $word_count words)";
        $message_type = "danger";
    }
    // New validation for team selection
    else if ($team_id <= 0) {
        $message = "Please select a valid team!";
        $message_type = "danger";
    }
    // Validate points
    else if ($points < 1 || $points > 100) {
        $message = "Points must be between 1 and 100!";
        $message_type = "danger";
    }
    // Validate start date
    else if (strtotime($start_date) < strtotime($current_date)) {
        $message = "Start date cannot be before current date!";
        $message_type = "danger";
    }
    // Validate dates
    else if (strtotime($start_date) > strtotime($due_date)) {
        $message = "Due date must be after start date!";
        $message_type = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO projects (title, description, team_id, start_date, due_date, created_by, points) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissii", $title, $description, $team_id, $start_date, $due_date, $_SESSION['user_id'], $points);
        
        if ($stmt->execute()) {
            $message = "Project assigned successfully!";
            $message_type = "success";
        } else {
            $message = "Error assigning project: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

function sanitizeInput($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Project | Team Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --success: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --grey: #6c757d;
            --card-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
            --btn-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            --transition: all 0.25s ease;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            color: var(--dark);
            line-height: 1.6;  /* Removed padding-top: 70px; */
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 70px;
            position: sticky; /* Added position sticky */
            top: 0; /* Added top 0 */
            z-index: 1000; /* Added z-index */
        }

        .back-button {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: var(--transition);
            padding: 8px 16px;
            border-radius: 8px;
        }

        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .page-header {
            background: #ffffff;
            padding: 2rem 0;
            margin-bottom: 2.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        
        .page-header h1 {
            font-weight: 600;
            color: var(--primary);
            margin: 0;
            font-size: 1.75rem;
            position: relative;
        }
        
        .page-header h1:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 2px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .card-body {
            padding: 2.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            letter-spacing: 0.3px;
        }

        .form-control, .form-select {
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.08);
            padding: 0.75rem 1rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            transition: var(--transition);
            font-size: 0.95rem;
            background-color: #fafbfc;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            background-color: #ffffff;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: none;
        }

        .btn {
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            transition: var(--transition);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            box-shadow: var(--btn-shadow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
            filter: brightness(1.05);
        }
        
        .btn i {
            margin-right: 8px;
        }

        .view-projects-btn {
            background-color: #ffffff;
            color: var(--primary);
            border: 1px solid rgba(67, 97, 238, 0.2);
            transition: var(--transition);
        }

        .view-projects-btn:hover {
            background-color: rgba(67, 97, 238, 0.05);
            color: var(--primary);
            border-color: rgba(67, 97, 238, 0.3);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            color: #0d8aa6;
        }
        
        .alert-danger {
            background-color: rgba(255, 92, 92, 0.1);
            color: #d32f2f;
        }
        
        .alert i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
        
        /* Custom form styling */
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        /* Hover effect for card */
        .card:hover {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
            transition: var(--transition);
        }
        
        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .page-header {
                padding: 1.5rem 0;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .d-flex.gap-3 {
                flex-direction: column;
                gap: 0.5rem !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid d-flex justify-content-between">
            <a class="navbar-brand" href="#">
                <i class="fas fa-project-diagram me-2"></i>
                Assign Project
            </a>
            <div>
                <a href="manage_projects.php" class="btn btn-outline-light">
                    <i class="fas fa-home me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>Assign New Project</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="projectForm" novalidate>
                            <div class="form-group">
                                <label for="title" class="form-label">Project Title</label>
                                <input type="text" class="form-control" id="title" name="title" placeholder="Enter project title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" placeholder="Describe project objectives and requirements" rows="4" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="points" class="form-label">Points (1-100)</label>
                                <input type="number" class="form-control" id="points" name="points" min="1" max="100" placeholder="Difficulty points" required>
                                <small class="text-muted">Assign project difficulty points</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="team_id" class="form-label">Assign to Team</label>
                                <select class="form-select" id="team_id" name="team_id" required>
                                    <option value="">Select a team</option>
                                    <?php while ($team = $teams_result->fetch_assoc()): ?>
                                        <option value="<?php echo $team['team_id']; ?>">
                                            <?php echo htmlspecialchars($team['team_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="due_date" class="form-label">Due Date</label>
                                        <input type="date" class="form-control" id="due_date" name="due_date" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-3 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i>Assign Project
                                </button>
                                <a href="manage_projects.php" class="btn view-projects-btn">
                                    <i class="fas fa-list"></i>View All Projects
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date for start date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').setAttribute('min', today);

        // Update due date minimum when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('due_date').setAttribute('min', this.value);
            // Clear due date if it's before start date
            if (document.getElementById('due_date').value < this.value) {
                document.getElementById('due_date').value = '';
            }
        });

        // Form validation
        document.getElementById('projectForm').addEventListener('submit', function(event) {
            let isValid = true;
            const startDate = document.getElementById('start_date').value;
            const dueDate = document.getElementById('due_date').value;
            const description = document.getElementById('description').value;
            const teamId = document.getElementById('team_id').value;

            // Description validation
            const wordCount = description.trim().split(/\s+/).length;
            if (wordCount < 20) {
                alert('Description must be at least 20 words! (Current: ' + wordCount + ' words)');
                isValid = false;
            }

            // Team validation
            if (!teamId) {
                alert('Please select a team!');
                isValid = false;
            }

            // Existing date validations
            if (startDate < today) {
                alert('Start date cannot be before today');
                isValid = false;
            }

            if (dueDate < startDate) {
                alert('Due date must be after start date');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>