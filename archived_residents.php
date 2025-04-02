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

// Handle unarchive request
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

            // Redirect to residents.php to show the updated list
            header("Location: residents.php?unarchived=success");
            exit();
        } else {
            header("Location: archived_residents.php?unarchived=error");
            exit();
        }
    } else {
        header("Location: archived_residents.php?unarchived=not_found");
        exit();
    }
}

// Fetch archived residents for display
$archivedUsers = [];
$result = $conn->query("SELECT * FROM archived_residents");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $archivedUsers[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>archived</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/arch.css">
    
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
        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M479.82-581q-12.82 0-21.32 8.62-8.5 8.63-8.5 21.38v171l-66-66q-7.82-8-19.91-8T344-446.14q-8 7.85-8 20 0 12.14 8 20.14l115 115q9 9 21 9t21-9l115-115q8-7.82 8-19.91T616.14-446q-7.85-8-20-8-12.14 0-20.14 8l-66 66v-171q0-12.75-8.68-21.38-8.67-8.62-21.5-8.62ZM180-674v494h600v-494H180Zm0 554q-24.75 0-42.37-17.63Q120-155.25 120-180v-529q0-9.88 3-19.06 3-9.18 9-16.94l52-71q8-11 20.94-17.5Q217.88-840 232-840h495q14.12 0 27.06 6.5T775-816l53 71q6 7.76 9 16.94 3 9.18 3 19.06v529q0 24.75-17.62 42.37Q804.75-120 780-120H180Zm17-614h565l-36.41-46H233l-36 46Zm283 307Z"/></svg>
                <span>Archived Residents</span>   
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
        <div class="table-fixed">
        <table id="userTable">
    <thead>
        <tr>
            <th>Name</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Zone</th>
            <th>Phone Number</th>
            <th>QR Code</th>
            <th>Status</th> <!-- Add Status column -->
        </tr>
    </thead>
    <tbody>
        <?php foreach ($archivedUsers as $user): ?>
        <tr>
            <td><?php echo $user['name']; ?></td>
            <td><?php echo $user['age']; ?></td>
            <td><?php echo $user['gender']; ?></td>
            <td><?php echo $user['zone']; ?></td>
            <td><?php echo $user['phone_number']; ?></td>
            <td>
                <img src="<?php echo $user['qr_code_url']; ?>" alt="QR Code">
            </td>
            <td><?php echo $user['status']; ?></td> <!-- Display Status -->
            <td>
            <form method="POST">
                                    <input type="hidden" name="resident_id" value="<?php echo $user['id']; ?>">
                                    <button class="unarchive-button" type="submit">Unarchive</button>
                                </form>
</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
        </div>
    </div>
    </main>
</body>
</html>
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