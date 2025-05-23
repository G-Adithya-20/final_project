<?php
session_start();
require '../includes/db_connect.php';

// Check for HR role
if ($_SESSION['role'] != 'HR') {
    echo "<script>window.location.href = 'error.php';</script>";
    // header("Location: error.php");
    // exit;
}

// Create User
if (isset($_POST['create'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Removed domain from the INSERT query
    $stmt = $conn->prepare("INSERT INTO users (username, email, role, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $role, $password);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User created successfully!";
    } else {
        $_SESSION['message'] = "Error creating user: " . $conn->error;
    }
    
    echo "<script>window.location.href = '../pages/hr_panel.php';</script>";
    // header("Location: ../pages/hr_panel.php");
    // exit;
}

// Update User
if (isset($_POST['update'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    if (!empty($_POST['password'])) {
        // Update with new password (removed domain)
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=?, password=? WHERE user_id=?");
        $stmt->bind_param("ssssi", $username, $email, $role, $password, $user_id);
    } else {
        // Update without changing password (removed domain)
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, role=? WHERE user_id=?");
        $stmt->bind_param("sssi", $username, $email, $role, $user_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "User updated successfully!";
    } else {
        $_SESSION['message'] = "Error updating user: " . $conn->error;
    }
    
    echo "<script>window.location.href = '../pages/hr_panel.php';</script>";
}

// Delete User
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // First check if user exists
    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['message'] = "User not found!";
        echo "<script>window.location.href = '../pages/hr_panel.php';</script>";
    }
    
    // Prevent deleting your own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
        echo "<script>window.location.href = '../pages/hr_panel.php';</script>";
    }
    
    // Delete the user
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $delete_stmt->bind_param("i", $user_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['message'] = "User deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting user: " . $conn->error;
    }
    
    echo "<script>window.location.href = '../pages/hr_panel.php';</script>";
}

// If no valid action is specified, redirect back to HR management page
echo "<script>window.location.href = '../pages/hr_panel.php';</script>";
?>