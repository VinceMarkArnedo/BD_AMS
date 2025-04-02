<?php
// Database connection
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "bdams_db"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

// Fetch all resident IDs from the attendance table
$sql = "SELECT DISTINCT resident_id FROM attendance";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $residentIds = [];
    while ($row = $result->fetch_assoc()) {
        $residentIds[] = $row['resident_id'];
    }
    echo json_encode(["status" => "success", "residentIds" => $residentIds]);
} else {
    echo json_encode(["status" => "error", "message" => "No scanned residents found."]);
}

$conn->close();
?>