<?php
$servername = "localhost"; // Change if needed
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password
$dbname = "contact_db"; // Database name

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// Create table if not exists
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $message);

    if ($stmt->execute()) {
        // Alternative method to send email - using file-based approach
        $log_file = "contact_submissions.log";
        $current_time = date("Y-m-d H:i:s");
        $log_entry = "Time: $current_time\nName: $name\nEmail: $email\nMessage: $message\n\n";
        
        // Save to log file
        file_put_contents($log_file, $log_entry, FILE_APPEND);

        // Original email attempt as fallback
        $to = "adithyakorean@gmail.com";
        $subject = "New Contact Message from " . $name;
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n" .
                  "Reply-To: " . $email . "\r\n" .
                  "Content-Type: text/plain; charset=UTF-8";
        $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
        @mail($to, $subject, $body, $headers);

        echo "<script>alert('Message sent successfully!'); window.location.href='contactus.html';</script>";
    } else {
        echo "<script>alert('Failed to send message.'); window.location.href='contactus.html';</script>";
    }

    $stmt->close();
}

$conn->close();
?>