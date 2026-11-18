
<?php

require_once "config/database.php";

echo "Database Connection Successful!";

echo "<br>";

echo "Connected Database: " .
     mysqli_fetch_row(
         mysqli_query($conn, "SELECT DATABASE()")
     )[0];

echo "<br>";

echo "MySQL Server: " .
     mysqli_get_host_info($conn);

echo "<br>";

$result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM items"
);

$row = mysqli_fetch_assoc($result);

echo "Total Items: " . $row["total"];

?>
