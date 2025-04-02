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

// Fetch events for the dropdown
$sqlEvents = "SELECT DISTINCT event_type FROM saved_attendance";
$resultEvents = $conn->query($sqlEvents);
$events = [];
if ($resultEvents->num_rows > 0) {
    while ($row = $resultEvents->fetch_assoc()) {
        $events[] = $row['event_type'];
    }
}



// Define today's date, current month, and current year
$today = date('Y-m-d'); // Today's date in YYYY-MM-DD format

$currentYear = date('Y'); // Current year in YYYY format

// Fetch all saved attendance records
$sqlAll = "SELECT batch_id, resident_name, resident_zone, event_type, time_in, time_out, zone_person, photo_path, 
                  DATE_FORMAT(saved_date, '%M %d, %Y') as saved_date 
           FROM saved_attendance 
           ORDER BY saved_date DESC, batch_id DESC, time_in DESC";

$resultAll = $conn->query($sqlAll);

// Process all attendance records
$savedAttendance = [];
if ($resultAll->num_rows > 0) {
    while ($row = $resultAll->fetch_assoc()) {
        $savedAttendance[] = [
            'batch_id' => $row['batch_id'],
            'name' => $row['resident_name'], 
            'zone' => $row['resident_zone'], 
            'event_type' => $row['event_type'],
            'time_in' => $row['time_in'], 
            'time_out' => $row['time_out'],
            'zone_person' => $row['zone_person'], // Add zone_person
            'photo_path' => $row['photo_path'], // Add photo_path
            'saved_date' => $row['saved_date']
        ];
    }
}

// Fetch today's records
$sqlToday = "SELECT batch_id, resident_name, resident_zone, event_type, time_in, time_out, zone_person, photo_path,
                    DATE_FORMAT(saved_date, '%M %d, %Y') as saved_date 
             FROM saved_attendance 
             WHERE DATE(saved_date) = '$today' 
             ORDER BY batch_id DESC, time_in DESC";

$resultToday = $conn->query($sqlToday);

// Process today's attendance
$todayAttendance = [];
if ($resultToday->num_rows > 0) {
    while ($row = $resultToday->fetch_assoc()) {
        $todayAttendance[] = [
            'batch_id' => $row['batch_id'],
            'name' => $row['resident_name'], 
            'zone' => $row['resident_zone'], 
            'event_type' => $row['event_type'],
            'time_in' => $row['time_in'], 
            'time_out' => $row['time_out'],
            'zone_person' => $row['zone_person'], // Add zone_person
            'photo_path' => $row['photo_path'],
            'saved_date' => $row['saved_date']
        ];
    }
}

// Fetch monthly records
// Fetch monthly records
$currentMonth = date('Y-m'); // Current month in YYYY-MM format
$sqlMonthly = "SELECT batch_id, resident_name, resident_zone, event_type, time_in, time_out, zone_person, photo_path, 
                      DATE_FORMAT(saved_date, '%M %d, %Y') as saved_date 
               FROM saved_attendance 
               WHERE DATE_FORMAT(saved_date, '%Y-%m') = '$currentMonth' 
               ORDER BY batch_id DESC, time_in DESC";

$resultMonthly = $conn->query($sqlMonthly);

// Process monthly attendance
$monthlyAttendance = [];
if ($resultMonthly->num_rows > 0) {
    while ($row = $resultMonthly->fetch_assoc()) {
        $monthlyAttendance[] = [
            'batch_id' => $row['batch_id'],
            'name' => $row['resident_name'], 
            'zone' => $row['resident_zone'], 
            'event_type' => $row['event_type'],
            'time_in' => $row['time_in'], 
            'time_out' => $row['time_out'],
            'zone_person' => $row['zone_person'], // Add zone_person
            'photo_path' => $row['photo_path'], // Add photo_path
            'saved_date' => $row['saved_date']
        ];
    }
}

