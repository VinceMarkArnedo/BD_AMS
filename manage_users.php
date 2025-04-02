<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    header('Content-Type: application/json');
    
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $zone_person_name = filter_input(INPUT_POST, 'zone_person_name', FILTER_SANITIZE_STRING);
    
    // Validate inputs
    if (empty($username) || empty($password) || empty($zone_person_name)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        exit;
    }

    if (strlen($password) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
        exit;
    }

    // Check if username already exists
    $stmt = $conn->prepare("SELECT username FROM security WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Hash password and set role as 'kagawad'
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'kagawad';
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into security table
        $stmt = $conn->prepare("INSERT INTO security (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);
        $stmt->execute();
        $kagawad_id = $stmt->insert_id;
        $stmt->close();

        // Insert into zone_persons table
        $stmt = $conn->prepare("INSERT INTO zone_persons (name, kagawad_id) VALUES (?, ?)");
        $stmt->bind_param("si", $zone_person_name, $kagawad_id);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Kagawad account and zone person created successfully',
            'zone_person_name' => $zone_person_name
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error creating kagawad account: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error creating account']);
    }
    exit;
}

// Fetch existing zone persons for reference
$zone_persons = [];
$result = $conn->query("SELECT name FROM zone_persons ORDER BY name");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $zone_persons[] = $row['name'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Keep existing head content -->
    <style>
        .zone-person-container {
            margin-bottom: 15px;
        }
        .existing-zone-persons {
            margin-top: 10px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .existing-zone-persons h4 {
            margin-top: 0;
        }
        .zone-person-list {
            list-style-type: none;
            padding-left: 0;
        }
        .zone-person-list li {
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <!-- Keep existing sidebar and header -->

    <div class="manage-users-form">
        <h2>Add Kagawad Account</h2>
        <form id="add-user-form">
            <div class="input-box">
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <span class="eye-icon" onclick="togglePassword('password')">👁</span>
            </div>
            <div class="zone-person-container">
                <input type="text" name="zone_person_name" placeholder="Zone Person Name" required>
                <small>This will be the name displayed in attendance records</small>
            </div>
            
            <!-- Display existing zone persons for reference -->
            <div class="existing-zone-persons">
                <h4>Existing Zone Persons:</h4>
                <?php if (!empty($zone_persons)): ?>
                    <ul class="zone-person-list">
                        <?php foreach ($zone_persons as $person): ?>
                            <li><?php echo htmlspecialchars($person); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No zone persons currently registered</p>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="btn">Add Kagawad</button>
            <div id="feedback"></div>
        </form>
    </div>

    <script>
        // Update form submission to include zone person name
        document.getElementById('add-user-form').addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const feedback = document.getElementById('feedback');

            fetch('manage_users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                feedback.style.color = data.status === 'error' ? 'red' : 'green';
                feedback.textContent = data.message;
                if (data.status === 'success') {
                    e.target.reset();
                    // Refresh the zone persons list after successful addition
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(() => {
                feedback.style.color = 'red';
                feedback.textContent = 'An error occurred. Please try again.';
            });
        });

        // Password visibility toggle
        function togglePassword(field) {
            const input = document.querySelector(`[name="${field}"]`);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>