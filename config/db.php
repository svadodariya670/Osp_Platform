<?php
// Database credentials
$host = "localhost";
$db_name = "osp_platform";
$username = "root";
$password = "";

// Options for PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES => false, // Disable emulation
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password, $options);
} catch (PDOException $e) {
    // Display error and stop execution
    die("Database Connection Failed: " . $e->getMessage());
}
?>
