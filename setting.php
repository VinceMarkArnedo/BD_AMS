<?php
session_start();
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Keep existing admin name handling
$sql = "SELECT id, name FROM admin_names ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);
$saved_name = '';
$saved_id = '';
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $saved_name = $row['name'];
    $saved_id = $row['id'];
}

// Add separate zone person handling
$sql = "SELECT id, name FROM zone_persons ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);
$saved_zone_person = '';
$saved_zone_person_id = '';
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $saved_zone_person = $row['name'];
    $saved_zone_person_id = $row['id'];
}

// Handle Admin Name Submission (unchanged)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_name'])) {
    $admin_name = $conn->real_escape_string($_POST['admin_name'] ?? '');

    if (empty($admin_name)) {
        die(json_encode(['status' => 'error', 'message' => 'Admin name is required.']));
    }

    if (isset($_POST['edit_id'])) {
        $edit_id = $conn->real_escape_string($_POST['edit_id'] ?? '');
        $sql = "UPDATE admin_names SET name = '$admin_name' WHERE id = '$edit_id'";
    } else {
        $sql = "INSERT INTO admin_names (name) VALUES ('$admin_name')";
    }

    if ($conn->query($sql)) {
        die(json_encode([
            'status' => 'success',
            'message' => 'Admin name saved successfully!',
            'saved_name' => $admin_name,
            'edit_id' => isset($edit_id) ? $edit_id : $conn->insert_id
        ]));
    } else {
        die(json_encode(['status' => 'error', 'message' => 'Error saving admin name: ' . $conn->error]));
    }
}