// Fetch yearly records
$sqlYearly = "SELECT batch_id, resident_name, resident_zone, event_type, time_in, time_out, zone_person, photo_path, 
                     DATE_FORMAT(saved_date, '%M %d, %Y') as saved_date 
              FROM saved_attendance 
              WHERE YEAR(saved_date) = '$currentYear' 
              ORDER BY batch_id DESC, time_in DESC";

$resultYearly = $conn->query($sqlYearly);

// Process yearly attendance
$yearlyAttendance = [];
if ($resultYearly->num_rows > 0) {
    while ($row = $resultYearly->fetch_assoc()) {
        $yearlyAttendance[] = [
            'batch_id' => $row['batch_id'],
            'name' => $row['resident_name'], 
            'zone' => $row['resident_zone'], 
            'event_type' => $row['event_type'],
            'time_in' => $row['time_in'], 
            'time_out' => $row['time_out'],
            'zone_person' => $row['zone_person'],
            'photo_path' => $row['photo_path'], // Add zone_person
            'saved_date' => $row['saved_date']
        ];
    }
}

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $sqlAll = "SELECT batch_id, resident_name, resident_zone, event_type, time_in, time_out, zone_person, photo_path, 
                      DATE_FORMAT(saved_date, '%M %d, %Y') as saved_date 
               FROM saved_attendance 
               WHERE DATE(saved_date) BETWEEN '$startDate' AND '$endDate'
               ORDER BY saved_date DESC, batch_id DESC, time_in DESC";
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Attendance Records</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/sves.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- Flatpickr CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
</head>
<body>
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
                    <li><a href="">
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
    <div class="main-content">
    <header id="header">
        <div class="logo-pie">
        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M140-160q-23 0-41.5-18.5T80-220v-520q0-23 18.5-41.5T140-800h256q12.44 0 23.72 5t19.37 13.09L481-740h369q12.75 0 21.38 8.68 8.62 8.67 8.62 21.5 0 12.82-8.62 21.32-8.63 8.5-21.38 8.5H455l-60-60H140v520l90-355q5-20 21.83-32.5Q268.65-620 289-620h574q29 0 47.5 23t10.5 52l-88 339q-6 24-22 35t-41 11H140Zm63-60h572l84-340H287l-84 340Zm-63-353v-167 167Zm63 353 84-340-84 340Z"/></svg>
                <span>Records</span>   
                </div>  
                <h2>
                <div class="bell">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#FFFFFF"><path d="M160-200v-66.67h80v-296q0-83.66 49.67-149.5Q339.33-778 420-796v-24q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v24q80.67 18 130.33 83.83Q720-646.33 720-562.67v296h80V-200H160Zm320-301.33ZM480-80q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM306.67-266.67h346.66v-296q0-72-50.66-122.66Q552-736 480-736t-122.67 50.67q-50.66 50.66-50.66 122.66v296Z"/></svg>
                </div>
               
            
                </h2>
          
            
        </header>
        <div class="continer">
        <div class="hid">
        <div class="records-header">

<div class="calendar" style="display: inline-block; margin-right: 10px;">
<label for="date-range">Choose Date Range:</label>
<input type="text" id="date-range" name="date-range" placeholder="Select Date Range">
</div>
<button id="clear-date-range" style="margin-left: 10px;">Clear Date Range</button>
<button id="download-excel-btn" class="btn btn-success">Download Excel</button>
<!-- New Dropdown -->
<div class="dropdown" style="display: inline-block; margin-right: 10px;">
<button>Events</button>
<div class="dropdown-content" id="event-dropdown">
         <a href="#" id="all-events" class="filter-event" data-event="all">All Records</a>
        <?php foreach ($events as $event): ?>
            <a href="#" class="filter-event" data-event="<?= htmlspecialchars($event) ?>"><?= htmlspecialchars($event) ?></a>
        <?php endforeach; ?>
    </div>
</div>



<div class="dropdown">
    <button>Reports</button>
    <div class="dropdown-content">
        <a href="#" id="show-daily">Daily</a>
        <a href="#" id="show-monthly">Monthly</a>
        <a href="#" id="show-yearly">Yearly</a>
        <a href="#" id="show-all">All Records</a>
    </div>
