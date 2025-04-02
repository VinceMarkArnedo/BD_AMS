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

// Set the default timezone
date_default_timezone_set('Asia/Manila');

// Fetch attendance records
$sql = "SELECT id, resident_name, resident_zone, event_type, time_in, time_out, zone_person, photo_path 
        FROM attendance 
        ORDER BY time_in DESC"; 

$result = $conn->query($sql);

$attendance = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attendance[] = [
            'id' => $row['id'],
            'name' => $row['resident_name'], 
            'zone' => $row['resident_zone'], 
            'event_type' => $row['event_type'],
            'time_in' => $row['time_in'], 
            'time_out' => $row['time_out'],
            'zone_person' => $row['zone_person'], // Add zone_person to the array
            'photo_path' => $row['photo_path'] // Add photo_path to the array
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_records'])) {
    $batch_id = uniqid(); // Generate a unique batch ID

    foreach ($attendance as $record) {
        // Insert records into saved_attendance table
        $stmt = $conn->prepare(
            "INSERT INTO saved_attendance 
            (batch_id, resident_name, resident_zone, event_type, time_in, time_out, saved_date, zone_person, photo_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssssssss", 
            $batch_id, 
            $record['name'], 
            $record['zone'], 
            $record['event_type'], 
            $record['time_in'], 
            $record['time_out'], 
            date('Y-m-d H:i:s'), // Current timestamp for saved_date
            $record['zone_person'], // Add zone_person
            $record['photo_path'] // Add photo_path
        );
        $stmt->execute();

        // After saving, delete record from the attendance table (if needed)
        $delete_stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
        $delete_stmt->bind_param("i", $record['id']);
        $delete_stmt->execute();
    }
    header("Location: saved_records.php"); // Redirect to saved records page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/rcrds.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery -->
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            position: relative;
            margin: 10% auto;
            padding: 20px;
            width: 80%;
            max-width: 600px;
            text-align: center;
            background-color: white;
            border-radius: 10px;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 30px;
            font-weight: bold;
            cursor: pointer;
            color: #000;
        }

        .modal-close:hover {
            color: #f00;
        }

        /* Style for the gallery icon */
        .bx-gallery {
            cursor: pointer;
            font-size: 24px;
            color: #4CAF50;
        }

        .bx-gallery:hover {
            color: #45a049;
        }
    </style>
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
                <a href="attendance.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M120-680q-17 0-28.5-11.5T80-720v-120q0-17 11.5-28.5T120-880h120q17 0 28.5 11.5T280-840q0 17-11.5 28.5T240-800h-80v80q0 17-11.5 28.5T120-680Zm0 600q-17 0-28.5-11.5T80-120v-120q0-17 11.5-28.5T120-280q17 0 28.5 11.5T160-240v80h80q17 0 28.5 11.5T280-120q0 17-11.5 28.5T240-80H120Zm600 0q-17 0-28.5-11.5T680-120q0-17 11.5-28.5T720-160h80v-80q0-17 11.5-28.5T840-280q17 0 28.5 11.5T880-240v120q0 17-11.5 28.5T840-80H720Zm120-600q-17 0-28.5-11.5T800-720v-80h-80q-17 0-28.5-11.5T680-840q0-17 11.5-28.5T720-880h120q17 0 28.5 11.5T880-840v120q0 17-11.5 28.5T840-680ZM700-200v-60h60v60h-60Zm0-120v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60-60v-60h60v60h-60Zm120-120v-60h60v60h-60Zm-60 60v-60h60v60h-60Zm-60-60v-60h60v60h-60Zm40-140q-17 0-28.5-11.5T520-560v-160q0-17 11.5-28.5T560-760h160q17 0 28.5 11.5T760-720v160q0 17-11.5 28.5T720-520H560ZM240-200q-17 0-28.5-11.5T200-240v-160q0-17 11.5-28.5T240-440h160q17 0 28.5 11.5T440-400v160q0 17-11.5 28.5T400-200H240Zm0-320q-17 0-28.5-11.5T200-560v-160q0-17 11.5-28.5T240-760h160q17 0 28.5 11.5T440-720v160q0 17-11.5 28.5T400-520H240Zm20 260h120v-120H260v120Zm0-320h120v-120H260v120Zm320 0h120v-120H580v120Z"/></svg>
                <span>Scan Attendance</span>
                </a>
            </li>
            <li>
                <a href="saved_records.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M600-160q-17 0-28.5-11.5T560-200q0-31 44-55.5T720-280q72 0 116 24.5t44 55.5q0 17-11.5 28.5T840-160H600Zm120-160q-33 0-56.5-23.5T640-400q0-33 23.5-56.5T720-480q33 0 56.5 23.5T800-400q0 33-23.5 56.5T720-320Zm-560 80v-480 172-12 320Zm0 80q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h207q16 0 30.5 6t25.5 17l57 57h320q33 0 56.5 23.5T880-640v80q0 17-11.5 28.5T840-520q-17 0-28.5-11.5T800-560v-80H447l-80-80H160v480h280q17 0 28.5 11.5T480-200q0 17-11.5 28.5T440-160H160Z"/></svg>
                <span>Records</span>
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
        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M591-160q-12.75 0-21.37-8.63Q561-177.25 561-190q0-33 42.5-55T721-267q75 0 117.5 22t42.5 55q0 12.75-8.62 21.37Q863.75-160 851-160H591Zm130.08-174q-30.08 0-51.58-21.42t-21.5-51.5q0-30.08 21.42-51.58t51.5-21.5q30.08 0 51.58 21.42t21.5 51.5q0 30.08-21.42 51.58t-51.5 21.5ZM140-220v-520 220-20 320Zm0 60q-24 0-42-18.5T80-220v-520q0-23 18-41.5t42-18.5h256q12.44 0 23.72 5t19.37 13.09L481-740h339q23 0 41.5 18.5T880-680v158q0 12.75-8.68 21.37-8.67 8.63-21.5 8.63-12.82 0-21.32-8.63-8.5-8.62-8.5-21.37v-158H456l-60-60H140v520h335q12.75 0 21.38 8.68 8.62 8.67 8.62 21.5 0 12.82-8.62 21.32-8.63 8.5-21.38 8.5H140Z"/></svg>
                <span>Attendance Records</span>   
                </div>  
                <h2>
                <div class="bell">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#FFFFFF"><path d="M160-200v-66.67h80v-296q0-83.66 49.67-149.5Q339.33-778 420-796v-24q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v24q80.67 18 130.33 83.83Q720-646.33 720-562.67v296h80V-200H160Zm320-301.33ZM480-80q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM306.67-266.67h346.66v-296q0-72-50.66-122.66Q552-736 480-736t-122.67 50.67q-50.66 50.66-50.66 122.66v296Z"/></svg>
                </div>
                </h2>
        </header>
    <div class="main-content">
        <form id="saveRecordsForm" style="float: right; margin-bottom: 10px;">
            <button type="button" id="saveRecordsButton">Save Records</button>
        </form>
        <button onclick="downloadTableAsExcel()" style="float: right; margin-right: 10px; margin-bottom: 10px;">Download as Excel</button>
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>Event Type</th>
                    <th>Name</th>
                    <th>Zone</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Zone Person</th>
                    <th>Photo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['event_type']) ?></td>
                        <td><?= htmlspecialchars($record['name']) ?></td>
                        <td><?= htmlspecialchars($record['zone']) ?></td>
                        <td><?= htmlspecialchars($record['time_in']) ?></td>
                        <td><?= htmlspecialchars($record['time_out']) ?></td>
                        <td><?= htmlspecialchars($record['zone_person']) ?></td>
                        <td>
                            <?php if (!empty($record['photo_path'])): ?>
                                <!-- Gallery Icon to View Image -->
                                <i class='bx bxs-image' onclick="viewPhoto('<?= base64_encode($record['photo_path']) ?>')"></i>
                            <?php else: ?>
                                <span>No Photo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Modal to Display Image -->
    <div id="photoViewModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closePhotoModal()">&times;</span>
            <img id="modalPhoto" src="" alt="Resident Photo" style="max-width: 100%; max-height: 80vh;">
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

        // AJAX request to save records without refreshing the page
        $(document).ready(function () {
            $('#saveRecordsButton').click(function () {
                $.ajax({
                    url: '', // Current script processes the POST request
                    method: 'POST',
                    data: { save_records: true }, // Send 'save_records' flag to trigger the PHP logic
                    success: function (response) {
                        alert("Records have been saved successfully!"); // Optional success message
                        $('#attendanceTable tbody').empty(); // Clear the table since records have been moved
                    },
                    error: function (xhr, status, error) {
                        console.error("Error saving records: ", error);
                        alert("An error occurred while saving records.");
                    }
                });
            });
        });

        function downloadTableAsExcel() {
            // Excel export logic remains unchanged
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.table_to_sheet(document.getElementById('attendanceTable'));

            // Auto-width adjustment logic
            var colWidths = [];
            var data = XLSX.utils.sheet_to_json(ws, { header: 1 });
            data.forEach(row => {
                row.forEach((cell, i) => {
                    if (cell) {
                        const cellLength = cell.toString().length;
                        colWidths[i] = Math.max(colWidths[i] || 0, cellLength);
                    }
                });
            });

            ws['!cols'] = colWidths.map((width, index) => {
                return { wch: index === 3 || index === 4 ? Math.max(width, 20) : width + 2 };
            });

            XLSX.utils.book_append_sheet(wb, ws, "Attendance Records");
            XLSX.writeFile(wb, "attendance_records.xlsx");
        }
    </script>

    <!-- pang color picker atuy -->
    <script>
        // Apply saved color when page loads
        window.addEventListener('load', function() {
            let savedColor = localStorage.getItem('headerSidebarColor');
            if (savedColor) {
                document.querySelector('header').style.backgroundColor = savedColor;
                document.querySelector('.sidebar').style.backgroundColor = savedColor;
                document.getElementById('colorPicker').value = savedColor; // Set the color picker to the saved color
            }
        });

        // Update color on input and save it to local storage
        document.getElementById('colorPicker').addEventListener('input', function() {
            let color = this.value;
            document.querySelector('header').style.backgroundColor = color;
            document.querySelector('.sidebar').style.backgroundColor = color;

            // Save color to local storage
            localStorage.setItem('headerSidebarColor', color);
        });
    </script>
</body>
</html>