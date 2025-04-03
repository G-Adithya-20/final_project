<?php
 session_start();
 require '../includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            background: linear-gradient(135deg, #3a66db, #2952c8);
            height: 120px;
            padding: 2.5rem 0;
            display: flex;
            align-items: center;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .back-btn {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .back-btn:hover {
            opacity: 0.85;
            color: white;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        /* Special styling for Schedule Meeting card */
        .card.schedule-card {
            background: linear-gradient(to right, rgba(58, 102, 219, 0.05), rgba(41, 82, 200, 0.05));
            border-left: 4px solid #3a66db;
            box-shadow: 0 5px 20px rgba(58, 102, 219, 0.1);
        }

        .card.schedule-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(58, 102, 219, 0.1);
        }

        .card.schedule-card .card-header h4 {
            color: #3a66db;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card.schedule-card .card-header h4 i {
            font-size: 1.4rem;
        }

        .card.schedule-card .card-body {
            padding: 2rem;
        }

        .card.schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(58, 102, 219, 0.15);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 20px 24px;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 600;
            color: #2d3748;
            font-size: 18px;
        }

        .card-body {
            padding: 24px;
        }

        .form-label {
            font-weight: 500;
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: #3a66db;
            box-shadow: 0 0 0 3px rgba(58, 102, 219, 0.12);
        }

        .btn {
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 18px;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #3a66db;
            border: none;
        }

        .btn-primary:hover {
            background-color: #2952c8;
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: #e53e3e;
            border: none;
        }

        .btn-danger:hover {
            background-color: #c53030;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }

        .btn i {
            margin-right: 6px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            background-color: rgba(58, 102, 219, 0.1);
            color: #3a66db;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            font-size: 14px;
            color: #4a5568;
            border-bottom-width: 1px;
            padding: 16px;
        }

        .table td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }

        .alert {
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .form-check-input:checked {
            background-color: #3a66db;
            border-color: #3a66db;
        }

        .no-meetings {
            padding: 48px 0;
            text-align: center;
            color: #718096;
        }

        .no-meetings i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #cbd5e0;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .toast {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .copy-link-group {
            display: flex;
            max-width: 300px;
        }

        .copy-link-group .form-control {
            border-radius: 8px 0 0 8px;
            font-size: 14px;
        }

        .copy-link-group .btn {
            border-radius: 0 8px 8px 0;
            border: 1px solid #e2e8f0;
            border-left: none;
            color: #4a5568;
        }

        .copy-link-group .btn:hover {
            background-color: #f8fafc;
            color: #3a66db;
        }

        /* Custom checkbox styling */
        .manager-checkbox-container {
            background: rgba(58, 102, 219, 0.05);
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(58, 102, 219, 0.1);
            margin-top: 0.5rem;
        }

        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.25em;
            background-color: #fff;
            border: 2px solid #3a66db;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #3a66db;
            border-color: #3a66db;
            box-shadow: 0 0 0 2px rgba(58, 102, 219, 0.2);
        }

        .form-check-label {
            color: #2d3748;
            font-weight: 500;
            cursor: pointer;
            padding-left: 0.5rem;
        }

        /* Enhanced Schedule Meeting Form Styling */
        .schedule-meeting-form {
            background: linear-gradient(to right, rgba(67, 97, 238, 0.03), rgba(58, 102, 219, 0.03));
            padding: 2rem;
            border-radius: 15px;
        }

        .form-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(67, 97, 238, 0.05);
            border: 1px solid rgba(67, 97, 238, 0.1);
        }

        .form-section-title {
            color: var(--primary-color);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label {
            color: #4a5568;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: auto;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .submit-btn-container {
            margin-top: 2rem;
            text-align: center;
        }

        .schedule-btn {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .schedule-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
            background: linear-gradient(135deg, #4895ef, #4361ee);
        }

        .schedule-btn i {
            font-size: 1.2rem;
        }

        /* Enhanced Scheduled Meetings Styling */
        .scheduled-meetings {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(58, 102, 219, 0.08);
            overflow: hidden;
        }

        .scheduled-meetings .card-header {
            background: linear-gradient(to right, rgba(67, 97, 238, 0.05), rgba(58, 102, 219, 0.05));
            padding: 1.5rem;
            border-bottom: 2px solid rgba(67, 97, 238, 0.1);
        }

        .scheduled-meetings .card-header h4 {
            color: #3a66db;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .scheduled-meetings .table {
            margin: 0;
        }

        .scheduled-meetings .table th {
            background: rgba(67, 97, 238, 0.02);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem 1.5rem;
            color: #3a66db;
        }

        .scheduled-meetings .table td {
            padding: 1.2rem 1.5rem;
            vertical-align: middle;
        }

        .scheduled-meetings .table tr {
            transition: all 0.3s ease;
        }

        .scheduled-meetings .table tr:hover {
            background-color: rgba(67, 97, 238, 0.02);
        }

        .meeting-title {
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meeting-time {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.95rem;
        }

        .meeting-time i {
            color: #3a66db;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .btn-action.update {
            background: rgba(67, 97, 238, 0.1);
            color: #3a66db;
        }

        .btn-action.delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
    </style>
</head>
<body>
    <?php
    // Check if user is team lead
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TeamLead') {
        echo "<script>window.location.href = 'login.php';</script>";
    }

    // Handle delete request
    if (isset($_POST['delete_meeting'])) {
        $meeting_id = $_POST['meeting_id'];
        $delete_stmt = $conn->prepare("DELETE FROM meetings WHERE meeting_id = ? AND team_id = ?");
        $delete_stmt->bind_param("ii", $meeting_id, $_SESSION['team_id']);
        
        if ($delete_stmt->execute()) {
            $success_message = "Meeting deleted successfully!";
        } else {
            $error_message = "Error deleting meeting: " . $conn->error;
        }
        $delete_stmt->close();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $scheduled_time = $_POST['scheduled_time'];
        $meet_link = $_POST['meet_link'];
        $manager_required = isset($_POST['manager_required']) ? 1 : 0;
        $team_id = $_SESSION['team_id'];
        $created_by = $_SESSION['user_id'];

        // Validate scheduled time
        $scheduled_datetime = new DateTime($scheduled_time);
        $current_datetime = new DateTime();

        if ($scheduled_datetime <= $current_datetime) {
            $error_message = "Meeting time must be in the future!";
        } else {
            $stmt = $conn->prepare("INSERT INTO meetings (team_id, title, description, scheduled_time, meet_link, manager_required, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssii", $team_id, $title, $description, $scheduled_time, $meet_link, $manager_required, $created_by);
            
            if ($stmt->execute()) {
                $success_message = "Meeting scheduled successfully!";
            } else {
                $error_message = "Error scheduling meeting: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Fetch existing meetings
    $meetings = [];
    $fetch_stmt = $conn->prepare("SELECT * FROM meetings WHERE team_id = ? ORDER BY scheduled_time DESC");
    if ($fetch_stmt) {
        $fetch_stmt->bind_param("i", $_SESSION['team_id']);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $meetings[] = $row;
        }
        $fetch_stmt->close();
    }
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="navbar-brand">
                <i class="fas fa-calendar me-2"></i>
                Meeting Scheduler
            </div>
            <a href="teamlead_dashboard.php" class="btn-back">
                <i class="fas fa-home"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-3">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Schedule Meeting Card -->
        <div class="card schedule-card">
            <div class="card-header">
                <h4>
                    <i class="fas fa-calendar-plus"></i>
                    Schedule New Meeting
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" class="schedule-meeting-form" onsubmit="return validateDateTime()">
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Meeting Title</label>
                                <input type="text" name="title" class="form-control" required 
                                       placeholder="Enter meeting title">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Schedule Time</label>
                                <input type="datetime-local" name="scheduled_time" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-align-left"></i>
                            Meeting Details
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" 
                                          placeholder="Enter meeting description"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Google Meet Link</label>
                                <input type="url" name="meet_link" class="form-control" required 
                                       placeholder="Enter Google Meet URL">
                            </div>
                            <div class="col-md-12">
                                <div class="manager-checkbox-container">
                                    <div class="form-check">
                                        <input type="checkbox" name="manager_required" class="form-check-input" id="managerRequired">
                                        <label class="form-check-label" for="managerRequired">
                                            <i class="fas fa-user-tie me-2"></i>
                                            Manager Presence Required
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="submit-btn-container">
                        <button type="submit" class="schedule-btn">
                            <i class="fas fa-calendar-plus"></i>
                            Schedule Meeting
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Meetings List -->
        <div class="card scheduled-meetings">
            <div class="card-header">
                <h4>
                    <i class="fas fa-calendar-check"></i>
                    Scheduled Meetings
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <?php if (empty($meetings)): ?>
                        <div class="no-meetings">
                            <i class="fas fa-calendar-times"></i>
                            <p>No meetings scheduled yet</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Meeting Details</th>
                                    <th>Schedule</th>
                                    <th>Meet Link</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meetings as $meeting): ?>
                                <tr>
                                    <td>
                                        <div class="meeting-title">
                                            <i class="fas fa-calendar-day text-primary"></i>
                                            <?= htmlspecialchars($meeting['title']) ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <?= htmlspecialchars($meeting['description']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="meeting-time">
                                            <i class="far fa-clock"></i>
                                            <?= date('M d, Y h:i A', strtotime($meeting['scheduled_time'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="copy-link-group">
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                                   id="link_<?= $meeting['meeting_id'] ?>" readonly>
                                            <button class="btn btn-light" type="button"
                                                    onclick="copyLink(<?= $meeting['meeting_id'] ?>)">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge">
                                            <?= htmlspecialchars($meeting['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons justify-content-end">
                                            <button class="btn-action update" 
                                                    onclick="updateLink(<?= $meeting['meeting_id'] ?>)">
                                                <i class="fas fa-sync-alt"></i>
                                                Update
                                            </button>
                                            <a href="delete_meeting.php?id=<?= $meeting['meeting_id'] ?>" 
                                               class="btn-action delete"
                                               onclick="return confirm('Are you sure you want to delete this meeting?');">
                                                <i class="fas fa-trash-alt"></i>
                                                End
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function validateDateTime() {
        const scheduledTime = new Date(document.querySelector('input[name="scheduled_time"]').value);
        const currentTime = new Date();
        
        if (scheduledTime <= currentTime) {
            alert('Meeting time must be in the future!');
            return false;
        }
        return true;
    }

    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    function copyLink(meetingId) {
        const linkInput = document.getElementById('link_' + meetingId);
        linkInput.select();
        document.execCommand('copy');
        showToast('Meeting link copied to clipboard!');
    }

    function updateLink(meetingId) {
        const newLink = document.getElementById('link_' + meetingId).value;
        fetch('update_meeting_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `meeting_id=${meetingId}&meet_link=${encodeURIComponent(newLink)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Link updated successfully!');
            } else {
                showToast('Error updating link', 'danger');
            }
        })
        .catch(error => {
            showToast('Error updating link: ' + error.message, 'danger');
        });
    }

    function deleteMeeting(meetingId) {
        if (confirm('Are you sure you want to end this meeting?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            form.innerHTML = `
                <input type="hidden" name="delete_meeting" value="1">
                <input type="hidden" name="meeting_id" value="${meetingId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Set minimum date for datetime-local input
    window.addEventListener('load', function() {
        const dateInput = document.querySelector('input[name="scheduled_time"]');
        if (dateInput) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            dateInput.min = now.toISOString().slice(0,16);
        }
    });
    </script>
</body>
</html>