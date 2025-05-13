<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['share_id'])) {
    echo "<script>window.location.href = 'file_sharing.php';</script>";
    exit;
}

// Determine back button URL based on role
$backUrl = ($_SESSION['role'] === 'Manager') ? 'manager_file_sharing.php' : 'file_sharing.php';

// Get share_id from URL
if (!isset($_GET['share_id'])) {
    echo "<script>window.location.href = '" . $backUrl . "';</script>";
}

$share_id = $_GET['share_id'];
$error = "";
$success = "";

// Fetch share details
$query = "SELECT * FROM file_shares WHERE share_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $share_id);
$stmt->execute();
$share = $stmt->get_result()->fetch_assoc();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $version_note = $_POST['version_note'];
    $file = $_FILES['file'];
    
    // Get the latest version number
    $query = "SELECT MAX(version_number) as max_version FROM file_versions WHERE share_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $share_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $next_version = ($result['max_version'] ?? 0) + 1;
    
    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Create unique filename
    $original_filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $stored_filename = $original_filename . 'v' . $next_version . '' . uniqid() . '.' . $file_extension;
    $upload_path = '../uploads/fileshare/' . $stored_filename;
    
    // Create directory if it doesn't exist
    if (!file_exists('../uploads/fileshare')) {
        mkdir('../uploads/fileshare', 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Insert file version into database
        $query = "INSERT INTO file_versions (share_id, version_number, file_name, stored_name, 
                  uploaded_by, username, version_note) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iississ", $share_id, $next_version, $file['name'], 
                         $stored_filename, $_SESSION['user_id'], $_SESSION['username'], $version_note);
        
        if ($stmt->execute()) {
            $success = "File uploaded successfully as version " . $next_version;
        } else {
            $error = "Database error while saving file version";
        }
    } else {
        $error = "Failed to upload file";
    }
}