// Handle Zone Person Name Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zone_person_name'])) {
    $zone_person_name = $conn->real_escape_string($_POST['zone_person_name'] ?? '');

    if (empty($zone_person_name)) {
        die(json_encode(['status' => 'error', 'message' => 'Zone person name is required.']));
    }

    if (isset($_POST['zone_person_edit_id']) && !empty($_POST['zone_person_edit_id'])) {
        $edit_id = $conn->real_escape_string($_POST['zone_person_edit_id']);
        $sql = "UPDATE zone_persons SET name = '$zone_person_name' WHERE id = '$edit_id'";
    } else {
        $sql = "INSERT INTO zone_persons (name) VALUES ('$zone_person_name')";
    }

    if ($conn->query($sql)) {
        die(json_encode([
            'status' => 'success',
            'message' => 'Zone person name saved successfully!',
            'saved_name' => $zone_person_name,
            'edit_id' => isset($edit_id) ? $edit_id : $conn->insert_id
        ]));
    } else {
        die(json_encode(['status' => 'error', 'message' => 'Error saving zone person name: ' . $conn->error]));
    }
}
// Handle Password Change Submission (existing code)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $current_password = $conn->real_escape_string($_POST['current_password'] ?? '');
    $new_password = $conn->real_escape_string($_POST['new_password'] ?? '');
    $confirm_password = $conn->real_escape_string($_POST['confirm_password'] ?? '');

    if (empty($username) || empty($current_password) || empty($new_password) || empty($confirm_password)) {
        die(json_encode(['status' => 'error', 'message' => 'All fields are required.']));
    }

    if (strlen($new_password) < 5) {
        die(json_encode(['status' => 'error', 'message' => 'New password is too weak (less than 5 characters).']));
    }

    $sql = "SELECT * FROM security WHERE username = '$username' AND password = '$current_password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        if ($new_password === $confirm_password) {
            $update_sql = "UPDATE security SET password = '$new_password' WHERE username = '$username'";
            if ($conn->query($update_sql)) {
                die(json_encode(['status' => 'success', 'message' => 'Password updated successfully!']));
            } else {
                die(json_encode(['status' => 'error', 'message' => 'Error updating password: ' . $conn->error]));
            }
        } else {
            die(json_encode(['status' => 'error', 'message' => 'New password and confirm password do not match.']));
        }
    } else {
        die(json_encode(['status' => 'error', 'message' => 'Invalid username or current password.']));
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="css/setings.css">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@500&display=swap" rel="stylesheet">
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
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M480-400q33 0 56.5-23.5T560-480q0-33-23.5-56.5T480-560q-33 0-56.5 23.5T400-480q0 33 23.5 56.5T480-400ZM320-240h320v-23q0-24-13-44t-36-30q-26-11-53.5-17t-57.5-6q-30 0-57.5 6T369-337q-23 10-36 30t-13 44v23ZM720-80H240q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h287q16 0 30.5 6t25.5 17l194 194q11 11 17 25.5t6 30.5v447q0 33-23.5 56.5T720-80Zm0-80v-446L526-800H240v640h480Zm-480 0v-640 640Z"/></svg>
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
            <li>
                <a href="changeName.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M480-240Zm-320 40v-72q0-34 17.5-62.5T224-378q62-31 126-46.5T480-440q37 0 73 4.5t72 14.5l-67 68q-20-3-39-5t-39-2q-56 0-111 13.5T260-306q-9 5-14.5 14t-5.5 20v32h240v80H200q-17 0-28.5-11.5T160-200Zm400 40v-50q0-16 6.5-30.5T584-266l197-197q9-9 20-13t22-4q12 0 23 4.5t20 13.5l37 37q8 9 12.5 20t4.5 22q0 11-4 22.5T903-340L706-143q-11 11-25.5 17t-30.5 6h-50q-17 0-28.5-11.5T560-160Zm300-223-37-37 37 37ZM620-180h38l121-122-18-19-19-18-122 121v38Zm141-141-19-18 37 37-18-19ZM480-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47Zm0-80q33 0 56.5-23.5T560-640q0-33-23.5-56.5T480-720q-33 0-56.5 23.5T400-640q0 33 23.5 56.5T480-560Zm0-80Z"/></svg>
                <span>Manage Names</span>
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
     <!-- Main Content -->
    
     <div class="main-content">
     <header id="header">
        <div class="logo-pie">
        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M421-80q-14 0-25-9t-13-23l-15-94q-19-7-40-19t-37-25l-86 40q-14 6-28 1.5T155-226L97-330q-8-13-4.5-27t15.5-23l80-59q-2-9-2.5-20.5T185-480q0-9 .5-20.5T188-521l-80-59q-12-9-15.5-23t4.5-27l58-104q8-13 22-17.5t28 1.5l86 40q16-13 37-25t40-18l15-95q2-14 13-23t25-9h118q14 0 25 9t13 23l15 94q19 7 40.5 18.5T669-710l86-40q14-6 27.5-1.5T804-734l59 104q8 13 4.5 27.5T852-580l-80 57q2 10 2.5 21.5t.5 21.5q0 10-.5 21t-2.5 21l80 58q12 8 15.5 22.5T863-330l-58 104q-8 13-22 17.5t-28-1.5l-86-40q-16 13-36.5 25.5T592-206l-15 94q-2 14-13 23t-25 9H421Zm15-60h88l14-112q33-8 62.5-25t53.5-41l106 46 40-72-94-69q4-17 6.5-33.5T715-480q0-17-2-33.5t-7-33.5l94-69-40-72-106 46q-23-26-52-43.5T538-708l-14-112h-88l-14 112q-34 7-63.5 24T306-642l-106-46-40 72 94 69q-4 17-6.5 33.5T245-480q0 17 2.5 33.5T254-413l-94 69 40 72 106-46q24 24 53.5 41t62.5 25l14 112Zm44-210q54 0 92-38t38-92q0-54-38-92t-92-38q-54 0-92 38t-38 92q0 54 38 92t92 38Zm0-130Z"/></svg>
                <span>Settings</span>   
                </div>  
                <h2>
                <div class="bell">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="#FFFFFF"><path d="M160-200v-66.67h80v-296q0-83.66 49.67-149.5Q339.33-778 420-796v-24q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v24q80.67 18 130.33 83.83Q720-646.33 720-562.67v296h80V-200H160Zm320-301.33ZM480-80q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM306.67-266.67h346.66v-296q0-72-50.66-122.66Q552-736 480-736t-122.67 50.67q-50.66 50.66-50.66 122.66v296Z"/></svg>
                </div>
               
            
                </h2>
          
            
        </header>
        <div class="User-account" id="UserAccount" style="cursor: pointer;">
    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#434343">
        <path d="M400-480q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM80-160v-112q0-33 17-62t47-44q51-26 115-44t141-18h14q6 0 12 2-8 18-13.5 37.5T404-360h-4q-71 0-127.5 18T180-306q-9 5-14.5 14t-5.5 20v32h252q6 21 16 41.5t22 38.5H80Zm560 40-12-60q-12-5-22.5-10.5T584-204l-58 18-40-68 46-40q-2-14-2-26t2-26l-46-40 40-68 58 18q11-8 21.5-13.5T628-460l12-60h80l12 60q12 5 22.5 11t21.5 15l58-20 40 70-46 40q2 12 2 25t-2 25l46 40-40 68-58-18q-11 8-21.5 13.5T732-180l-12 60h-80Zm40-120q33 0 56.5-23.5T760-320q0-33-23.5-56.5T680-400q-33 0-56.5 23.5T600-320q0 33 23.5 56.5T680-240ZM400-560q33 0 56.5-23.5T480-640q0-33-23.5-56.5T400-720q-33 0-56.5 23.5T320-640q0 33 23.5 56.5T400-560Zm0-80Zm12 400Z"/>
    </svg>
    <span>Manage User Account</span>
</div>
        <div class="dashboard">
            <div class="events">
                <div class="grid-inside" id="eventGrid">
                    <div class="g1">
                    <div class="name-body">
    <h2 class="name">Manage Name</h2> 
    <div class="admin-name" id="admin-btn">
        Admin
    </div>
    <div class="user-name" id="user-btn">
        User
    </div> 
</div>
<div class="modal-name" id="modalName">
    <form class="form-modal" id="adminNameForm">
        <div class="close-modal" id="closeModal">
            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#000000">
                <path d="M480-429 316-265q-11 11-25 10.5T266-266q-11-11-11-25.5t11-25.5l163-163-164-164q-11-11-10.5-25.5T266-695q11-11 25.5-11t25.5 11l163 164 164-164q11-11 25.5-11t25.5 11q11 11 11 25.5T695-644L531-480l164 164q11 11 11 25t-11 25q-11 11-25.5 11T644-266L480-429Z"/>
            </svg>
        </div>
        <h2>Add Name</h2>
        <div class="input-box">
            <input type="text" name="admin_name" placeholder="Admin Name" required>                                    
            <input type="hidden" name="edit_id" id="editId" value="<?php echo $saved_id; ?>">
        </div>
        <div class="name-btn">
            <div class="edtandsave-btn">
                <button type="submit" class="save-name" id="Savename">
                    <div class="icon-text-container">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                            <path d="M216-144q-29.7 0-50.85-21.15Q144-186.3 144-216v-528q0-29.7 21.15-50.85Q186.3-816 216-816h426q14.22 0 27.11 5Q682-806 693-795l102 102q11 11 16 23.89t5 27.11v426q0 29.7-21.15 50.85Q773.7-144 744-144H216Zm528-498L642-744H216v528h528v-426ZM480-252q45 0 76.5-31.5T588-360q0-45-31.5-76.5T480-468q-45 0-76.5 31.5T372-360q0 45 31.5 76.5T480-252ZM300-552h264q15.3 0 25.65-10.32Q600-572.65 600-587.91v-71.83q0-15.26-10.35-25.76Q579.3-696 564-696H300q-15.3 0-25.65 10.32Q264-675.35 264-660.09v71.83q0 15.26 10.35 25.76Q284.7-552 300-552Zm-84-77v413-528 115Z"/>
                        </svg>
                        <span class="save-span">Save</span>
                    </div>
                </button>
            </div>
        </div>
    </form>
    <div id="savedNameBox" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; <?php echo empty($saved_name) ? 'display: none;' : ''; ?>">
        <strong>Saved Name:</strong>
        <span id="savedName"><?php echo htmlspecialchars($saved_name); ?></span>
        <button id="editNameBtn" style="margin-left: 10px;">Edit</button>
    </div>
</div>

<!-- Add separate zone person modal -->
<div class="modal-name" id="modalUser">
    <form class="form-modal" id="zonePersonForm">
        <div class="close-modal" id="closeZonePersonModal">
            <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#000000">
                <path d="M480-429 316-265q-11 11-25 10.5T266-266q-11-11-11-25.5t11-25.5l163-163-164-164q-11-11-10.5-25.5T266-695q11-11 25.5-11t25.5 11l163 164 164-164q11-11 25.5-11t25.5 11q11 11 11 25.5T695-644L531-480l164 164q11 11 11 25t-11 25q-11 11-25.5 11T644-266L480-429Z"/>
            </svg>
        </div>
        <h2>Change Zone Person Name</h2>
        <div class="input-box">
            <input type="text" name="zone_person_name" placeholder="Zone Person Name" required>
            <input type="hidden" name="zone_person_edit_id" id="zonePersonEditId" value="">
        </div>
        <div class="name-btn">
            <div class="edtandsave-btn">
                <button type="submit" class="save-name" id="SaveZonePersonName">
                    <div class="icon-text-container">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                            <path d="M216-144q-29.7 0-50.85-21.15Q144-186.3 144-216v-528q0-29.7 21.15-50.85Q186.3-816 216-816h426q14.22 0 27.11 5Q682-806 693-795l102 102q11 11 16 23.89t5 27.11v426q0 29.7-21.15 50.85Q773.7-144 744-144H216Zm528-498L642-744H216v528h528v-426ZM480-252q45 0 76.5-31.5T588-360q0-45-31.5-76.5T480-468q-45 0-76.5 31.5T372-360q0 45 31.5 76.5T480-252ZM300-552h264q15.3 0 25.65-10.32Q600-572.65 600-587.91v-71.83q0-15.26-10.35-25.76Q579.3-696 564-696H300q-15.3 0-25.65 10.32Q264-675.35 264-660.09v71.83q0 15.26 10.35 25.76Q284.7-552 300-552Zm-84-77v413-528 115Z"/>
                        </svg>
                        <span class="save-span">Save</span>
                    </div>
                </button>
            </div>
        </div>
    </form>
    <div id="savedZonePersonBox" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc;">
        <strong>Saved Zone Person:</strong>
        <span id="savedZonePersonName"><?php echo htmlspecialchars($saved_zone_person ?? 'FERNANDO GAZMEN'); ?></span>
        <button id="editZonePersonNameBtn" style="margin-left: 10px;">Edit</button>
    </div>
</div>
                        <div>
                                 <!-- Color Picker Container -->
                        <div class="color-picker-container">
                            <label for="headerColorPicker">Choose Header Color:</label>
                            <input type="color" id="headerColorPicker" name="headerColorPicker" value="#000000">
                        </div>
                        <div class="color-picker-container">
                            <label for="sidebarColorPicker">Choose Sidebar Color:</label>
                            <input type="color" id="sidebarColorPicker" name="sidebarColorPicker" value="#000000">
                        </div>
                            </div>
                            <!-- Change Password Form -->
                            <div class="change-password-form">
                                <h2>Change Password</h2>
                                <form id="change-password-form">
                                    <div class="input-box">
                                        <input type="text" name="username" placeholder="User Name" required>
                                    </div>
                                    <div class="input-box">
                                        <input type="password" name="current_password" placeholder="Current Password" required>
                                        <span class="eye-icon" onclick="togglePassword('current_password')">&#128065;</span>
                                    </div>
                                    <div class="input-box">
                                        <input type="password" name="new_password" placeholder="New Password" required>
                                        <div class="strength-indicator"><span></span></div>
                                        <span class="eye-icon" onclick="togglePassword('new_password')">&#128065;</span>
                                    </div>
                                    <div class="input-box">
                                        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                                        <span class="eye-icon" onclick="togglePassword('confirm_password')">&#128065;</span>
                                    </div>
                                    <button type="button" id="change-password-btn" class="btn">Change Password</button>
                                    <div id="feedback"></div>
                                </form>
                            </div>

                        </div>
                    </div>
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
// manage user
document.getElementById('UserAccount').addEventListener('click', () => {
    window.location.href = 'manage_users.php';
});
// end

       // Existing admin modal functionality
const adminBtn = document.getElementById('admin-btn');
const modal = document.getElementById('modalName');
const closeModalBtn = document.getElementById('closeModal');

adminBtn.addEventListener('click', () => {
    modal.style.display = 'block';
});

closeModalBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

window.addEventListener('click', (event) => {
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

document.getElementById('adminNameForm').addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    const feedback = document.getElementById('feedback');
    const savedNameBox = document.getElementById('savedNameBox');
    const savedNameText = document.getElementById('savedName');
    const editIdInput = document.getElementById('editId');

    fetch('', {
        method: 'POST',
        body: formData,
    })
    .then((response) => response.json())
    .then((data) => {
        if (data.status === 'error') {
            feedback.style.color = 'red';
            feedback.textContent = data.message;
        } else {
            feedback.style.color = 'green';
            feedback.textContent = data.message;
            savedNameText.textContent = data.saved_name;
            savedNameBox.style.display = 'block';
            editIdInput.value = data.edit_id;
        }
    })
    .catch(() => {
        feedback.textContent = 'An error occurred. Please try again.';
    });
});

document.getElementById('editNameBtn').addEventListener('click', function () {
    const savedNameText = document.getElementById('savedName');
    const adminNameInput = document.querySelector('input[name="admin_name"]');
    adminNameInput.value = savedNameText.textContent;
    modal.style.display = 'block';
    adminNameInput.focus();
});

// New zone person modal functionality
const userBtn = document.getElementById('user-btn');
const zonePersonModal = document.getElementById('modalUser');
const closeZonePersonModal = document.getElementById('closeZonePersonModal');

userBtn.addEventListener('click', () => {
    zonePersonModal.style.display = 'block';
});

closeZonePersonModal.addEventListener('click', () => {
    zonePersonModal.style.display = 'none';
});

window.addEventListener('click', (event) => {
    if (event.target === zonePersonModal) {
        zonePersonModal.style.display = 'none';
    }
});

document.getElementById('zonePersonForm').addEventListener('submit', function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    const feedback = document.getElementById('feedback');
    const savedZonePersonBox = document.getElementById('savedZonePersonBox');
    const savedZonePersonText = document.getElementById('savedZonePersonName');
    const zonePersonEditIdInput = document.getElementById('zonePersonEditId');

    fetch('', {
        method: 'POST',
        body: formData,
    })
    .then((response) => response.json())
    .then((data) => {
        if (data.status === 'error') {
            feedback.style.color = 'red';
            feedback.textContent = data.message;
        } else {
            feedback.style.color = 'green';
            feedback.textContent = data.message;
            savedZonePersonText.textContent = data.saved_name;
            savedZonePersonBox.style.display = 'block';
            zonePersonEditIdInput.value = data.edit_id;
        }
    })
    .catch(() => {
        feedback.textContent = 'An error occurred. Please try again.';
    });
});

