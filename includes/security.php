<?php

require_once __DIR__ . "/session.php";

function catchit_csrf_token()
{
    if (empty($_SESSION["catchit_csrf_token"])) {
        $_SESSION["catchit_csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["catchit_csrf_token"];
}

function catchit_valid_csrf_token($token)
{
    return isset($_SESSION["catchit_csrf_token"]) &&
        is_string($token) &&
        hash_equals($_SESSION["catchit_csrf_token"], $token);
}

function catchit_app_key()
{
    $key = getenv("CATCHIT_APP_KEY");

    return $key === false || strlen($key) < 32 ? null : $key;
}
