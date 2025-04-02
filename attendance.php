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
    die("An error occurred while connecting to the database. Please try again later.");
}

// Set timezone to match the server timezone
date_default_timezone_set('Asia/Manila');

// Log attendance when QR code is scanned
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $qrCodeData = $_POST['qrCodeData'];  // userId from the scanned QR code
    $eventType = $_POST['eventType'];    // Event type passed from dashboard.html
    $zonePerson = $_POST['zonePerson'];  // Zone person from the h2 element (updated from settings)

    // Fetch the resident details based on the userId from the database
    $sql = "SELECT id, name, zone FROM residents WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $qrCodeData);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Resident found
        $resident = $result->fetch_assoc();
        $resident_id = $resident['id'];
        $name = $resident['name'];
        $zone = $resident['zone'];

        // Find today's attendance record for the resident
        $today = date('Y-m-d');
        $sql = "SELECT * FROM attendance WHERE resident_id=? AND scan_date=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $resident_id, $today);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // No record for today, insert as time in (first scan)
            $sql = "INSERT INTO attendance (resident_id, resident_name, resident_zone, event_type, time_in, scan_date, zone_person) 
                    VALUES (?, ?, ?, ?, NOW(), ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $resident_id, $name, $zone, $eventType, $today, $zonePerson);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Time in recorded successfully"]);
            } else {
                echo json_encode(["status" => "error", "message" => $stmt->error]);
            }
        } else {
            // Record for today exists, check if time out is null
            $attendance = $result->fetch_assoc();
            if (is_null($attendance['time_out'])) {
                // Time out has not been recorded, so mark time out (second scan)
                $sql = "UPDATE attendance SET time_out=NOW() WHERE resident_id=? AND scan_date=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $resident_id, $today);
                if ($stmt->execute()) {
                    echo json_encode(["status" => "success", "message" => "Time out recorded successfully"]);
                } else {
                    echo json_encode(["status" => "error", "message" => $stmt->error]);
                }
            } else {
                // Both time in and time out are already recorded, reject the scan (third scan)
                echo json_encode(["status" => "error", "message" => "Both time in and time out are already recorded for today."]);
            }
        }
    } else {
        // Resident not found
        echo json_encode(["status" => "error", "message" => "Resident not found"]);
    }
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scanner</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/presents.css">
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
    <style>
        
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
                <a href="records.php">
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
   <div class="main-content">
   <header id="header">
        <div class="logo-pie">
        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M200-400v-80h80v80h-80Zm-80-80v-80h80v80h-80Zm360-280v-80h80v80h-80ZM170-650h140v-140H170v140Zm0 480h140v-140H170v140Zm480-480h140v-140H650v140ZM360-400v-80h-80v-80h160v160h-80Zm40-200v-160h80v80h80v80H400Zm-190-90v-60h60v60h-60Zm0 480v-60h60v60h-60Zm480-480v-60h60v60h-60Zm-40 440H550q-12.75 0-21.37-8.68-8.63-8.67-8.63-21.5 0-12.82 8.63-21.32 8.62-8.5 21.37-8.5h100v-100q0-12.75 8.68-21.38 8.67-8.62 21.5-8.62 12.82 0 21.32 8.62 8.5 8.63 8.5 21.38v100h100q12.75 0 21.38 8.68 8.62 8.67 8.62 21.5 0 12.82-8.62 21.32-8.63 8.5-21.38 8.5H710v100q0 12.75-8.68 21.37-8.67 8.63-21.5 8.63-12.82 0-21.32-8.63-8.5-8.62-8.5-21.37v-100ZM120-630v-180q0-12.75 8.63-21.38Q137.25-840 150-840h180q12.75 0 21.38 8.62Q360-822.75 360-810v180q0 12.75-8.62 21.37Q342.75-600 330-600H150q-12.75 0-21.37-8.63Q120-617.25 120-630Zm0 480v-180q0-12.75 8.63-21.38Q137.25-360 150-360h180q12.75 0 21.38 8.62Q360-342.75 360-330v180q0 12.75-8.62 21.37Q342.75-120 330-120H150q-12.75 0-21.37-8.63Q120-137.25 120-150Zm480-480v-180q0-12.75 8.63-21.38Q617.25-840 630-840h180q12.75 0 21.38 8.62Q840-822.75 840-810v180q0 12.75-8.62 21.37Q822.75-600 810-600H630q-12.75 0-21.37-8.63Q600-617.25 600-630Z"/></svg>
                <span>Check Attendance</span>   
                </div>  
                
                <div class="bell-container">
                <div class="bell">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#FFFFFF"><path d="M160-200v-66.67h80v-296q0-83.66 49.67-149.5Q339.33-778 420-796v-24q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v24q80.67 18 130.33 83.83Q720-646.33 720-562.67v296h80V-200H160Zm320-301.33ZM480-80q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM306.67-266.67h346.66v-296q0-72-50.66-122.66Q552-736 480-736t-122.67 50.67q-50.66 50.66-50.66 122.66v296Z"/></svg>
                </div>
                </div>
                
        </header>
        <div class="cam">
        <?php
        // Fetch the latest zone person name from the zone_persons table
        $sql = "SELECT p.name 
        FROM zone_persons p
        JOIN security s ON p.kagawad_id = s.id
        WHERE s.role = 'kagawad'
        ORDER BY p.name";
