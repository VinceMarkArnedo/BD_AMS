<?php
// config.php

// Database configuration
define('DB_HOST', 'localhost');    // Database host
define('DB_USER', 'root');         // Database username
define('DB_PASS', '');             // Database password (empty for default local setup)
define('DB_NAME', 'bdams_db');     // Database name

// Prevent direct access to this file
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Direct access not allowed');
}
?>