</div>
</div>
        </div>

        <!-- All Records -->
<div id="all-records">
    <?php 
    $currentBatch = "";
    foreach ($savedAttendance as $record): 
        if ($currentBatch !== $record['batch_id']):
            if ($currentBatch !== ""): ?>
                </tbody></table>
            <?php endif;
            $currentBatch = $record['batch_id']; ?>
            <h2>
                Saved Date: <?= htmlspecialchars($record['saved_date']) ?>
                <input type="checkbox" class="batch-checkbox" data-batch-id="<?= htmlspecialchars($record['batch_id']) ?>"> Select table to download
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Name</th>
                        <th>Zone</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Zone Person</th> <!-- New column for zone_person -->
                        <th>Photo</th> <!-- New column for photo_path -->
                    </tr>
                </thead>
                <tbody>
        <?php endif; ?>
            <tr>
                <td><?= htmlspecialchars($record['event_type']) ?></td>
                <td><?= htmlspecialchars($record['name']) ?></td>
                <td><?= htmlspecialchars($record['zone']) ?></td>
                <td><?= htmlspecialchars($record['time_in']) ?></td>
                <td><?= htmlspecialchars($record['time_out']) ?></td>
                <td><?= htmlspecialchars($record['zone_person']) ?></td> <!-- Display zone_person -->
                <td>
                    <?php if (!empty($record['photo_path'])): ?>
                        <!-- Gallery Icon to View Image -->
                        <i class='bx bxs-image' onclick="viewPhoto('<?= base64_encode($record['photo_path']) ?>')"></i>
                    <?php else: ?>
                        <span>No Photo</span>
                    <?php endif; ?>
                </td>
            </tr>
    <?php endforeach; 
    if ($currentBatch !== ""): ?>
        </tbody></table>
    <?php endif; ?>
</div>

       <!-- Today's Records -->
<div id="daily-records" style="display: none;">
    <?php 
    $currentBatch = "";
    foreach ($todayAttendance as $record): 
        if ($currentBatch !== $record['batch_id']):
            if ($currentBatch !== ""): ?>
                </tbody></table>
            <?php endif;
            $currentBatch = $record['batch_id']; ?>
            <h2>
                    Saved Date: <?= htmlspecialchars($record['saved_date']) ?>
                    <input type="checkbox" class="batch-checkbox" data-batch-id="<?= htmlspecialchars($record['batch_id']) ?>"> Select table to download
                </h2>
                <table>
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Name</th>
                        <th>Zone</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Zone Person</th> <!-- New column for zone_person -->
                        <th>Photo</th> <!-- New column for photo_path -->
                    </tr>
                </thead>
                <tbody>
        <?php endif; ?>
            <tr>
                <td><?= htmlspecialchars($record['event_type']) ?></td>
                <td><?= htmlspecialchars($record['name']) ?></td>
                <td><?= htmlspecialchars($record['zone']) ?></td>
                <td><?= htmlspecialchars($record['time_in']) ?></td>
                <td><?= htmlspecialchars($record['time_out']) ?></td>
                <td><?= htmlspecialchars($record['zone_person']) ?></td> <!-- Display zone_person -->
                <td>
                    <?php if (!empty($record['photo_path'])): ?>
                        <!-- Gallery Icon to View Image -->
                        <i class='bx bxs-image' onclick="viewPhoto('<?= base64_encode($record['photo_path']) ?>')"></i>
                    <?php else: ?>
                        <span>No Photo</span>
                    <?php endif; ?>
                </td>
            </tr>
    <?php endforeach; 
    if ($currentBatch !== ""): ?>
        </tbody></table>
    <?php endif; ?>
</div>

       <!-- Monthly Records -->
