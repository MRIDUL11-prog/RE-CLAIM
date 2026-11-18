
<?php

require_once __DIR__ . "/session.php";

// Base URL Configuration
$script_directory = str_replace(
    "\\",
    "/",
    dirname($_SERVER["SCRIPT_NAME"])
);

$base_url = preg_replace(
    "~/(student|admin)$~",
    "",
    $script_directory
);

$base_url = rtrim($base_url, "/");
$theme_cookie_path = $base_url !== "" ? $base_url : "/";

// Dashboard Page Detection
$is_dashboard = (
    basename($_SERVER["SCRIPT_NAME"]) === "dashboard.php"
);
$is_admin_dashboard = (
    $is_dashboard &&
    strpos(str_replace("\\", "/", $_SERVER["SCRIPT_NAME"]), "/admin/") !== false
);

$saved_theme = $_COOKIE["campus-theme"] ?? "";
$page_theme = in_array($saved_theme, ["light", "dark"], true)
    ? $saved_theme
    : ($is_admin_dashboard ? "dark" : "light");

if (!in_array($saved_theme, ["light", "dark"], true)) {
    setcookie("campus-theme", $page_theme, [
        "expires" => time() + 31536000,
        "path" => $theme_cookie_path,
        "samesite" => "Lax"
    ]);
}

$page_title = $page_title ?? "RE:CLAIM | Campus Lost & Found";
$page_body_classes = [];

if ($is_dashboard) {
    $page_body_classes[] = "is-dashboard-page";
}

if (!empty($page_body_class)) {
    $page_body_classes[] = $page_body_class;
}

?>

<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($page_theme, ENT_QUOTES, 'UTF-8'); ?>" data-theme-cookie-path="<?php echo htmlspecialchars($theme_cookie_path, ENT_QUOTES, 'UTF-8'); ?>">

<head>

    <!-- Basic Meta Information -->

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="<?php echo $page_theme === "dark" ? "#0C0C16" : "#EDE8F8"; ?>"
    >

    <meta
        name="description"
        content="RE:CLAIM - Campus Lost and Found Platform. Find lost belongings and help reunite items with their owners."
    >

    <meta name="application-name" content="RE:CLAIM">

    <meta name="color-scheme" content="<?php echo htmlspecialchars($page_theme, ENT_QUOTES, 'UTF-8'); ?>">

    <link
        rel="icon"
        type="image/png"
        href="<?php echo $base_url; ?>/images/brand/reclaim-icon.png?v=<?php echo filemtime(dirname(__DIR__) . '/images/brand/reclaim-icon.png'); ?>"
    >

    <!-- Page Title -->

    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>


    <!-- Google Fonts -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- Main Project Stylesheet -->

    <link
        rel="stylesheet"
        href="<?php echo $base_url; ?>/css/style.css?v=<?php echo filemtime(dirname(__DIR__) . '/css/style.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo $base_url; ?>/css/navigation.css?v=<?php echo filemtime(dirname(__DIR__) . '/css/navigation.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo $base_url; ?>/css/brand.css?v=<?php echo filemtime(dirname(__DIR__) . '/css/brand.css'); ?>"
    >

    <script
        src="<?php echo $base_url; ?>/js/navigation.js?v=<?php echo filemtime(dirname(__DIR__) . '/js/navigation.js'); ?>"
        defer
    ></script>

    <?php if (!empty($page_stylesheet)) { ?>
        <link
            rel="stylesheet"
            href="<?php echo $base_url; ?>/css/<?php echo htmlspecialchars($page_stylesheet, ENT_QUOTES, 'UTF-8'); ?>?v=<?php echo filemtime(dirname(__DIR__) . '/css/' . basename($page_stylesheet)); ?>"
        >
    <?php } ?>

</head>


<!-- Body -->

<body class="<?php echo htmlspecialchars(implode(' ', $page_body_classes), ENT_QUOTES, 'UTF-8'); ?>">