document.getElementById('editZonePersonNameBtn').addEventListener('click', function () {
    const savedZonePersonText = document.getElementById('savedZonePersonName');
    const zonePersonNameInput = document.querySelector('input[name="zone_person_name"]');
    zonePersonNameInput.value = savedZonePersonText.textContent;
    zonePersonModal.style.display = 'block';
    zonePersonNameInput.focus();
});
        // Function to toggle password visibility
        function togglePassword(field) {
            const passwordField = document.querySelector(`[name="${field}"]`);
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
        }

        // Password strength check and form submission
        document.getElementById('change-password-btn').addEventListener('click', function () {
            const form = document.getElementById('change-password-form');
            const feedback = document.getElementById('feedback');
            const formData = new FormData(form);

            const newPassword = formData.get('new_password');
            const strengthIndicator = document.querySelector('.strength-indicator span');

            if (newPassword.length <= 5) {
                strengthIndicator.textContent = 'Password Strength: Weak';
                strengthIndicator.style.color = 'red';
                return;
            }

            const confirmation = confirm('Are you sure you want to change your password?');
            if (!confirmation) return;

            fetch('', {
                method: 'POST',
                body: formData,
            })
            .then((response) => response.json())
            .then((data) => {
                if (data.status === 'error') {
                    feedback.style.color = 'red';
                    feedback.textContent = data.message;
                } else {
                    feedback.style.color = 'green';
                    feedback.innerHTML = `
                        ${data.message}
                        <button id="ok-button" class="btn">Ok</button>
                    `;
                    document.getElementById('ok-button').addEventListener('click', function (event) {
                        event.preventDefault();
                        window.location.href = 'login.php';
                    });
                }
            })
            .catch(() => {
                feedback.textContent = 'An error occurred. Please try again.';
            });
        });
    </script>
       <script>
  window.addEventListener('load', function() {
      let savedColor = localStorage.getItem('headerSidebarColor');
      if (savedColor) {
          document.querySelector('header').style.backgroundColor = savedColor;
          document.querySelector('#sidebar').style.backgroundColor = savedColor;
      }
  });

  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