// Fetch all versions of this share
$query = "SELECT * FROM file_versions WHERE share_id = ? ORDER BY version_number DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $share_id);
$stmt->execute();
$versions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Versions | Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4895ef;
            --background: #f8f9fc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
        }

        /* Navbar - Matches App Theme */
        .navbar {
            background: linear-gradient(135deg, #3a66db, #2952c8);
            padding: 1.2rem 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            height: 80px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0 2rem;
        }

        .navbar-brand {
            font-size: 1.4rem;
            font-weight: 600;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            text-decoration: none;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white;
        }

        /* ...existing styles... */
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --secondary: #3f37c9;
            --success: #0bb36d;
            --success-hover: #0a9f61;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #293452;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --alert-success-bg: #ecfdf5;
            --alert-success-border: #a7f3d0;
            --alert-success-text: #065f46;
            --alert-danger-bg: #fef2f2;
            --alert-danger-border: #fecaca;
            --alert-danger-text: #991b1b;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            color: var(--text-main);
            line-height: 1.6;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .navbar .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(-2px);
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: 600;
            margin-left: 1rem;
            font-size: 1.2rem;
        }

        .main-container {
            max-width: 1100px;
            margin: 2.5rem auto;
        }

        .content-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .file-header {
            margin-bottom: 2.5rem;
            position: relative;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .file-title {
            color: var(--text-main);
            font-weight: 700;
            margin-bottom: 0.75rem;
            font-size: 1.75rem;
        }

        .file-description {
            color: var(--text-secondary);
            margin-bottom: 0;
            font-size: 1.05rem;
        }

        .upload-section {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .upload-section h3 {
            color: var(--text-main);
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .upload-section h3:before {
            content: '';
            display: inline-block;
            width: 18px;
            height: 18px;
            background-color: var(--primary);
            border-radius: 50%;
        }

        .form-label {
            color: var(--text-main);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        input[type="file"].form-control {
            padding: 0.9rem;
        }

        textarea.form-control {
            min-height: 120px;
        }

        .btn-upload {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            color: #ffffff;
            padding: 0.75rem 1.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-upload:hover {
            background: linear-gradient(to right, var(--primary-hover), var(--secondary));
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(67, 97, 238, 0.25);
        }

        .versions-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .versions-header h3 {
            color: var(--text-main);
            font-weight: 600;
            margin-bottom: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .versions-header h3:before {
            content: '';
            display: inline-block;
            width: 18px;
            height: 18px;
            background-color: var(--secondary);
            border-radius: 50%;
        }

        .version-item {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 1.75rem;
            margin-bottom: 1.25rem;
            transition: all 0.3s;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .version-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border-color: rgba(67, 97, 238, 0.3);
        }

        .version-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            border-radius: 10px 0 0 10px;
        }

        .version-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.25rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .version-number {
            font-weight: 600;
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            padding: 0.35rem 0.85rem;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .version-meta span {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .version-note {
            color: var(--text-main);
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
            background: rgba(241, 245, 249, 0.5);
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid var(--border-color);
        }

        .btn-download {
            background-color: var(--success);
            border: none;
            color: #ffffff;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(11, 179, 109, 0.2);
        }

        .btn-download:hover {
            background-color: var(--success-hover);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(11, 179, 109, 0.25);
        }

        .alert {
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            border-width: 1px;
            border-style: solid;
        }

        .alert-success {
            background-color: var(--alert-success-bg);
            border-color: var(--alert-success-border);
            color: var(--alert-success-text);
        }

        .alert-danger {
            background-color: var(--alert-danger-bg);
            border-color: var(--alert-danger-border);
            color: var(--alert-danger-text);
        }

        .alert i {
            font-size: 1.25rem;
            margin-top: 2px;
        }

        /* Empty state for no versions */
        .empty-versions {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 3rem 2rem;
            text-align: center;
            color: var(--text-secondary);
            border: 1px dashed var(--border-color);
        }

        .empty-versions i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border-color);
        }

        .empty-versions p {
            font-size: 1.1rem;
            max-width: 80%;
            margin: 0 auto;
        }

        /* Animation for new uploads */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container navbar-container">
            <div class="navbar-brand">
                <i class="fas fa-file-alt me-2"></i>
                File Versions
            </div>
            <a href="manager_file_sharing.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Files
            </a>
        </div>
    </nav>

    <div class="container main-container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Error</strong>
                    <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success fade-in">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success</strong>
                    <p class="mb-0"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="file-header">
                <h2 class="file-title">
                    <i class="fas fa-file-alt me-2"></i>
                    <?= htmlspecialchars($share['title']) ?>
                </h2>
                <p class="file-description"><?= htmlspecialchars($share['description']) ?></p>
            </div>

            <div class="upload-section">
                <h3>Upload New Version</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="file" class="form-label">
                            <i class="fas fa-file-upload me-1"></i>
                            Select File
                        </label>
                        <input type="file" class="form-control" id="file" name="file" required>
                    </div>
                    <div class="mb-4">
                        <label for="version_note" class="form-label">
                            <i class="fas fa-edit me-1"></i>
                            Version Notes
                        </label>
                        <textarea class="form-control" id="version_note" name="version_note" rows="3" 
                                  placeholder="Describe the changes in this version..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-upload">
                        <i class="fas fa-upload"></i>
                        Upload New Version
                    </button>
                </form>
            </div>

            <div class="versions-header">
                <h3>File Version History</h3>
            </div>
            
            <?php if (empty($versions)): ?>
                <div class="empty-versions">
                    <i class="far fa-folder-open"></i>
                    <h4 class="mt-3 mb-3">No Versions Available</h4>
                    <p>Upload the first version of this file using the form above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($versions as $version): ?>
                    <div class="version-item">
                        <div class="version-meta">
                            <span class="version-number">
                                <i class="fas fa-code-branch"></i>
                                Version <?= htmlspecialchars($version['version_number']) ?>
                            </span>
                            <span>
                                <i class="far fa-user"></i>
                                <?= htmlspecialchars($version['username']) ?>
                            </span>
                            <span>
                                <i class="far fa-calendar-alt"></i>
                                <?= date('F j, Y', strtotime($version['upload_time'])) ?>
                            </span>
                            <span>
                                <i class="far fa-clock"></i>
                                <?= date('g:i A', strtotime($version['upload_time'])) ?>
                            </span>
                        </div>
                        <div class="version-note">
                            <?= htmlspecialchars($version['version_note']) ?>
                        </div>
                        <div>
                            <a href="../uploads/fileshare/<?= urlencode($version['stored_name']) ?>" 
                               class="btn btn-download" 
                               download="<?= htmlspecialchars($version['file_name']) ?>">
                                <i class="fas fa-download"></i>
                                Download File
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>