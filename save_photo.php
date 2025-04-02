<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get Data from Request
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    error_log("❌ Invalid JSON input.");
    echo json_encode(["status" => "error", "message" => "Invalid JSON input."]);
    exit;
}

$image = $data['image'] ?? null;
$residentIds = $data['residentIds'] ?? null;

// Validate Inputs
if (!$image || !$residentIds || !is_array($residentIds)) {
    error_log("❌ Missing image or resident IDs.");
    echo json_encode(["status" => "error", "message" => "Invalid data received."]);
    exit;
}

// Convert Base64 Image to Binary Data
$image = str_replace('data:image/jpeg;base64,', '', $image);
$image = str_replace(' ', '+', $image);
$imageData = base64_decode($image);

if (!$imageData) {
    error_log("❌ Failed to decode image.");
    echo json_encode(["status" => "error", "message" => "Failed to process image."]);
    exit;
}

// Connect to Database
$conn = new mysqli("localhost", "root", "", "bdams_db");
if ($conn->connect_error) {
    error_log("❌ Database connection failed: " . $conn->connect_error);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}

// Update ALL Attendance Records for Each Resident ID
$stmt = $conn->prepare("UPDATE attendance SET photo_path=? WHERE resident_id=?");
if (!$stmt) {
    error_log("❌ Database prepare failed: " . $conn->error);
    echo json_encode(["status" => "error", "message" => "Database prepare failed."]);
    exit;
}

foreach ($residentIds as $residentId) {
    $stmt->bind_param("si", $imageData, $residentId);
    if (!$stmt->execute()) {
        error_log("❌ Database update failed for resident ID $residentId: " . $stmt->error);
        echo json_encode(["status" => "error", "message" => "Database update failed for resident ID $residentId."]);
        exit;
    }
}

// Return the image as a downloadable file
header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="resident_photo.jpg"');
echo $imageData;

$stmt->close();
$conn->close();
?>