window.addEventListener('load', function() {
    const savedHeaderColor = getCookie('headerColor');
    const savedSidebarColor = getCookie('sidebarColor');

    if (savedHeaderColor) {
        document.querySelector('header').style.backgroundColor = savedHeaderColor;
    }
    if (savedSidebarColor) {
        document.querySelector('#sidebar').style.backgroundColor = savedSidebarColor;
    }
});
</script>

<script>
  // Apply saved colors when page loads
  window.addEventListener('load', function() {
      let savedHeaderColor = localStorage.getItem('headerColor');
      let savedSidebarColor = localStorage.getItem('sidebarColor');

      if (savedHeaderColor) {
          document.querySelector('header').style.backgroundColor = savedHeaderColor;
          document.getElementById('headerColorPicker').value = savedHeaderColor;
      }
      if (savedSidebarColor) {
          // Update the sidebar color using the class .sidebar
          document.querySelector('#sidebar').style.backgroundColor = savedSidebarColor;
          document.getElementById('sidebarColorPicker').value = savedSidebarColor;
      }
  });

  // Update header color on input and save it to local storage
  document.getElementById('headerColorPicker').addEventListener('input', function() {
      let color = this.value;
      document.querySelector('header').style.backgroundColor = color;
      localStorage.setItem('headerColor', color); // Save to local storage
  });

  // Update sidebar color on input and save it to local storage
  document.getElementById('sidebarColorPicker').addEventListener('input', function() {
      let color = this.value;
      document.querySelector('#sidebar').style.backgroundColor = color;
      localStorage.setItem('sidebarColor', color); // Save to local storage
  });
</script>

</body>
</html>