$result = $conn->query($sql);
$zone_person_name = $result->num_rows > 0 ? $result->fetch_assoc()['name'] : 'DEFAULT NAME';
        ?>
        <h2 id="zonePerson"><?php echo htmlspecialchars($zone_person_name); ?></h2>
        <div class="capture-photo">
            <i class='bx bx-camera' id="openPhotoModal" style="cursor: pointer;"><span>Capture Photo</span></i>
        </div>
        <video id="preview" style="width: 100%; height: 400px;"></video>
    </div>
        
      <!-- Toast 2: For QR Code Scan (Toast Loader) -->
<div id="toast-loader" class="toast-loader">
    <div class="loader"></div>
    <span id="toast-message-loader"></span> 
</div>
<!-- Toast-Bar Container -->
<div id="toast-bar" class="toast-bar">
    <span id="toast-message-bar"></span>
    <div class="progress-line-bar"></div> 
</div>
<!-- Toast-Bar Container -->
<div id="toast-bar" class="toast-bar">
    <span id="toast-message-bar"></span>
    <button class="close-btn" onclick="hideToastBar()">&times;</button>
    <div class="progress-line-bar"></div> <!-- Progress line at the bottom -->
</div>
</div>
    </div>
  
 <!-- Modal for Capturing & Saving Image -->
 <div id="photoModal" class="modal">
    <div class="modal-content">
        <span class="m-close" id="closeModal">&times;</span>
        <h2>Capture Resident's Photo</h2>

        <!-- Live Camera (now with flip correction) -->
        <video id="photoPreview" width="100%" height="auto" autoplay></video>

        <!-- Canvas to Display Captured Image -->
        <canvas id="photoCanvas" style="display: none; border: 2px solid #ccc; margin-top: 10px;"></canvas>

        <!-- Buttons -->
        <button id="capturePhoto">📷 Capture</button>
        <button id="savePhoto" style="display: none;">💾 Save Image</button>
        <button id="retakePhoto" style="display: none;">🔄 Retake</button>
    </div>
</div>

<!-- Hidden Canvas for Processing the Image -->
<canvas id="photoCanvas" style="display: none;"></canvas>
   </main>

