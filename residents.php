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

// File upload and CSV import
if (isset($_POST['import'])) {
    $filename = $_FILES['csv_file']['tmp_name'];

    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($filename, 'r');

        // Skip the first row (header)
        fgetcsv($file);

        $batchSize = 100; // Insert 100 records at a time
        $batchData = [];

        // Prepare the SQL statement for batch insertion
        $stmt = $conn->prepare("INSERT INTO residents (name, age, gender, zone, phone_number) 
                                VALUES (?, ?, ?, ?, ?)");

        while (($data = fgetcsv($file, 1000, ',')) !== FALSE) {
            $name = $conn->real_escape_string($data[0]);        // Column 1: Name
            $age = $conn->real_escape_string($data[1]);         // Column 2: Age
            $gender = $conn->real_escape_string($data[2]);      // Column 3: Gender
            $zone = $conn->real_escape_string($data[3]);        // Column 4: Zone
            // Column 5: Phone Number (Optional) - Check if the field is empty, use NULL if so
            $phone_number = isset($data[4]) && !empty($data[4]) ? $conn->real_escape_string($data[4]) : NULL;

            // Bind parameters to prepared statement
            $stmt->bind_param("sssss", $name, $age, $gender, $zone, $phone_number);

            // Execute the prepared statement
            $stmt->execute();
        }

        fclose($file);
        
        // Redirect back to residents.php after import
        header("Location: residents.php");
        exit(); // Stop further execution
    } else {
        echo "Please upload a valid CSV file.";
    }
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'archive') {
        $userId = $_POST['userId'];
        $status = $_POST['status']; // Get the selected status

        // Archive the resident by copying data to archived_residents table
        $sql = "INSERT INTO archived_residents (name, age, gender, zone, phone_number, qr_code_url, status)
                SELECT name, age, gender, zone, phone_number, qr_code_url, '$status'
                FROM residents WHERE id = $userId";
        if ($conn->query($sql) === TRUE) {
            // If the insertion was successful, delete the resident from the main table
            $sql = "DELETE FROM residents WHERE id = $userId";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["status" => "error", "message" => $conn->error]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        exit();
    }
}




// Add, update, or delete user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        $userId = $_POST['userId'];
        
        // Delete the resident but leave the attendance records intact
        $sql = "DELETE FROM residents WHERE id=$userId";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
        exit();
    }

    $name = $_POST['name'];
    $age = $_POST['age'];
    $gender = $_POST['gender'];
    $zone = $_POST['zone'];
    $phone_number = $_POST['phone_number'];
    $qrCodeUrl = $_POST['qrCodeUrl']; 

    if (isset($_POST['userId']) && $_POST['userId'] != "") {
        // Update existing user
        $userId = $_POST['userId'];
        $sql = "UPDATE residents SET name='$name', age='$age', gender='$gender', zone='$zone', phone_number='$phone_number', qr_code_url='$qrCodeUrl' WHERE id=$userId";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    } else {
        // Insert new user
        $sql = "INSERT INTO residents (name, age, gender, zone, phone_number, qr_code_url) VALUES ('$name', '$age', '$gender', '$zone', '$phone_number', '$qrCodeUrl')";
        if ($conn->query($sql) === TRUE) {
            echo json_encode(["status" => "success", "userId" => $conn->insert_id]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    }
    
    
    exit();
}

// Fetch users for display (modified to include QR code generation)
$users = [];
$result = $conn->query("SELECT * FROM residents");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['qr_code_url'] = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($row['id']);
        $users[] = $row;
    }
}

// Example PHP script to fetch resident details when QR code is scanned
if (isset($_GET['userId'])) {
    $userId = $_GET['userId'];

    // Fetch resident details based on the unique ID
    $sql = "SELECT * FROM residents WHERE id = $userId";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Output the resident data (without exposing name/zone in QR code)
        $resident = $result->fetch_assoc();
        echo json_encode($resident); // Return the data as JSON
    } else {
        echo json_encode(["error" => "Resident not found"]);
    }
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>dashboard</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/rc.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script></head>
    
