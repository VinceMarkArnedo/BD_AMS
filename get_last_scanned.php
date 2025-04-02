<?php
$conn = new mysqli("localhost", "root", "", "bdams_db");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}

// ✅ Fetch the last scanned resident from records.php
$sql = "SELECT resident_id FROM attendance ORDER BY time_in DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(["status" => "success", "resident_id" => $row['resident_id']]);
} else {
    echo json_encode(["status" => "error", "message" => "No records found."]);
}

$conn->close();
?>
