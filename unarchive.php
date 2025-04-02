<?php
// Database connection
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "bdams_db"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the request is valid and resident_id is provided
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resident_id'])) {
    $resident_id = $_POST['resident_id'];

    // Fetch resident details from `archived_residents`
    $sql = "SELECT * FROM archived_residents WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $resident = $result->fetch_assoc();

        // Insert back into `residents` without specifying the `id` (auto-generate new `id`)
        $sql_insert = "INSERT INTO residents (name, age, gender, zone, phone_number) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sissi", $resident['name'], $resident['age'], $resident['gender'], 
                                 $resident['zone'], $resident['phone_number']);
        if ($stmt_insert->execute()) {
            // Get the new auto-generated `id` for the resident
            $new_resident_id = $stmt_insert->insert_id;

            // Generate a new QR code URL based on the new `id`
            $new_qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($new_resident_id);

            // Update the `residents` table with the new QR code URL
            $sql_update_qr = "UPDATE residents SET qr_code_url = ? WHERE id = ?";
            $stmt_update_qr = $conn->prepare($sql_update_qr);
            $stmt_update_qr->bind_param("si", $new_qr_code_url, $new_resident_id);
            $stmt_update_qr->execute();

            // Remove from `archived_residents`
            $sql_delete = "DELETE FROM archived_residents WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param("i", $resident_id);
            $stmt_delete->execute();

            // Redirect back to archived page with success message
            header("Location: archived_residents.php?unarchived=success");
            exit();
        } else {
            header("Location: archived_residents.php?unarchived=error");
            exit();
        }
    } else {
        header("Location: archived_residents.php?unarchived=not_found");
        exit();
    }
} else {
    header("Location: archived_residents.php?unarchived=invalid_request");
    exit();
}

$conn->close();
?>