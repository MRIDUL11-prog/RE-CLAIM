
<?php

require_once __DIR__ . "/session.php";

// Check whether user is logged in
function checkLogin() {

    if (!isset($_SESSION["user_id"])) {

        header("Location: ../login.php");
        exit();

    }
}

// Check whether user is a student
function checkStudent() {

    checkLogin();

    if ($_SESSION["role"] !== "student") {

        header("Location: ../login.php");
        exit();

    }
}

// Check whether user is an admin
function checkAdmin() {

    checkLogin();

    if ($_SESSION["role"] !== "admin") {

        header("Location: ../login.php");
        exit();

    }
}

?>
