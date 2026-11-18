
<?php
// Database connection details
// Replace these placeholder values with your own database details.

$host = "YOUR_DATABASE_HOST";
$username = "YOUR_DATABASE_USERNAME";
$password = "YOUR_DATABASE_PASSWORD";
$database = "YOUR_DATABASE_NAME";
$port = 3306;

// Create connection
$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database,
    $port
);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>
