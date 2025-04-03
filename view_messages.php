<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "contact_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM messages ORDER BY created_at DESC");

echo "<h2>Contact Messages</h2>";
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th><th>Date</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . $row["id"] . "</td><td>" . $row["name"] . "</td><td>" . $row["email"] . "</td><td>" . $row["message"] . "</td><td>" . $row["created_at"] . "</td></tr>";
}

echo "</table>";

$conn->close();
?>