<div id="monthly-records" style="display: none;">
    <?php 
    $currentBatch = "";
    foreach ($monthlyAttendance as $record): 
        if ($currentBatch !== $record['batch_id']):
            if ($currentBatch !== ""): ?>
                </tbody></table>
            <?php endif;
            $currentBatch = $record['batch_id']; ?>
            <h2>
                Saved Date: <?= htmlspecialchars($record['saved_date']) ?>
                <input type="checkbox" class="batch-checkbox" data-batch-id="<?= htmlspecialchars($record['batch_id']) ?>"> Select table to download
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Name</th>
                        <th>Zone</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Zone Person</th> <!-- New column for zone_person -->
                        <th>Photo</th> <!-- New column for photo_path -->
                    </tr>
                </thead>
                <tbody>
        <?php endif; ?>
            <tr>
                <td><?= htmlspecialchars($record['event_type']) ?></td>
                <td><?= htmlspecialchars($record['name']) ?></td>
                <td><?= htmlspecialchars($record['zone']) ?></td>
                <td><?= htmlspecialchars($record['time_in']) ?></td>
                <td><?= htmlspecialchars($record['time_out']) ?></td>
                <td><?= htmlspecialchars($record['zone_person']) ?></td> <!-- Display zone_person -->
                <td>
                    <?php if (!empty($record['photo_path'])): ?>
                        <!-- Gallery Icon to View Image -->
                        <i class='bx bxs-image' onclick="viewPhoto('<?= base64_encode($record['photo_path']) ?>')"></i>
                    <?php else: ?>
                        <span>No Photo</span>
                    <?php endif; ?>
                </td>
            </tr>
    <?php endforeach; 
    if ($currentBatch !== ""): ?>
        </tbody></table>
    <?php endif; ?>
</div>

       <!-- Yearly Records -->
<div id="yearly-records" style="display: none;">
    <?php 
    $currentBatch = "";
    foreach ($yearlyAttendance as $record): 
        if ($currentBatch !== $record['batch_id']):
            if ($currentBatch !== ""): ?>
                </tbody></table>
            <?php endif;
            $currentBatch = $record['batch_id']; ?>
            <h2>
                Saved Date: <?= htmlspecialchars($record['saved_date']) ?>
                <input type="checkbox" class="batch-checkbox" data-batch-id="<?= htmlspecialchars($record['batch_id']) ?>"> Select table to download
            </h2>
            <table>
                <thead>
                    <tr>
                        <th>Event Type</th>
                        <th>Name</th>
                        <th>Zone</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Name of Kagawad</th> <!-- New column for zone_person -->
                    </tr>
                </thead>
                <tbody>
        <?php endif; ?>
            <tr>
                <td><?= htmlspecialchars($record['event_type']) ?></td>
                <td><?= htmlspecialchars($record['name']) ?></td>
                <td><?= htmlspecialchars($record['zone']) ?></td>
                <td><?= htmlspecialchars($record['time_in']) ?></td>
                <td><?= htmlspecialchars($record['time_out']) ?></td>
                <td><?= htmlspecialchars($record['zone_person']) ?></td> <!-- Display zone_person -->
            </tr>
    <?php endforeach; 
    if ($currentBatch !== ""): ?>
        </tbody></table>
    <?php endif; ?>
