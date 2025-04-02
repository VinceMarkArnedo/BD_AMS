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

if (isset($_GET['date'])) {
    $selectedDate = $_GET['date'];

    // SQL query to fetch records for the selected date
    $sql = "SELECT batch_id, resident_name, resident_zone, event_type, time_in, time_out, zone_person, 
                   DATE_FORMAT(saved_date, '%M %d, %Y') as saved_date 
            FROM saved_attendance 
            WHERE DATE(saved_date) = '$selectedDate' 
            ORDER BY batch_id DESC, time_in DESC";

    $result = $conn->query($sql);

    $attendanceRecords = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendanceRecords[] = [
                'batch_id' => $row['batch_id'],
                'name' => $row['resident_name'], 
                'zone' => $row['resident_zone'], 
                'event_type' => $row['event_type'],
                'time_in' => $row['time_in'], 
                'time_out' => $row['time_out'],
                'zone_person' => $row['zone_person'], // Add zone_person
                'saved_date' => $row['saved_date']
            ];
        }
    }

    // Return the filtered records as a JSON response
    echo json_encode($attendanceRecords);
}
?>