<script>
    let scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
    let photoStream = null;
    let lastCapturedImage = null;
    let scannedResidents = []; // Array to store all scanned resident IDs

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

    // Function to fetch existing scanned residents from the database
    function fetchScannedResidents() {
        fetch('fetch_scanned_residents.php') // PHP script to fetch resident IDs
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok: " + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    scannedResidents = data.residentIds; // Populate the array with existing resident IDs
                    console.log("Fetched scanned residents:", scannedResidents);
                } else {
                    console.error("Error fetching scanned residents:", data.message);
                }
            })
            .catch(error => {
                console.error("Error fetching scanned residents:", error);
                alert("An error occurred while fetching scanned residents. Check the console for details.");
            });
    }

    // Call the function when the page loads
    window.onload = function () {
        fetchScannedResidents();
    };

    // Function to add a resident to the scanned list
    function addScannedResident(residentId) {
        if (!scannedResidents.includes(residentId)) {
            scannedResidents.push(residentId);
            console.log("Added resident to scanned list:", residentId);
        }
    }

  // 📸 Open Modal & Start Camera (with validation)
// Open Photo Modal
document.getElementById('openPhotoModal').addEventListener('click', function() {
        if (scannedResidents.length === 0) {
            showToastBar("⚠️ No resident found! Please scan a QR code first.", 3000, "#FF5722");
            return;
        }

        // Pause the QR scanner
        if (scanner && activeCamera) {
            scanner.stop();
        }

        let modal = document.getElementById('photoModal');
        modal.style.display = "flex"; // Changed to flex for centering

        // Start the photo camera
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(function(stream) {
                photoStream = stream;
                document.getElementById('photoPreview').srcObject = stream;
            })
            .catch(error => {
                console.error("Error accessing camera:", error);
                alert("Failed to access camera. Please ensure camera permissions are granted.");
                closePhotoModal();
            });
    });

    // Close Photo Modal
    function closePhotoModal() {
        const modal = document.getElementById('photoModal');
        modal.style.display = "none";

        // Stop the photo camera
        if (photoStream) {
            photoStream.getTracks().forEach(track => track.stop());
        }

        // Reset modal state
        document.getElementById('photoPreview').style.display = "block";
        document.getElementById('photoCanvas').style.display = "none";
        document.getElementById('capturePhoto').style.display = "block";
        document.getElementById('savePhoto').style.display = "none";

        // Restart the QR scanner
        if (scanner && activeCamera) {
            scanner.start(activeCamera);
        }
    }

    document.getElementById('closeModal').addEventListener('click', closePhotoModal);

    // Initialize the scanner when page loads
    window.addEventListener('load', function() {
        initializeScanner();
        fetchScannedResidents();
    });




  // Function to show the toast-bar
function showToastBar(message, duration = 3000, color = "#4CAF50") { // Default color is green
    const toastBar = document.getElementById('toast-bar');
    const toastMessage = document.getElementById('toast-message-bar');
    const progressLine = toastBar.querySelector('.progress-line-bar');

    if (!toastBar || !toastMessage || !progressLine) {
        console.error("Toast-bar elements not found!");
        return;
    }

    // Set the progress line color
    progressLine.style.backgroundColor = color;

    // Reset the progress line animation
    progressLine.style.animation = 'none'; // Reset animation
    void progressLine.offsetWidth; // Trigger reflow to restart animation
    progressLine.style.animation = 'loading 3s linear forwards'; // Restart animation

    // Set the message and show the toast-bar
    toastMessage.textContent = message;
    toastBar.classList.add('show');

    // Automatically hide the toast-bar after the specified duration
    setTimeout(() => {
        hideToastBar();
    }, duration);
}