</div>
<!-- Modal to Display Image -->
<div id="photoViewModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closePhotoModal()">&times;</span>
        <img id="modalPhoto" src="" alt="Resident Photo" style="max-width: 100%; max-height: 80vh;">
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
        
        closeAllSubMenu()

    }
    function toggleSubMenu(button) {
        if(!button.nextElementSibling.classList.contains('show')){
            closeAllSubMenu()
        }
        
        button.nextElementSibling.classList.toggle('show');
        button.classList.toggle('rotate');

        if(sidebar.classList.contains('close')) {
            sidebar.classList.remove('close');
            toggleButton.classList.toggle('rotate');
        }
    }
    function closeAllSubMenu(){
        Array.from(sidebar.getElementsByClassName('show')).forEach(ul => {
            ul.classList.remove('show');
            ul.previousElementSibling.classList.remove('rotate');
        });
    }
        //end sidebar toggle 

        //for the reports menu
        const showMonthly = document.getElementById('show-monthly');
        const showYearly = document.getElementById('show-yearly');
        const showDaily = document.getElementById('show-daily');
        const showAll = document.getElementById('show-all');
        const monthlyRecords = document.getElementById('monthly-records');
        const yearlyRecords = document.getElementById('yearly-records');
        const dailyRecords = document.getElementById('daily-records');
        const allRecords = document.getElementById('all-records');

        

        document.getElementById('clear-date-range').addEventListener('click', function() {
    document.getElementById('date-range')._flatpickr.clear(); // Clear the date range
    const allRecords = document.querySelectorAll('#all-records table');
    allRecords.forEach(table => {
        table.style.display = ''; // Show all tables
        table.previousElementSibling.style.display = ''; // Show all headers
    });
});

        showMonthly.addEventListener('click', () => {
            allRecords.style.display = 'none';
            dailyRecords.style.display = 'none';
            yearlyRecords.style.display = 'none';
            monthlyRecords.style.display = 'block';
        });

        showYearly.addEventListener('click', () => {
            allRecords.style.display = 'none';
            dailyRecords.style.display = 'none';
            monthlyRecords.style.display = 'none';
            yearlyRecords.style.display = 'block';
        });

        showDaily.addEventListener('click', () => {
            allRecords.style.display = 'none';
            monthlyRecords.style.display = 'none';
            yearlyRecords.style.display = 'none';
            dailyRecords.style.display = 'block';
        });

        showAll.addEventListener('click', () => {
            dailyRecords.style.display = 'none';
            monthlyRecords.style.display = 'none';
            yearlyRecords.style.display = 'none';
            allRecords.style.display = 'block';
        });

        
        //for the event menu atuy
        document.querySelectorAll('.filter-event').forEach(eventLink => {
    eventLink.addEventListener('click', function(e) {
        e.preventDefault();

        // Get the selected event type
        const selectedEvent = this.dataset.event;

        // Get all zone headers and tables
        const zoneHeaders = document.querySelectorAll('#all-records h2');
        const zoneTables = document.querySelectorAll('#all-records table');

        // Loop through each zone
        zoneHeaders.forEach((header, index) => {
            const table = zoneTables[index];
            const rows = table.querySelectorAll('tbody tr');
            let hasMatchingEvent = false;

            // Loop through rows in the current zone's table
            rows.forEach(row => {
                const eventType = row.querySelector('td:first-child').textContent.trim();

                // Show or hide rows based on the selected event
                if (selectedEvent === 'all' || eventType === selectedEvent) {
                    row.style.display = ''; // Show matching row
                    hasMatchingEvent = true; // Mark zone as having matching event
                } else {
                    row.style.display = 'none'; // Hide non-matching row
                }
            });

            // Show or hide the entire zone based on whether it has matching events
            if (hasMatchingEvent || selectedEvent === 'all') {
                header.style.display = ''; // Show zone header
                table.style.display = ''; // Show zone table
            } else {
                header.style.display = 'none'; // Hide zone header
                table.style.display = 'none'; // Hide zone table
            }
        });
    });
});
    //end of event menu atuy
    
    // Function to open the modal and display the photo
    function viewPhoto(photoData) {
        const modal = document.getElementById('photoViewModal');
        const modalPhoto = document.getElementById('modalPhoto');
        modalPhoto.src = "data:image/jpeg;base64," + photoData; // Set the image source
        modal.style.display = "block"; // Show the modal
    }

    // Function to close the modal
    function closePhotoModal() {
        const modal = document.getElementById('photoViewModal');
        modal.style.display = "none"; // Hide the modal
    }

    // Close modal if user clicks outside the image
    window.onclick = function (event) {
        const modal = document.getElementById('photoViewModal');
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };

     // Update event dropdown dynamically when events are added, edited, or removed
     function updateEventDropdown() {
            const eventDropdown = document.getElementById('event-dropdown');
            fetch('get_events.php') // Assuming `get_events.php` fetches the list of event types
                .then(response => response.json())
                .then(events => {
                    eventDropdown.innerHTML = '';
                    events.forEach(event => {
                        const eventItem = document.createElement('a');
                        eventItem.href = '#';
                        eventItem.classList.add('filter-event');
                        eventItem.setAttribute('data-event', event);
                        eventItem.textContent = event;
                        eventDropdown.appendChild(eventItem);
                    });
                });
        }

        function downloadExcelAndRefresh() {
    const selectedTables = document.querySelectorAll('.batch-checkbox:checked');
    if (selectedTables.length === 0) {
        alert('Please select at least one batch to download.');
        return;
    }

    const wb = XLSX.utils.book_new();
    selectedTables.forEach(checkbox => {
        const batchId = checkbox.getAttribute('data-batch-id');
        const table = document.querySelector(`#table-${batchId}`);
        const sheetData = [];
        const colWidths = [];

        // Get table headers
        const headers = table.querySelectorAll('thead th');
        const headerRow = [];
        headers.forEach((header, index) => {
            headerRow.push(header.textContent);
            colWidths[index] = header.textContent.length;  // Initialize column width based on header length
        });
        sheetData.push(headerRow);

        // Get table rows
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const rowData = [];
            row.querySelectorAll('td').forEach((cell, index) => {
                const cellText = cell.textContent;
                rowData.push(cellText);
                // Adjust column width if current cell's content is longer than the header or previous rows
                colWidths[index] = Math.max(colWidths[index], cellText.length);
            });
            sheetData.push(rowData);
        });

        // Add column width settings to sheet
        const sheetName = `Batch ID ${batchId}`;
        const ws = XLSX.utils.aoa_to_sheet(sheetData);

        // Set the column widths (add padding for better visibility)
        ws['!cols'] = colWidths.map(width => ({ wch: width + 2 }));  // Adjust width by adding 2 for padding

        // Add sheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
    });

    // Download the workbook as an Excel file
    XLSX.writeFile(wb, 'attendance_records.xlsx');

    // Automatically refresh the page after download
    window.location.reload();
}

