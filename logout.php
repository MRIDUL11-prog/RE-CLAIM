
<?php

require_once __DIR__ . "/includes/session.php";

// Remove all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Expire the persistent session cookie when the user explicitly signs out.
$cookie_params = session_get_cookie_params();
setcookie(session_name(), "", [
    "expires" => time() - 42000,
    "path" => $cookie_params["path"],
    "domain" => $cookie_params["domain"],
    "secure" => $cookie_params["secure"],
    "httponly" => $cookie_params["httponly"],
    "samesite" => $cookie_params["samesite"] ?? "Lax"
]);

// Redirect to project home page
header("Location: index.php");
exit();

?>
