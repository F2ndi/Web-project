<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "registerdb"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get username and password from POST request
$user = $_POST['Email'];
$pass = $_POST['password'];

// Query to check user credentials
$sql = "SELECT * FROM users WHERE Email = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();
$result = $stmt->get_result();

// Verify credentials
if ($result->num_rows > 0) {
    // Login successful
    $_SESSION['username'] = $user;
    header("Location: feed.html");
    exit();
} else {
    // Login failed
    echo "Invalid username or password.";
}

$stmt->close();
$conn->close();
?>