// Attach the function to the download button
document.getElementById('download-excel-btn').addEventListener('click', function() {
    const selectedTables = document.querySelectorAll('.batch-checkbox:checked');
    if (selectedTables.length === 0) {
        alert('Please select at least one batch to download.');
        return;
    }

    const wb = XLSX.utils.book_new();
    selectedTables.forEach(checkbox => {
        const batchId = checkbox.getAttribute('data-batch-id');
        const table = checkbox.closest('h2').nextElementSibling; // Get the table following the h2
        const sheetData = [];
        const colWidths = [];

        // Get table headers
        const headers = table.querySelectorAll('thead th');
        const headerRow = [];
        headers.forEach((header, index) => {
            headerRow.push(header.textContent);
            colWidths[index] = header.textContent.length;
        });
        sheetData.push(headerRow);

        // Get table rows
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const rowData = [];
            row.querySelectorAll('td').forEach((cell, index) => {
                const cellText = cell.textContent;
                rowData.push(cellText);
                colWidths[index] = Math.max(colWidths[index], cellText.length);
            });
            sheetData.push(rowData);
        });

        // Add sheet to workbook
        const sheetName = `Batch ${batchId}`;
        const ws = XLSX.utils.aoa_to_sheet(sheetData);
        XLSX.utils.book_append_sheet(wb, ws, sheetName);
    });

    // Download the workbook as an Excel file
    XLSX.writeFile(wb, 'attendance_records.xlsx');
});


    </script>