// Function to hide the toast-bar
function hideToastBar() {
    const toastBar = document.getElementById('toast-bar');
    if (toastBar) {
        toastBar.classList.remove('show');
    }
}
    // 📷 Capture Photo
    document.getElementById('capturePhoto').addEventListener('click', function() {
        let canvas = document.getElementById('photoCanvas');
        let context = canvas.getContext('2d');
        let video = document.getElementById('photoPreview');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Flip the context before drawing to correct the mirror effect
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Reset the transformation
        context.setTransform(1, 0, 0, 1, 0, 0);

        lastCapturedImage = canvas.toDataURL('image/jpeg', 0.8);

        video.style.display = "none";
        canvas.style.display = "block";

        document.getElementById('capturePhoto').style.display = "none";
        document.getElementById('savePhoto').style.display = "block";
        document.getElementById('retakePhoto').style.display = "block";
    });

    // Add retake functionality
    document.getElementById('retakePhoto').addEventListener('click', function() {
        document.getElementById('photoPreview').style.display = "block";
        document.getElementById('photoCanvas').style.display = "none";
        document.getElementById('capturePhoto').style.display = "block";
        document.getElementById('savePhoto').style.display = "none";
        document.getElementById('retakePhoto').style.display = "none";
    });

    // 💾 Save Photo
    // Example: Show Toast Bar (Save Image)
document.getElementById('savePhoto').addEventListener('click', function () {
    if (!lastCapturedImage) {
        showToastBar("❌ No image captured!", 5000, "#FF5722"); // Red progress line
        return;
    }

    if (scannedResidents.length === 0) {
        showToastBar("⚠️ No residents have been scanned yet!", 3000, "#FF5722"); // Red progress line
        return;
    }

    fetch('save_photo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            image: lastCapturedImage, 
            residentIds: scannedResidents // Send all scanned resident IDs
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Network response was not ok: " + response.statusText);
        }
        return response.blob(); // Get the image as a Blob
    })
    .then(blob => {
        // Create a download link for the image
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'resident_photo.jpg'; // Set the filename
        document.body.appendChild(a);
        a.click(); // Trigger the download
        document.body.removeChild(a); // Clean up
        window.URL.revokeObjectURL(url); // Free up memory

        showToastBar("✅ Image successfully saved and downloaded!", 3000, "#4CAF50"); // Green progress line

        // Close modal
        document.getElementById('photoModal').style.display = "none";
    })
    .catch(error => {
        console.error("❌ Error:", error);
        showToastBar("❌ An error occurred while saving the photo. Check the console for details.", 3000, "#FF5722"); // Red progress line
    });
});
// Function to show Toast Loader (QR Code Scan)
function showToastLoader(message, duration = 3000) {
    const toast = document.getElementById('toast-loader');
    const toastMessage = document.getElementById('toast-message-loader');

    toastMessage.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        hideToastLoader();
    }, duration);
}

// Function to hide Toast Loader (QR Code Scan)
function hideToastLoader() {
    const toast = document.getElementById('toast-loader');
    toast.classList.remove('show');
}   

// Update the QR code scan listener to use the toast loader
scanner.addListener('scan', function (content) {
    const eventType = localStorage.getItem('selectedEvent');
    const zonePerson = document.querySelector('h2').textContent;

    fetch('attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ 
            qrCodeData: content,
            eventType: eventType,
            zonePerson: zonePerson
        })
    }).then(response => response.json())
      .then(data => {
          if (data.status === 'success') {
            showToastLoader(data.message, 2000); // Show success message for 1.5 seconds
              addScannedResident(content);
          } else {
            showToastLoader('Error: ' + data.message, 1500); // Show error message for 1.5 seconds
          }
      });
});
// Initialize the scanner when page loads
function initializeScanner() {
        scanner = new Instascan.Scanner({ video: document.getElementById('preview') });
        
        scanner.addListener('scan', function(content) {
            const eventType = localStorage.getItem('selectedEvent');
            const zonePerson = document.querySelector('h2').textContent;

            fetch('attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 
                    qrCodeData: content,
                    eventType: eventType,
                    zonePerson: zonePerson
                })
            }).then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      showToastLoader(data.message, 2000);
                      addScannedResident(content);
                  } else {
                      showToastLoader('Error: ' + data.message, 1500);
                  }
              });
        });
         // Start the camera
         Instascan.Camera.getCameras().then(function(cameras) {
            if (cameras.length > 0) {
                activeCamera = cameras[0];
                scanner.start(activeCamera);
            } else {
                console.error('No cameras found.');
            }
        }).catch(function(e) {
            console.error(e);
        });
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