</head>
<body>
 <!-- this is sidebar navigation -->
 <nav id="sidebar">
    <ul>
            <li>
                
                <span class="Logo">
                <img src="img/logo.jpg" class="pic">
                <span>BDAMS </span>
                    
                </span>
                <button onclick="toggleSidebar()" id="toggle-btn">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m313-480 155 156q11 11 11.5 27.5T468-268q-11 11-28 11t-28-11L228-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 27.5-11.5T468-692q11 11 11 28t-11 28L313-480Zm264 0 155 156q11 11 11.5 27.5T732-268q-11 11-28 11t-28-11L492-452q-6-6-8.5-13t-2.5-15q0-8 2.5-15t8.5-13l184-184q11-11 27.5-11.5T732-692q11 11 11 28t-11 28L577-480Z"/></svg>
                </button>
                
            </li>
            <hr>
            <li>
                <button onclick="toggleSubMenu(this)" class="dropdown-btn">
                     <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF" class=""><path d="M520-640v-160q0-17 11.5-28.5T560-840h240q17 0 28.5 11.5T840-800v160q0 17-11.5 28.5T800-600H560q-17 0-28.5-11.5T520-640ZM120-480v-320q0-17 11.5-28.5T160-840h240q17 0 28.5 11.5T440-800v320q0 17-11.5 28.5T400-440H160q-17 0-28.5-11.5T120-480Zm400 320v-320q0-17 11.5-28.5T560-520h240q17 0 28.5 11.5T840-480v320q0 17-11.5 28.5T800-120H560q-17 0-28.5-11.5T520-160Zm-400 0v-160q0-17 11.5-28.5T160-360h240q17 0 28.5 11.5T440-320v160q0 17-11.5 28.5T400-120H160q-17 0-28.5-11.5T120-160Zm80-360h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/></svg>
                    <span>Dashboard</span>
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M459-381 314-526q-3-3-4.5-6.5T308-540q0-8 5.5-14t14.5-6h304q9 0 14.5 6t5.5 14q0 2-6 14L501-381q-5 5-10 7t-11 2q-6 0-11-2t-10-7Z"/></svg>
                </button>
                <ul class="sub-menu">
                    <div>
                    <li><a href="dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M480-120q-75 0-140.5-28.5t-114-77q-48.5-48.5-77-114T120-480q0-75 28.5-140.5t77-114q48.5-48.5 114-77T480-840q82 0 155.5 35T760-706v-54q0-17 11.5-28.5T800-800q17 0 28.5 11.5T840-760v160q0 17-11.5 28.5T800-560H640q-17 0-28.5-11.5T600-600q0-17 11.5-28.5T640-640h70q-41-56-101-88t-129-32q-117 0-198.5 81.5T200-480q0 117 81.5 198.5T480-200q95 0 170-57t99-147q5-16 18-24t29-6q17 2 27 14.5t6 27.5q-29 119-126 195.5T480-120Zm40-376 100 100q11 11 11 28t-11 28q-11 11-28 11t-28-11L452-452q-6-6-9-13.5t-3-15.5v-159q0-17 11.5-28.5T480-680q17 0 28.5 11.5T520-640v144Z"/></svg>
                        <span>Schedule</span>
                    </a></li>
                    <li><a href="bar_graph.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M640-160v-280h160v280H640Zm-240 0v-640h160v640H400Zm-240 0v-440h160v440H160Z"/></svg>
                        <span>Events Statistics</span>
                    </a></li>
                    <li><a href="pie.php">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M521-830q0-20 16-32t36-7q112 24 193.5 107T872-564q4 17-7.5 30.5T833-520H602q-4 0-7-2t-5-6q-9-21-25-37.5T529-591q-4-2-6-4.5t-2-6.5v-228Zm80 54v136q11 9 21 19t19 21h136q-24-60-70-106t-106-70ZM389-91q-134-31-221-139T81-480q0-142 87-249.5T389-869q20-5 36 8t16 33v228q0 1-6 8-34 14-54 44.5T361-480q0 37 20 66.5t54 43.5q2 1 6 8v232q0 20-16 32t-36 7Zm-28-685q-91 35-145.5 116T161-480q0 99 54.5 180T361-182v-138q-38-29-59-70.5T281-480q0-48 21-89.5t59-70.5v-136ZM573-91q-20 5-36-7t-16-32v-229q0-4 2-7t6-5q20-9 36-25t25-36q1-2 11-8h232q18 0 30 15t8 34q-25 115-107 196T573-91Zm68-269q-8 11-18.5 21T601-320v136q60-24 106-70t70-106H641ZM281-479Zm360-121Zm0 240Z"/></svg>
                        <span>No. of Residents</span>
                    </a></li>
                    </div>
                </ul>
            </li>
            <li>
                <a href="saved_records.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h207q16 0 30.5 6t25.5 17l57 57h360q17 0 28.5 11.5T880-680q0 17-11.5 28.5T840-640H447l-80-80H160v480l79-263q8-26 29.5-41.5T316-560h516q41 0 64.5 32.5T909-457l-72 240q-8 26-29.5 41.5T760-160H160Zm84-80h516l72-240H316l-72 240Zm-84-262v-218 218Zm84 262 72-240-72 240Z"/></svg>
                <span>Records</span>
                </a>
            </li>
            <li>
                <a href="residents.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M500-482q29-32 44.5-73t15.5-85q0-44-15.5-85T500-798q60 8 100 53t40 105q0 60-40 105t-100 53Zm198 322q11-18 16.5-38.5T720-240v-40q0-36-16-68.5T662-406q51 18 94.5 46.5T800-280v40q0 33-23.5 56.5T720-160h-22Zm102-360h-40q-17 0-28.5-11.5T720-560q0-17 11.5-28.5T760-600h40v-40q0-17 11.5-28.5T840-680q17 0 28.5 11.5T880-640v40h40q17 0 28.5 11.5T960-560q0 17-11.5 28.5T920-520h-40v40q0 17-11.5 28.5T840-440q-17 0-28.5-11.5T800-480v-40Zm-480 40q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM0-240v-32q0-34 17.5-62.5T64-378q62-31 126-46.5T320-440q66 0 130 15.5T576-378q29 15 46.5 43.5T640-272v32q0 33-23.5 56.5T560-160H80q-33 0-56.5-23.5T0-240Zm320-320q33 0 56.5-23.5T400-640q0-33-23.5-56.5T320-720q-33 0-56.5 23.5T240-640q0 33 23.5 56.5T320-560ZM80-240h480v-32q0-11-5.5-20T540-306q-54-27-109-40.5T320-360q-56 0-111 13.5T100-306q-9 5-14.5 14T80-272v32Zm240-400Zm0 400Z"/></svg>
                <span>Residents</span>
                </a>
            </li>
            <li>
                <a href="archived_residents.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M480-560q-17 0-28.5 11.5T440-520v128l-36-36q-11-11-28-11t-28 11q-11 11-11 28t11 28l104 104q12 12 28 12t28-12l104-104q11-11 11-28t-11-28q-11-11-28-11t-28 11l-36 36v-128q0-17-11.5-28.5T480-560Zm-280-80v440h560v-440H200Zm0 520q-33 0-56.5-23.5T120-200v-499q0-14 4.5-27t13.5-24l50-61q11-14 27.5-21.5T250-840h460q18 0 34.5 7.5T772-811l50 61q9 11 13.5 24t4.5 27v499q0 33-23.5 56.5T760-120H200Zm16-600h528l-34-40H250l-34 40Zm264 300Z"/></svg>
                <span>Archived</span>
                </a>
            </li>
            <li>
                <a href="setting.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M433-80q-27 0-46.5-18T363-142l-9-66q-13-5-24.5-12T307-235l-62 26q-25 11-50 2t-39-32l-47-82q-14-23-8-49t27-43l53-40q-1-7-1-13.5v-27q0-6.5 1-13.5l-53-40q-21-17-27-43t8-49l47-82q14-23 39-32t50 2l62 26q11-8 23-15t24-12l9-66q4-26 23.5-44t46.5-18h94q27 0 46.5 18t23.5 44l9 66q13 5 24.5 12t22.5 15l62-26q25-11 50-2t39 32l47 82q14 23 8 49t-27 43l-53 40q1 7 1 13.5v27q0 6.5-2 13.5l53 40q21 17 27 43t-8 49l-48 82q-14 23-39 32t-50-2l-60-26q-11 8-23 15t-24 12l-9 66q-4 26-23.5 44T527-80h-94Zm7-80h79l14-106q31-8 57.5-23.5T639-327l99 41 39-68-86-65q5-14 7-29.5t2-31.5q0-16-2-31.5t-7-29.5l86-65-39-68-99 42q-22-23-48.5-38.5T533-694l-13-106h-79l-14 106q-31 8-57.5 23.5T321-633l-99-41-39 68 86 64q-5 15-7 30t-2 32q0 16 2 31t7 30l-86 65 39 68 99-42q22 23 48.5 38.5T427-266l13 106Zm42-180q58 0 99-41t41-99q0-58-41-99t-99-41q-59 0-99.5 41T342-480q0 58 40.5 99t99.5 41Zm-2-140Z"/></svg>
                <span>Settings</span>
                </a>
            </li>
            <li id="logoutBtn">
                <a href="login.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>
                <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
    <main>
    <header id="header">
        <div class="logo-pie">
        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M474-486q26-32 38.5-66t12.5-79q0-45-12.5-79T474-776q76-17 133.5 23T665-631q0 82-57.5 122T474-486Zm202 326q5-15 9.5-29.5T690-220v-34q0-51-26-95t-90-74q173 22 236.5 64T874-254v34q0 24.75-17.62 42.37Q838.75-160 814-160H676Zm124-389h-70q-12.75 0-21.37-8.68-8.63-8.67-8.63-21.5 0-12.82 8.63-21.32 8.62-8.5 21.37-8.5h70v-70q0-12.75 8.68-21.38 8.67-8.62 21.5-8.62 12.82 0 21.32 8.62 8.5 8.63 8.5 21.38v70h70q12.75 0 21.38 8.68 8.62 8.67 8.62 21.5 0 12.82-8.62 21.32-8.63 8.5-21.38 8.5h-70v70q0 12.75-8.68 21.37-8.67 8.63-21.5 8.63-12.82 0-21.32-8.63-8.5-8.62-8.5-21.37v-70Zm-485 68q-66 0-108-42t-42-108q0-66 42-108t108-42q66 0 108 42t42 108q0 66-42 108t-108 42ZM0-220v-34q0-35 18.5-63.5T68-360q72-32 128.5-46T315-420q62 0 118 14t128 46q31 14 50 42.5t19 63.5v34q0 24.75-17.62 42.37Q594.75-160 570-160H60q-24.75 0-42.37-17.63Q0-195.25 0-220Zm315-321q39 0 64.5-25.5T405-631q0-39-25.5-64.5T315-721q-39 0-64.5 25.5T225-631q0 39 25.5 64.5T315-541ZM60-220h510v-34q0-16-8-30t-25-22q-69-32-117-43t-105-11q-57 0-104.5 11T92-306q-15 7-23.5 21.5T60-254v34Zm255-411Zm0 411Z"/></svg>
                <span>Residents</span>   
                </div>  
                <div class="search-conti">
                <div class="searh-container">
                    <form class="form">
                      <label for="search">
                         <input class="input" type="text" required="" placeholder="Search residents" id="search">
                         <div class="fancy-bg"></div>
                         <div class="search">
                         <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#000000"><path d="M384.03-336Q284-336 214-406t-70-170q0-100 70-170t170-70q100 0 170 70t70 170.03q0 40.39-12.5 76.18Q599-464 577-434l214 214q11 11 11 25t-11 25q-11 11-25.5 11T740-170L526-383q-30 22-65.79 34.5-35.79 12.5-76.18 12.5Zm-.03-72q70 0 119-49t49-119q0-70-49-119t-119-49q-70 0-119 49t-49 119q0 70 49 119t119 49Z"/></svg>
                         </div>
                             <button class="close-btn" type="reset">
                             <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#000000"><path d="M480-429 316-265q-11 11-25 10.5T266-266q-11-11-11-25.5t11-25.5l163-163-164-164q-11-11-10.5-25.5T266-695q11-11 25.5-11t25.5 11l163 164 164-164q11-11 25.5-11t25.5 11q11 11 11 25.5T695-644L531-480l164 164q11 11 11 25t-11 25q-11 11-25.5 11T644-266L480-429Z"/></svg>
                             </button>
                      </label>
                     </form>
               </div>
                </div>
                <h2>
                <div class="bell">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#FFFFFF"><path d="M160-200v-66.67h80v-296q0-83.66 49.67-149.5Q339.33-778 420-796v-24q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v24q80.67 18 130.33 83.83Q720-646.33 720-562.67v296h80V-200H160Zm320-301.33ZM480-80q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM306.67-266.67h346.66v-296q0-72-50.66-122.66Q552-736 480-736t-122.67 50.67q-50.66 50.66-50.66 122.66v296Z"/></svg>
                </div>
                </h2>
        </header>
    <div class="addtable">
    <form method="POST" class="import-file" enctype="multipart/form-data">
        <label for="csv_file"></label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit" name="import">Import</button>
    </form>
        <div class="table-fixed">
            <h1>Adding Residents with QR Code</h1>
            <form id="userForm">
                <input type="hidden" id="userId" name="userId">
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Zone</th>
                        <th>Phone Number</th>
                        <th>QR Code</th>
                        <th>Actions</th>
                    </tr>
                    <tr>
                        <td><input type="text" id="name" name="name" placeholder="Enter Name" required></td>
                        <td><input type="number" id="age" name="age" placeholder="Enter Age" required></td>
                        <td>
                            <select id="gender" name="gender" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </td>
                        <td><input type="text" id="zone" name="zone" placeholder="Enter Zone" required></td>
                        <td><input type="text" id="phone_number" name="phone_number" placeholder="Enter Phone Number" required></td>
                        <td>
                            <img id="qrCodeImage" src="https://via.placeholder.com/100" alt="QR Code">
                            <input type="hidden" id="qrCodeUrl" name="qrCodeUrl">
                        </td>
                        <td><button type="submit" id="addBtn">Add Resident</button></td>
                    </tr>
                </table>
            </form>
            
            <hr>
            <table id="userTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Zone</th>
                    <th>Phone Number</th>
                    <th>QR Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr id="row-<?php echo $user['id']; ?>">
                    <td><?php echo $user['name']; ?></td>
                    <td><?php echo $user['age']; ?></td>
                    <td><?php echo $user['gender']; ?></td>
                    <td><?php echo $user['zone']; ?></td>
                    <td><?php echo $user['phone_number']; ?></td>
                    <td>
                        <img src="<?php echo $user['qr_code_url']; ?>" alt="QR Code" onload="this.style.display='block';">
                        <a href="<?php echo $user['qr_code_url']; ?>" download="QRCode_<?php echo $user['name']; ?>.png">Download</a>
                    </td>
                    <td class="action-buttons">
                        <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['name']; ?>', <?php echo $user['age']; ?>, '<?php echo $user['gender']; ?>', '<?php echo $user['zone']; ?>', '<?php echo $user['phone_number']; ?>', '<?php echo $user['qr_code_url']; ?>')">Edit</button>
                        <button class="archive" onclick="openArchiveModal(<?php echo $user['id']; ?>)">Archive</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="archiveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index:5;">
            <div style="margin: 300px auto; padding: 20px; background: #fff; width: 300px; text-align: center; border-radius: 10px;  ">
                <h3>Select Resident Status</h3>
                <select id="statusSelect">
                    <option value="Deceased">Deceased</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Moved Out">Moved Out</option>
                    <option value="Other">Other</option>
                </select>
                <br><br>
                <button class="confirm" onclick="confirmArchive()">Confirm</button>
                <button class="cancel" onclick="closeArchiveModal()">Cancel</button>
            </div>
        </div>
        </div>
    </div>
    </main>

    

    <script>
       //sidebar toggle
    const toggleButton = document.getElementById("toggle-btn");
    const sidebar = document.getElementById('sidebar');
    
    function toggleSidebar() {
        sidebar.classList.toggle('close');
        toggleButton.classList.toggle('rotate');

        Array.from(sidebar.getElementsByClassName('show')).forEach(ul => {
            ul.classList.remove('show');
            ul.previousElementSibling.classList.remove('rotate');
        });

    }
    function toggleSubMenu(button) {
        button.nextElementSibling.classList.toggle('show');
        button.classList.toggle('rotate');

        if(sidebar.classList.contains('close')) {
            sidebar.classList.remove('close');
            toggleButton.classList.toggle('rotate');
        }
    }
     //end sidebar toggle 
    

        function generateQRCode(userId) { // Modified to accept userId
            const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(userId)}`;
            document.getElementById("qrCodeImage").src = qrCodeUrl;
            document.getElementById("qrCodeUrl").value = qrCodeUrl;
        }





        document.getElementById("name").addEventListener("input", generateQRCode);
        document.getElementById("zone").addEventListener("input", generateQRCode);

        document.addEventListener("DOMContentLoaded", function() {
    // Apply default blue color to all Archived buttons when the page loads
    const archivedButtons = document.querySelectorAll(".archived");
    archivedButtons.forEach(button => {
        button.style.backgroundColor = "blue"; // Default blue color
        button.textContent = "Archived"; // Default text
    });
});



// Listen for adding new or editing existing residents
document.getElementById("addBtn").addEventListener("click", function (e) {
            e.preventDefault();
            const formData = new FormData(document.getElementById("userForm"));

            // ... (Required field check remains the same)

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === "success") {
                    const updatedUser = {
                        id: document.getElementById('userId').value || data.userId,
                        name: document.getElementById("name").value,
                        age: document.getElementById("age").value,
                        gender: document.getElementById("gender").value,
                        zone: document.getElementById("zone").value,
                        phone_number: document.getElementById("phone_number").value,
                        qr_code_url: `https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(document.getElementById('userId').value || data.userId)}`
                    };

                    if (document.getElementById('userId').value) { // Update
                        const row = document.getElementById(`row-${updatedUser.id}`);
                        row.innerHTML = `
                            <td>${updatedUser.name}</td>
                            <td>${updatedUser.age}</td>
                            <td>${updatedUser.gender}</td>
                            <td>${updatedUser.zone}</td>
                            <td>${updatedUser.phone_number}</td>
                            <td>
                                <img src="${updatedUser.qr_code_url}" alt="QR Code" onload="this.style.display='block';">
                                <a href="${updatedUser.qr_code_url}" download="QRCode_${updatedUser.name}.png">Download</a>
                            </td>
                            <td class="action-buttons">
                                <button onclick="editUser(${updatedUser.id}, '${updatedUser.name}', ${updatedUser.age}, '${updatedUser.gender}', '${updatedUser.zone}', '${updatedUser.phone_number}', '${updatedUser.qr_code_url}')">Edit</button>
                                <button class="archive" onclick="openArchiveModal(${updatedUser.id})">Archive</button>
                            </td>
                        `;
                    } else { // Add
                        const newRow = document.createElement("tr");
                        newRow.id = `row-${updatedUser.id}`;
                        newRow.innerHTML = `
                            <td>${updatedUser.name}</td>
                            <td>${updatedUser.age}</td>
                            <td>${updatedUser.gender}</td>
                            <td>${updatedUser.zone}</td>
                            <td>${updatedUser.phone_number}</td>
                            <td>
                                <img src="${updatedUser.qr_code_url}" alt="QR Code" onload="this.style.display='block';">
                                <a href="${updatedUser.qr_code_url}" download="QRCode_${updatedUser.name}.png">Download</a>
                            </td>
                            <td class="action-buttons">
                                <button onclick="editUser(${updatedUser.id}, '${updatedUser.name}', ${updatedUser.age}, '${updatedUser.gender}', '${updatedUser.zone}', '${updatedUser.phone_number}', '${updatedUser.qr_code_url}')">Edit</button>
                                <button class="archive" onclick="openArchiveModal(${updatedUser.id})">Archive</button>
                            </td>
                        `;
                        document.querySelector("#userTable tbody").appendChild(newRow);
                    }

                    document.getElementById("userForm").reset();
                    document.getElementById("qrCodeImage").src = "https://via.placeholder.com/100";
                    document.getElementById("qrCodeUrl").value = "";
                    document.getElementById("addBtn").textContent = "Add Resident";
                    document.getElementById('userId').value = "";
                } else {
                    alert("Error: " + data.message);
                }
            });
        });



// To store the current user ID being archived
let currentUserId = null;

// Function to open the modal and pass the user ID
function openArchiveModal(userId) {
  currentUserId = userId; // Save the user ID for archiving
  document.getElementById("archiveModal").style.display = "block";
}

// Function to close the modal
function closeArchiveModal() {
  document.getElementById("archiveModal").style.display = "none";
  currentUserId = null; // Reset the user ID
}

// Function to confirm archive with selected status
async function confirmArchive() {
    const status = document.getElementById("statusSelect").value;

    if (!status) {
        alert("Please select a status.");
        return;
    }

    if (currentUserId) {
        await archiveUser(currentUserId, status); // Pass status along with userId
        closeArchiveModal();
    } else {
        alert("Error: No user selected.");
    }
}

// Function to archive the user with status
async function archiveUser(userId, status) {
    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'archive', userId, status })
        });

        const data = await response.json();

        if (data.status === "success") {
            document.getElementById(`row-${userId}`).remove();
            alert("Resident archived successfully.");
        } else {
            alert(`Error: ${data.message}`);
        }
    } catch (error) {
        console.error("Error:", error);
        alert("An error occurred while processing the request.");
    }
}








function editUser(id, name, age, gender, zone, phone_number, qrCodeUrl) {
    document.getElementById('userId').value = id;
    document.getElementById('name').value = name;
    document.getElementById('age').value = age;
    document.getElementById('gender').value = gender;
    document.getElementById('zone').value = zone;
    document.getElementById('phone_number').value = phone_number;
    document.getElementById('qrCodeImage').src = qrCodeUrl;
    document.getElementById('qrCodeUrl').value = qrCodeUrl;
    document.getElementById("addBtn").textContent = "Update Resident"; // Change button text to 'Update Resident'
    generateQRCode(id);
}

document.getElementById("search").addEventListener("input", function () {
        const query = this.value.toLowerCase(); // Get the search query and convert to lowercase
        const rows = document.querySelectorAll("#userTable tbody tr"); // Select all rows in the table

        rows.forEach(row => {
            const name = row.children[0].textContent.toLowerCase(); // Get the text content of the "Name" column
            const zone = row.children[3].textContent.toLowerCase(); // Get the text content of the "Zone" column

            if (name.includes(query) || zone.includes(query)) {
                row.style.display = ""; // Show row if the name or zone matches the query
            } else {
                row.style.display = "none"; // Hide row if neither matches
            }
        });
    });

    function deleteUser(userId) {
    if (!confirm("Are you sure you want to archive this resident?")) return;

    fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'archive', userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === "success") {
            document.getElementById(`row-${userId}`).remove();
            alert("Resident archived successfully.");
        } else {
            alert(`Error: ${data.message}`);
        }
    })
    .catch(error => console.error("Error:", error));
}


    </script>
    
 <!-- pang color picker atuy -->
 <script>
  window.addEventListener('load', function() {
      let savedColor = localStorage.getItem('headerSidebarColor');
      if (savedColor) {
          document.querySelector('header').style.backgroundColor = savedColor;
          document.querySelector('#sidebar').style.backgroundColor = savedColor;
      }
  });

</script>

<script>
  // Apply saved colors when the page loads
  window.addEventListener('load', function() {
      // Retrieve saved colors from local storage
      const savedHeaderColor = localStorage.getItem('headerColor');
      const savedSidebarColor = localStorage.getItem('sidebarColor');

      // Apply the colors if they exist
      if (savedHeaderColor) {
          document.querySelector('header').style.backgroundColor = savedHeaderColor;
      }
      if (savedSidebarColor) {
          document.querySelector('#sidebar').style.backgroundColor = savedSidebarColor;
      }
  });
</script>

    
</body>
</html>