<script>
    // Initialize Flatpickr with range option
    flatpickr("#date-range", {
        mode: "range", // Enable range selection
        dateFormat: "Y-m-d", // Date format
        altInput: true, // Show user-friendly date format
        altFormat: "F j, Y", // User-friendly date format
        onClose: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                const startDate = selectedDates[0].toISOString().split('T')[0]; // Start date in YYYY-MM-DD format
                const endDate = selectedDates[1].toISOString().split('T')[0]; // End date in YYYY-MM-DD format
                filterTablesByEventAndDateRange(startDate, endDate); // Filter tables based on the date range and selected event
            }
        }
    });

    // Function to filter tables by event and date range
    function filterTablesByEventAndDateRange(startDate, endDate) {
        const selectedEvent = document.querySelector('.filter-event.active')?.dataset.event || 'all'; // Get the selected event
        const allTables = document.querySelectorAll('#all-records table'); // Get all tables

        allTables.forEach(table => {
            const tableHeader = table.previousElementSibling; // Get the table header (h2)
            const savedDateText = tableHeader.textContent.match(/\w+ \d{1,2}, \d{4}/)[0]; // Extract the saved date (e.g., "October 10, 2023")
            const savedDate = new Date(savedDateText).toISOString().split('T')[0]; // Convert to YYYY-MM-DD format

            // Check if the saved date falls within the selected range
            const isDateInRange = savedDate >= startDate && savedDate <= endDate;

            // Check if the table contains the selected event
            const rows = table.querySelectorAll('tbody tr');
            let hasMatchingEvent = false;

            rows.forEach(row => {
                const eventType = row.querySelector('td:first-child').textContent.trim();
                if (selectedEvent === 'all' || eventType === selectedEvent) {
                    hasMatchingEvent = true;
                }
            });

            // Show or hide the table based on the date range and event
            if (isDateInRange && hasMatchingEvent) {
                table.style.display = ''; // Show the table
                tableHeader.style.display = ''; // Show the header
            } else {
                table.style.display = 'none'; // Hide the table
                tableHeader.style.display = 'none'; // Hide the header
            }
        });
    }

    // Clear date range and reset tables to original state
    document.getElementById('clear-date-range').addEventListener('click', function() {
        document.getElementById('date-range')._flatpickr.clear(); // Clear the date range input

        // Get the selected event
        const selectedEvent = document.querySelector('.filter-event.active')?.dataset.event || 'all';

        // Filter tables by event only (hide saved dates with no records for the selected event)
        filterTablesByEvent(selectedEvent);
    });

    // Event filtering logic
    document.querySelectorAll('.filter-event').forEach(eventLink => {
        eventLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all event links
            document.querySelectorAll('.filter-event').forEach(link => link.classList.remove('active'));

            // Add active class to the clicked event link
            this.classList.add('active');

            // Get the selected event type
            const selectedEvent = this.dataset.event;

            // Filter tables by event and date range
            const dateRange = document.getElementById('date-range')._flatpickr.selectedDates;
            if (dateRange.length === 2) {
                const startDate = dateRange[0].toISOString().split('T')[0];
                const endDate = dateRange[1].toISOString().split('T')[0];
                filterTablesByEventAndDateRange(startDate, endDate);
            } else {
                filterTablesByEvent(selectedEvent); // If no date range is selected, filter only by event
            }
        });
    });

    // Function to filter tables by event only
    function filterTablesByEvent(selectedEvent) {
        const allTables = document.querySelectorAll('#all-records table'); // Get all tables
        allTables.forEach(table => {
            const tableHeader = table.previousElementSibling; // Get the table header (h2)
            const rows = table.querySelectorAll('tbody tr');
            let hasMatchingEvent = false;

            // Loop through rows in the current table
            rows.forEach(row => {
                const eventType = row.querySelector('td:first-child').textContent.trim();

                // Show or hide rows based on the selected event
                if (selectedEvent === 'all' || eventType === selectedEvent) {
                    row.style.display = ''; // Show matching row
                    hasMatchingEvent = true; // Mark table as having matching event
                } else {
                    row.style.display = 'none'; // Hide non-matching row
                }
            });

            // Show or hide the entire table based on whether it has matching events
            if (hasMatchingEvent || selectedEvent === 'all') {
                table.style.display = ''; // Show table
                tableHeader.style.display = ''; // Show header
            } else {
                table.style.display = 'none'; // Hide table
                tableHeader.style.display = 'none'; // Hide header
            }
        });
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