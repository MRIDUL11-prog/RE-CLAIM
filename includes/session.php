<?php

if (session_status() === PHP_SESSION_NONE) {
    $session_lifetime = 31536000;
    $script_directory = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
    $session_path = preg_replace("~/(student|admin)$~", "", $script_directory);
    $session_path = rtrim($session_path, "/") . "/";

    ini_set("session.gc_maxlifetime", (string) $session_lifetime);
    session_set_cookie_params([
        "lifetime" => $session_lifetime,
        "path" => $session_path,
        "secure" => !empty($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();

    setcookie(session_name(), session_id(), [
        "expires" => time() + $session_lifetime,
        "path" => $session_path,
        "secure" => !empty($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}

?>
