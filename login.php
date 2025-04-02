
<?php
session_start();
require_once 'config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Retrieve POST data
$username = isset($_POST['username']) ? $_POST['username'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Sanitize input to prevent SQL injection
$username = $conn->real_escape_string($username);
$password = $conn->real_escape_string($password);

// Query to check if the username and password are correct
$sql = "SELECT * FROM security WHERE username = '$username' AND password = '$password'";

$result = $conn->query($sql);



// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>log-in form</title>
  <link rel="stylesheet" href="css/capstonee.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

</head>
<body>

    <div class="login">
    <div class="dalla-login">
      <div class="img-logo">
        <img class="picture-logo" src="img/logo.jpg">
      </div>
      <h1 class="h1">
        BARANGAY DALLA ATTENDANCE MONITORING SYSTEM
      </h1>
      <form action="login.php" method="POST">
        <div class="box">
          <div class="input-box">
            <input type="username" placeholder="Username" class="i-user" name="username" id="username">
            <i class='bx bxs-user'></i>
  
           </div>    
        </div>
  
        <div class="box2">
          <div class="input-box">
            <input type="password" placeholder="Password" class="i-pass" name="password" id="password">
            <i class='bx bxs-lock-alt' ></i>
          </div>
        </div>
          <div class="rem">
            <label class="chuchu"><input type="checkbox">Remember Me</label>
  
          </div>
       
        <div class="botton">
          <button type="button" class="btn" id="sign">Login</button>
        </div>
    </div>
  </div>
    </form>
  

</body>
</html>
<script>
document.getElementById('sign').addEventListener('click', function() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    if (username === '' || password === '') {
        alert('Please fill in both fields.');
        return;
    }

    // Make an AJAX call to the server-side PHP script to handle login
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'login_handler.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    
    // When the server responds
    xhr.onload = function() {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // Redirect to different pages based on user role
                if (response.role === 'admin') {
                    window.location.href = 'dashboard.php'; // Admin dashboard
                } else if (response.role === 'kagawad') {
                    window.location.href = 'attendance.php'; // Redirect to QR Code Scanner
                } else {
                    alert('Unknown user role!');
                }
            } else {
                alert('Invalid username or password!');
            }
        }
    };

    // Send username and password to the server-side script
    xhr.send('username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password));
});
</script>
 <!-- pang color picker atuy -->
 <script>
          // Apply saved color when page loads
  window.addEventListener('load', function() {
      let savedColor = localStorage.getItem('headerSidebarColor');
      if (savedColor) {
          document.querySelector('header').style.backgroundColor = savedColor;
          document.querySelector('.sidebar').style.backgroundColor = savedColor;
          document.querySelector('login').style.backgroundColor = savedColor;
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
