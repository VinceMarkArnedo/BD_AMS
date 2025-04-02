<?php
session_start();
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Retrieve POST data
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Sanitize input to prevent SQL injection
$username = $conn->real_escape_string($username);
$password = $conn->real_escape_string($password);

// Query to check if the username and password are correct
$sql = "SELECT * FROM security WHERE username = '$username' AND password = '$password'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Successful login, fetch user role
    $user = $result->fetch_assoc();
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];  // Store role in session

    echo json_encode(['success' => true, 'role' => $user['role']]);
} else {
    // Invalid credentials
    echo json_encode(['success' => false]);
}

// Close the database connection
$conn->close();
?>