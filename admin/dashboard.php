<?php
require_once __DIR__ . "/../includes/session.php";

// Check admin access
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: ../student/dashboard.php");
    exit();
}

require_once "../config/database.php";

// Dashboard statistics
function getCount($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    $data = mysqli_fetch_assoc($result);
    return (int)($data["total"] ?? 0);
}

$total_users = getCount($conn, "SELECT COUNT(*) AS total FROM users");
$total_items = getCount($conn, "SELECT COUNT(*) AS total FROM items");
$total_lost = getCount($conn, "SELECT COUNT(*) AS total FROM items WHERE item_type = 'lost'");
$total_found = getCount($conn, "SELECT COUNT(*) AS total FROM items WHERE item_type = 'found'");
$total_pending = getCount($conn, "SELECT COUNT(*) AS total FROM items WHERE status = 'pending'");
$total_claims = getCount($conn, "SELECT COUNT(*) AS total FROM claims");

// Recent activity (items awaiting review or recently logged)
$recent_admin_sql = "SELECT i.*, u.name AS reporter_name, c.category_name
                     FROM items i
                     LEFT JOIN users u ON i.user_id = u.user_id
                     LEFT JOIN categories c ON i.category_id = c.category_id
                     ORDER BY i.created_at DESC
                     LIMIT 5";
$recent_admin_result = mysqli_query($conn, $recent_admin_sql);

$script_directory = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
$base_url = rtrim(
    preg_replace("~/(student|admin)$~", "", $script_directory),
    "/"
);
$theme_cookie_path = $base_url !== "" ? $base_url : "/";
$saved_theme = $_COOKIE["campus-theme"] ?? "";
$page_theme = in_array($saved_theme, ["light", "dark"], true) ? $saved_theme : "dark";
if (!in_array($saved_theme, ["light", "dark"], true)) {
    setcookie("campus-theme", $page_theme, [
        "expires" => time() + 31536000,
        "path" => $theme_cookie_path,
        "samesite" => "Lax"
    ]);
}
$current_page = basename($_SERVER["SCRIPT_NAME"]);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($page_theme, ENT_QUOTES, 'UTF-8'); ?>" data-theme-cookie-path="<?php echo htmlspecialchars($theme_cookie_path, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?php echo $page_theme === "dark" ? "#0C0C16" : "#f4f6f9"; ?>">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($base_url); ?>/images/brand/reclaim-icon.png">

    <title>Admin Dashboard | re:claim</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://api.fontshare.com/v2/css?f[]=cabinet-grotesk@800,700,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..800;1,9..40,400..800&family=Montserrat:wght@700;800;900&family=Space+Grotesk:wght@500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>/css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>/css/brand.css?v=<?php echo filemtime(__DIR__ . '/../css/brand.css'); ?>">
    <script src="<?php echo htmlspecialchars($base_url); ?>/js/navigation.js?v=<?php echo filemtime(__DIR__ . '/../js/navigation.js'); ?>" defer></script>

    <style>
        /* RE dashboard palette and glass surface tokens */
        :root {
            --catchit-primary: #6666FF;      /* Medium Slate Blue */
            --catchit-secondary: #B8BAFF;    /* Periwinkle */
            --catchit-accent: #C9E8FF;       /* Columbia Blue */
            --catchit-bg: #0C0C16;           /* Rich Black */
            --catchit-success: #B9F0D7;      /* Celadon */
            --catchit-text: #F5F5FF;
            --catchit-text-muted: #B8BAFF;
            --glass-border: rgba(201, 232, 255, 0.16);
            --glass-border-strong: rgba(184, 186, 255, 0.35);
            --radius-md: 12px;
            --radius-lg: 18px;
            --shadow-depth-1: 0 8px 24px rgba(0, 0, 0, 0.35);
            --shadow-depth-2: 0 16px 40px rgba(0, 0, 0, 0.45);
            --shadow-depth-3: 0 24px 60px rgba(0, 0, 0, 0.6);
            --transition-fast: 0.2s ease;
            --transition-smooth: 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            background-color: #0C0C16 !important;
            min-height: 100%;
        }

        .admin-body {
            background-color: #0C0C16 !important;
            background-image:
                radial-gradient(ellipse at 12% 15%, rgba(102, 102, 255, 0.18), transparent 38rem),
                radial-gradient(ellipse at 88% 65%, rgba(185, 240, 215, 0.10), transparent 34rem),
                radial-gradient(ellipse at 50% 95%, rgba(102, 102, 255, 0.12), transparent 32rem),
                #0C0C16 !important;
            background-attachment: fixed !important;
            background-size: cover !important;
            background-repeat: no-repeat !important;
            color: #F5F5FF !important;
            font-family: "DM Sans", -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            padding-bottom: 0 !important;
            margin: 0 !important;
        }

        /* Sidebar Styled Exclusively with CATCHIT Palette */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: rgba(12, 12, 22, 0.94) !important;
            border-right: 1px solid rgba(201, 232, 255, 0.16) !important;
            backdrop-filter: blur(24px) saturate(160%) !important;
            -webkit-backdrop-filter: blur(24px) saturate(160%) !important;
            position: fixed;
            top: 0;
            left: 0;
            padding: 28px 20px;
            color: #F5F5FF;
            z-index: 100;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6);
            animation: dashboardFadeSlideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .sidebar h2 {
            font-family: "Cabinet Grotesk", "Space Grotesk", "Montserrat", sans-serif !important;
            font-size: 1.35rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 36px;
            color: #F5F5FF !important;
            letter-spacing: -0.03em;
            text-shadow: 0 2px 14px rgba(102, 102, 255, 0.35);
        }

        .sidebar h2.admin-brand-lockup {
            font-size: 0.8rem;
            letter-spacing: 0.14em;
            line-height: 1;
            text-shadow: none;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #B8BAFF;
            text-decoration: none;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.92rem;
            border: 1px solid transparent;
            transition: all var(--transition-fast);
        }

        .sidebar a:hover {
            color: #F5F5FF;
            background: rgba(102, 102, 255, 0.16);
            border-color: rgba(184, 186, 255, 0.32);
            transform: translateX(4px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }

        .sidebar a.is-active {
            color: #F5F5FF !important;
            background: linear-gradient(135deg, rgba(102, 102, 255, 0.32), rgba(184, 186, 255, 0.16)) !important;
            border-color: #6666FF !important;
            box-shadow: 0 0 22px rgba(102, 102, 255, 0.35), inset 0 1px 1px rgba(201, 232, 255, 0.3);
        }

        .sidebar .logout {
            margin-top: 36px;
            background: linear-gradient(135deg, #6666FF, #B8BAFF) !important;
            color: #0C0C16 !important;
            font-family: "Cabinet Grotesk", "Space Grotesk", sans-serif;
            font-weight: 800;
            justify-content: center;
            border: 1px solid rgba(201, 232, 255, 0.4);
            box-shadow: 0 6px 20px rgba(102, 102, 255, 0.3);
        }

        .sidebar .logout:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 26px rgba(102, 102, 255, 0.5);
        }

        .main-content {
            margin-left: 260px;
            padding: clamp(24px, 3.5vw, 42px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            flex: 1;
            background: transparent !important;
        }

        .topbar {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            background: rgba(18, 18, 36, 0.75) !important;
            border: 1px solid rgba(201, 232, 255, 0.16) !important;
            border-top: 1px solid rgba(201, 232, 255, 0.28) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            padding: 22px 28px;
            border-radius: var(--radius-lg);
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.24), inset 0 1px 1px rgba(255, 255, 255, 0.08);
            animation: dashboardFadeSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .topbar::after {
            content: "";
            position: absolute;
            z-index: -1;
            top: -110px;
            right: 12%;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(102, 102, 255, 0.22), rgba(201, 232, 255, 0.06) 48%, transparent 72%);
            pointer-events: none;
            animation: dashboardOrbDrift 10s ease-in-out infinite alternate;
        }

        .topbar h1 {
            font-family: "Cabinet Grotesk", "Space Grotesk", sans-serif !important;
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #F5F5FF !important;
        }

        .topbar .welcome {
            color: #C9E8FF;
            font-size: 0.95rem;
            margin-top: 4px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar .welcome strong {
            color: #ffffff;
        }

        .admin-account-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar .theme-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            background: rgba(184, 186, 255, 0.14);
            border: 1px solid rgba(201, 232, 255, 0.25);
            color: #C9E8FF;
            cursor: pointer;
            font-size: 1.15rem;
            transition: all var(--transition-fast);
        }

        .topbar .theme-toggle:hover {
            background: rgba(102, 102, 255, 0.28);
            border-color: #6666FF;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(102, 102, 255, 0.35);
        }

        .system-status {
            display: inline-block;
            flex: 0 0 auto;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--catchit-success, #B9F0D7);
            box-shadow: 0 0 0 4px rgba(185, 240, 215, 0.12), 0 0 14px rgba(185, 240, 215, 0.75);
            animation: dashboardStatusPulse 2.4s ease-in-out infinite;
        }

        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(184, 186, 255, 0.12);
            border: 1px solid rgba(201, 232, 255, 0.2);
            color: #F5F5FF;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.92rem;
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .btn-secondary:hover {
            background: rgba(102, 102, 255, 0.22);
            border-color: #6666FF;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #6666FF 0%, #5050df 100%);
            color: #ffffff;
            border: 1px solid rgba(201, 232, 255, 0.4);
            border-radius: var(--radius-md);
            font-family: "Cabinet Grotesk", "Space Grotesk", sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(102, 102, 255, 0.35);
            transition: all var(--transition-fast);
        }

        .btn-primary:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 102, 255, 0.5);
        }

        .section-heading {
            font-family: "Cabinet Grotesk", "Space Grotesk", sans-serif !important;
            color: #F5F5FF;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .telemetry-heading {
            margin: 0 0 18px;
        }

        .telemetry-heading .section-heading {
            font-size: clamp(1.35rem, 2vw, 1.65rem);
        }

        .telemetry-heading p {
            margin-top: 5px;
            color: rgba(201, 232, 255, 0.72);
            font-size: 0.9rem;
        }

        /* Six database-backed summary metrics */
        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
            animation: dashboardFadeSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.08s both;
        }

        .cards .card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            isolation: isolate;
            overflow: hidden;
            min-height: 136px;
            padding: 20px 20px 18px;
            background: rgba(22, 23, 42, 0.82) !important;
            border: 1px solid rgba(201, 232, 255, 0.14) !important;
            border-top-color: rgba(255, 255, 255, 0.22) !important;
            backdrop-filter: blur(14px) saturate(130%) !important;
            -webkit-backdrop-filter: blur(14px) saturate(130%) !important;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            transition: transform 220ms ease, border-color 220ms ease, box-shadow 220ms ease;
            animation: dashboardCardReveal 0.55s ease both;
        }

        .cards .card:nth-child(2) { animation-delay: 70ms; }
        .cards .card:nth-child(3) { animation-delay: 140ms; }
        .cards .card:nth-child(4) { animation-delay: 210ms; }
        .cards .card:nth-child(5) { animation-delay: 280ms; }
        .cards .card:nth-child(6) { animation-delay: 350ms; }

        .metric-icon {
            position: absolute;
            top: 17px;
            right: 18px;
            display: grid;
            width: 34px;
            height: 34px;
            place-items: center;
            border: 1px solid rgba(184, 186, 255, 0.18);
            border-radius: 10px;
            background: rgba(184, 186, 255, 0.07);
            color: #B8BAFF;
        }

        .metric-icon svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.7;
        }

        .cards .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24), inset 0 1px 0 rgba(255, 255, 255, 0.09);
            border-color: rgba(184, 186, 255, 0.28) !important;
        }

        .cards .card:nth-child(1) { border-left: 3px solid #6666FF !important; }
        .cards .card:nth-child(2) { border-left: 3px solid #B8BAFF !important; }
        .cards .card:nth-child(3) { border-left: 3px solid #C9E8FF !important; }
        .cards .card:nth-child(4) { border-left: 3px solid #B9F0D7 !important; }
        .cards .card:nth-child(5) { border-left: 3px solid #6666FF !important; }
        .cards .card:nth-child(6) { border-left: 3px solid #B8BAFF !important; }

        .cards .card h3 {
            max-width: calc(100% - 42px);
            min-height: 2.5em;
            font-size: 0.76rem;
            color: rgba(201, 232, 255, 0.72) !important;
            margin-bottom: 8px;
            text-transform: uppercase;
            line-height: 1.35;
            letter-spacing: 0.045em;
        }

        .cards .card p {
            font-family: "Cabinet Grotesk", "Space Grotesk", sans-serif !important;
            font-size: clamp(1.8rem, 2.4vw, 2.15rem);
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #F5F5FF !important;
            line-height: 1;
        }

        .section-title {
            font-size: 1.08rem !important;
            margin: 0 0 6px !important;
        }

        .activity-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 12px;
        }

        .section-description {
            margin: 0 0 18px;
            font-size: 0.84rem !important;
        }

        /* Management Sections */
        .section {
            background: rgba(20, 21, 39, 0.88) !important;
            border: 1px solid rgba(201, 232, 255, 0.12) !important;
            border-top-color: rgba(255, 255, 255, 0.18) !important;
            backdrop-filter: blur(12px) saturate(115%) !important;
            -webkit-backdrop-filter: blur(12px) saturate(115%) !important;
            border-radius: 15px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.17), inset 0 1px 0 rgba(255, 255, 255, 0.05);
            animation: dashboardFadeSlideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.12s both;
        }

        .section h2 {
            font-family: "Cabinet Grotesk", "Space Grotesk", sans-serif !important;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #F5F5FF !important;
        }

        .section p {
            color: #C9E8FF;
            opacity: 0.85;
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .quick-links {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .quick-link {
            display: block;
            min-height: 66px;
            padding: 14px 16px;
            background: rgba(184, 186, 255, 0.045);
            border: 1px solid rgba(184, 186, 255, 0.13);
            border-radius: 11px;
            color: #F5F5FF;
            text-decoration: none;
            font-weight: 650;
            font-size: 0.86rem;
            line-height: 1.45;
            transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
        }

        .quick-link:hover {
            background: rgba(102, 102, 255, 0.09);
            border-color: rgba(184, 186, 255, 0.26);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Inputs */
        input[type="text"] {
            background: rgba(12, 12, 22, 0.7);
            border: 1px solid rgba(201, 232, 255, 0.2);
            border-radius: var(--radius-md);
            padding: 12px 18px;
            color: #F5F5FF;
            font-family: "DM Sans", sans-serif;
            font-size: 0.92rem;
            transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #6666FF;
            box-shadow: 0 0 16px rgba(102, 102, 255, 0.35);
        }

        /* Table styles */
        .admin-table-container {
            overflow-x: auto;
            border-radius: 12px;
            scrollbar-color: rgba(184, 186, 255, 0.4) transparent;
        }

        .admin-table-container table {
            width: 100%;
            min-width: 760px;
            border-collapse: separate;
            border-spacing: 0 5px;
        }

        .admin-table-container th {
            font-family: "Space Grotesk", sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            color: #B8BAFF;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid rgba(201, 232, 255, 0.22);
        }

        .admin-table-container td {
            background: rgba(12, 12, 22, 0.48);
            padding: 12px;
            color: #F5F5FF;
            font-size: 0.92rem;
            border-top: 1px solid rgba(201, 232, 255, 0.12);
            border-bottom: 1px solid rgba(201, 232, 255, 0.12);
            vertical-align: middle;
            transition: background var(--transition-fast);
        }

        .admin-table-container tr td:first-child {
            border-left: 1px solid rgba(201, 232, 255, 0.1);
            border-top-left-radius: var(--radius-md);
            border-bottom-left-radius: var(--radius-md);
        }

        .admin-table-container tr td:last-child {
            border-right: 1px solid rgba(201, 232, 255, 0.1);
            border-top-right-radius: var(--radius-md);
            border-bottom-right-radius: var(--radius-md);
        }

        .admin-table-container tr:hover td {
            background: rgba(102, 102, 255, 0.14);
        }

        .admin-table-container td strong {
            color: #ffffff;
            font-weight: 700;
        }

        .admin-table-container .btn-secondary {
            min-height: 34px;
        }

        .mono {
            font-family: "Space Mono", monospace;
        }

        .item-type {
            font-family: "Space Mono", monospace;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .item-type.lost {
            background: rgba(201, 232, 255, 0.14);
            color: #C9E8FF;
            border: 1px solid rgba(201, 232, 255, 0.35);
        }

        .item-type.found {
            background: rgba(185, 240, 215, 0.18);
            color: #B9F0D7;
            border: 1px solid rgba(185, 240, 215, 0.35);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 25px;
            font-family: "Space Mono", monospace;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .status-badge.pending {
            background: rgba(184, 186, 255, 0.18);
            color: #B8BAFF;
            border: 1px solid rgba(184, 186, 255, 0.35);
        }

        .status-badge.approved {
            background: rgba(102, 102, 255, 0.2);
            color: #B8BAFF;
            border: 1px solid rgba(102, 102, 255, 0.4);
        }

        .status-badge.claimed,
        .status-badge.returned {
            background: rgba(185, 240, 215, 0.18);
            color: #B9F0D7;
            border: 1px solid rgba(185, 240, 215, 0.35);
        }

        .status-badge.rejected {
            background: rgba(255, 145, 122, 0.14);
            color: #FFB5A5;
            border: 1px solid rgba(255, 145, 122, 0.35);
        }

        .admin-copyright {
            position: static !important;
            margin: 40px 0 0 0 !important;
            padding: 24px 0 12px 0 !important;
            text-align: center;
            color: #B8BAFF !important;
            opacity: 0.85;
            font-size: 0.85rem;
            border: none !important;
            border-top: 1px solid rgba(201, 232, 255, 0.12) !important;
            background: transparent !important;
            box-shadow: none !important;
            pointer-events: auto;
        }

        @keyframes dashboardFadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes dashboardCardReveal {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes dashboardOrbDrift {
            from { transform: translate3d(-12px, 0, 0) scale(0.92); }
            to { transform: translate3d(20px, 24px, 0) scale(1.08); }
        }

        @keyframes dashboardStatusPulse {
            0%, 100% { box-shadow: 0 0 0 4px rgba(185, 240, 215, 0.12), 0 0 14px rgba(185, 240, 215, 0.75); }
            50% { box-shadow: 0 0 0 7px rgba(185, 240, 215, 0.06), 0 0 20px rgba(185, 240, 215, 0.95); }
        }

        /* =====================================================================
           ORIGINAL LIGHT MODE: Clean Light Glass Theme (No Lavender Theme)
           Preserves original light-mode backgrounds (#f4f6f9), white glass panels,
           clean contrast, and signature CATCHIT accents.
           ===================================================================== */
        html[data-theme="light"] {
            background-color: #f4f6f9 !important;
        }

        html[data-theme="light"] .admin-body {
            background-color: #f4f6f9 !important;
            background-image: none !important;
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .sidebar {
            background: rgba(255, 255, 255, 0.95) !important;
            border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important;
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .sidebar h2 {
            color: #0C0C16 !important;
            text-shadow: none !important;
        }

        html[data-theme="light"] .sidebar a {
            color: #2F2A56 !important;
        }

        html[data-theme="light"] .sidebar a:hover {
            color: #0C0C16 !important;
            background: rgba(102, 102, 255, 0.1) !important;
            border-color: rgba(102, 102, 255, 0.25) !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05) !important;
        }

        html[data-theme="light"] .sidebar a.is-active {
            color: #0C0C16 !important;
            background: linear-gradient(135deg, rgba(102, 102, 255, 0.16), rgba(184, 186, 255, 0.25)) !important;
            border-color: #6666FF !important;
            border-left: 4px solid #6666FF !important;
            box-shadow: 0 0 16px rgba(102, 102, 255, 0.2), inset 0 1px 1px rgba(255, 255, 255, 0.9) !important;
        }

        html[data-theme="light"] .sidebar .logout {
            background: linear-gradient(135deg, #6666FF, #B8BAFF) !important;
            color: #0C0C16 !important;
            border: 1px solid rgba(102, 102, 255, 0.4) !important;
            box-shadow: 0 4px 16px rgba(102, 102, 255, 0.25) !important;
        }

        html[data-theme="light"] .topbar {
            background: rgba(255, 255, 255, 0.92) !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            border-top: 1px solid rgba(255, 255, 255, 1) !important;
            box-shadow: 0 10px 28px rgba(23, 31, 51, 0.06), inset 0 1px 1px rgba(255, 255, 255, 0.9) !important;
        }

        html[data-theme="light"] .topbar h1 {
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .topbar .welcome {
            color: #526477 !important;
        }

        html[data-theme="light"] .topbar .welcome strong {
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .system-status {
            background: #16864b !important;
            box-shadow: 0 0 0 4px rgba(22, 134, 75, 0.1), 0 0 12px rgba(22, 134, 75, 0.3);
        }

        html[data-theme="light"] .topbar .theme-toggle {
            background: rgba(0, 0, 0, 0.05) !important;
            border-color: rgba(0, 0, 0, 0.14) !important;
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .topbar .theme-toggle:hover {
            background: rgba(102, 102, 255, 0.15) !important;
            border-color: #6666FF !important;
            color: #0C0C16 !important;
            box-shadow: 0 4px 14px rgba(102, 102, 255, 0.2) !important;
        }

        html[data-theme="light"] .btn-secondary {
            background: rgba(184, 186, 255, 0.22) !important;
            border: 1px solid rgba(102, 102, 255, 0.45) !important;
            color: #0C0C16 !important;
            font-weight: 700 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
        }

        html[data-theme="light"] .btn-secondary:hover {
            background: linear-gradient(135deg, #6666FF, #B8BAFF) !important;
            border-color: #6666FF !important;
            color: #0C0C16 !important;
            box-shadow: 0 4px 14px rgba(102, 102, 255, 0.3) !important;
        }

        html[data-theme="light"] .section-heading {
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .cards .card {
            background: rgba(255, 255, 255, 0.88) !important;
            border: 1px solid rgba(76, 80, 145, 0.14) !important;
            border-top: 1px solid rgba(255, 255, 255, 1) !important;
            box-shadow: 0 10px 24px rgba(23, 31, 51, 0.07), inset 0 1px 1px rgba(255, 255, 255, 0.95) !important;
        }

        html[data-theme="light"] .metric-icon {
            border-color: rgba(76, 80, 145, 0.16);
            background: rgba(102, 102, 255, 0.075);
            color: #45459d;
        }

        html[data-theme="light"] .cards .card:hover {
            box-shadow: 0 14px 28px rgba(23, 31, 51, 0.1) !important;
            border-color: rgba(102, 102, 255, 0.32) !important;
        }

        html[data-theme="light"] .cards .card h3 {
            color: #667085 !important;
            font-weight: 700;
        }

        html[data-theme="light"] .cards .card p {
            color: #0C0C16 !important;
            text-shadow: none !important;
        }

        html[data-theme="light"] .cards .card:nth-child(1) { border-left: 3px solid #6666FF !important; }
        html[data-theme="light"] .cards .card:nth-child(2) { border-left: 3px solid #8585e8 !important; }
        html[data-theme="light"] .cards .card:nth-child(3) { border-left: 3px solid #5b9fc5 !important; }
        html[data-theme="light"] .cards .card:nth-child(4) { border-left: 3px solid #4d9d79 !important; }
        html[data-theme="light"] .cards .card:nth-child(5) { border-left: 3px solid #6666FF !important; }
        html[data-theme="light"] .cards .card:nth-child(6) { border-left: 3px solid #8585e8 !important; }

        html[data-theme="light"] .section {
            background: rgba(255, 255, 255, 0.86) !important;
            border: 1px solid rgba(76, 80, 145, 0.14) !important;
            border-top: 1px solid rgba(255, 255, 255, 1) !important;
            box-shadow: 0 12px 28px rgba(23, 31, 51, 0.07), inset 0 1px 1px rgba(255, 255, 255, 0.98) !important;
        }

        html[data-theme="light"] .telemetry-heading p {
            color: #667085;
        }

        html[data-theme="light"] .section h2 {
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .section p {
            color: #526477 !important;
        }

        html[data-theme="light"] .activity-heading .btn-secondary {
            background: rgba(102, 102, 255, 0.055) !important;
            border-color: rgba(76, 80, 145, 0.16) !important;
            box-shadow: none !important;
        }

        html[data-theme="light"] .quick-link {
            background: rgba(102, 102, 255, 0.045) !important;
            border: 1px solid rgba(76, 80, 145, 0.14) !important;
            color: #0C0C16 !important;
            font-weight: 600;
        }

        html[data-theme="light"] .quick-link:hover {
            background: rgba(102, 102, 255, 0.09) !important;
            border-color: rgba(102, 102, 255, 0.32) !important;
            color: #0C0C16 !important;
            box-shadow: 0 5px 14px rgba(23, 31, 51, 0.07) !important;
        }

        html[data-theme="light"] input[type="text"] {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(0, 0, 0, 0.15) !important;
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .admin-table-container th {
            color: #39369a !important;
            border-bottom: 2px solid rgba(102, 102, 255, 0.18) !important;
        }

        html[data-theme="light"] .admin-table-container td {
            background: rgba(255, 255, 255, 0.72) !important;
            color: #0C0C16 !important;
            border-top: 1px solid rgba(76, 80, 145, 0.08) !important;
            border-bottom: 1px solid rgba(76, 80, 145, 0.08) !important;
        }

        html[data-theme="light"] .admin-table-container td strong {
            color: #0C0C16 !important;
        }

        html[data-theme="light"] .admin-table-container tr td:first-child {
            border-left: 1px solid rgba(0, 0, 0, 0.05) !important;
        }

        html[data-theme="light"] .admin-table-container tr td:last-child {
            border-right: 1px solid rgba(0, 0, 0, 0.05) !important;
        }

        html[data-theme="light"] .admin-table-container tr:hover td {
            background: rgba(232, 233, 255, 0.62) !important;
        }

        html[data-theme="light"] .item-type.lost {
            background: rgba(201, 232, 255, 0.6) !important;
            color: #172B4D !important;
            border: 1px solid #7cb9e8 !important;
        }

        html[data-theme="light"] .item-type.found {
            background: rgba(185, 240, 215, 0.6) !important;
            color: #0E4E30 !important;
            border: 1px solid #7cd6b0 !important;
        }

        html[data-theme="light"] .status-badge.pending {
            background: rgba(184, 186, 255, 0.4) !important;
            color: #272355 !important;
            border: 1px solid #8E92E8 !important;
        }

        html[data-theme="light"] .status-badge.approved {
            background: rgba(102, 102, 255, 0.22) !important;
            color: #1A1A60 !important;
            border: 1px solid #6666FF !important;
        }

        html[data-theme="light"] .status-badge.claimed,
        html[data-theme="light"] .status-badge.returned {
            background: rgba(185, 240, 215, 0.6) !important;
            color: #0E4E30 !important;
            border: 1px solid #7cd6b0 !important;
        }

        html[data-theme="light"] .status-badge.rejected {
            background: rgba(255, 145, 122, 0.2) !important;
            color: #7a2918 !important;
            border: 1px solid rgba(190, 75, 52, 0.38) !important;
        }

        html[data-theme="dark"] .admin-body .main-content .cards .card {
            background: rgba(22, 23, 42, 0.82) !important;
            border-top-color: rgba(255, 255, 255, 0.22) !important;
            border-right-color: rgba(201, 232, 255, 0.14) !important;
            border-bottom-color: rgba(201, 232, 255, 0.14) !important;
            backdrop-filter: blur(14px) saturate(130%) !important;
            -webkit-backdrop-filter: blur(14px) saturate(130%) !important;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.08) !important;
        }

        html[data-theme="dark"] .admin-body .main-content .metric-icon {
            border-color: rgba(184, 186, 255, 0.18);
            background: rgba(184, 186, 255, 0.07);
            color: #B8BAFF;
        }

        html[data-theme="dark"] .admin-body .main-content .cards .card:nth-child(1) { border-left: 3px solid #6666FF !important; }
        html[data-theme="dark"] .admin-body .main-content .cards .card:nth-child(2) { border-left: 3px solid #B8BAFF !important; }
        html[data-theme="dark"] .admin-body .main-content .cards .card:nth-child(3) { border-left: 3px solid #C9E8FF !important; }
        html[data-theme="dark"] .admin-body .main-content .cards .card:nth-child(4) { border-left: 3px solid #B9F0D7 !important; }
        html[data-theme="dark"] .admin-body .main-content .cards .card:nth-child(5) { border-left: 3px solid #6666FF !important; }
        html[data-theme="dark"] .admin-body .main-content .cards .card:nth-child(6) { border-left: 3px solid #B8BAFF !important; }

        html[data-theme="dark"] .admin-body .main-content .cards .card:hover {
            border-top-color: rgba(184, 186, 255, 0.32) !important;
            border-right-color: rgba(184, 186, 255, 0.32) !important;
            border-bottom-color: rgba(184, 186, 255, 0.32) !important;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
        }

        html[data-theme="dark"] .admin-body .main-content .section {
            background: rgba(20, 21, 39, 0.82) !important;
            border-color: rgba(201, 232, 255, 0.14) !important;
            border-top-color: rgba(255, 255, 255, 0.22) !important;
            backdrop-filter: blur(14px) saturate(125%) !important;
            -webkit-backdrop-filter: blur(14px) saturate(125%) !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.06) !important;
        }

        html[data-theme="dark"] .admin-body .main-content .section .quick-link {
            background: rgba(184, 186, 255, 0.045) !important;
            border-color: rgba(184, 186, 255, 0.13) !important;
            box-shadow: none !important;
        }

        html[data-theme="dark"] .admin-body .main-content .section .quick-link:hover {
            background: rgba(102, 102, 255, 0.09) !important;
            border-color: rgba(184, 186, 255, 0.26) !important;
        }

        html[data-theme="dark"] .admin-body .main-content .activity-heading .btn-secondary {
            background: rgba(184, 186, 255, 0.045) !important;
            border-color: rgba(184, 186, 255, 0.13) !important;
            box-shadow: none !important;
        }

        html[data-theme="light"] .admin-copyright {
            color: #526477 !important;
            border-top: 1px solid rgba(0, 0, 0, 0.08) !important;
        }

        @media (max-width: 1100px) {
            .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .sidebar {
                width: 100%;
                min-height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }

        @media (max-width: 600px) {
            .main-content {
                padding: 14px;
            }

            .topbar,
            .section {
                padding: 20px;
            }

            .admin-account-actions {
                width: 100%;
                flex-wrap: wrap;
            }

            .cards {
                grid-template-columns: minmax(0, 1fr);
                gap: 14px;
            }

            .cards .card {
                min-height: 136px;
                padding: 20px;
            }

            .quick-links {
                grid-template-columns: minmax(0, 1fr);
                gap: 10px;
            }

            .section {
                overflow: hidden;
            }

            .telemetry-heading {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }

            .activity-heading {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

    </style>
</head>

<body class="admin-body">

    <!-- Admin Futuristic Glass Sidebar -->
    <div class="sidebar">

        <h2 class="admin-brand-lockup">
            <picture class="brand-lockup">
                <img
                    class="brand-logo-dark"
                    src="<?php echo htmlspecialchars($base_url); ?>/images/brand/reclaim-logo-dark.png"
                    alt="RE:CLAIM - You lost we find"
                >
                <img
                    class="brand-logo-light"
                    src="<?php echo htmlspecialchars($base_url); ?>/images/brand/reclaim-logo-light.png"
                    alt=""
                >
            </picture>
            <span>Control room</span>
        </h2>

        <a href="dashboard.php" class="<?php echo $current_page === "dashboard.php" ? "is-active" : ""; ?>" <?php echo $current_page === "dashboard.php" ? 'aria-current="page"' : ""; ?>>
            <span>⎔</span> Overview
        </a>

        <a href="manage_items.php" class="<?php echo $current_page === "manage_items.php" ? "is-active" : ""; ?>" <?php echo $current_page === "manage_items.php" ? 'aria-current="page"' : ""; ?>>
            <span>◈</span> Item Reports
        </a>

        <a href="manage_users.php" class="<?php echo $current_page === "manage_users.php" ? "is-active" : ""; ?>" <?php echo $current_page === "manage_users.php" ? 'aria-current="page"' : ""; ?>>
            <span>👥</span> Accounts
        </a>

        <a href="manage_claims.php" class="<?php echo $current_page === "manage_claims.php" ? "is-active" : ""; ?>" <?php echo $current_page === "manage_claims.php" ? 'aria-current="page"' : ""; ?>>
            <span>⚖</span> Claims
        </a>

        <a href="../index.php">
            <span>↗</span> Visit Website
        </a>

        <a href="../help.php">
            <span>?</span> Help &amp; Support
        </a>

        <a href="../feedback.php">
            <span>✦</span> Feedback
        </a>

        <a href="../logout.php" class="logout">
            Sign Out
        </a>

    </div>

    <!-- Main Content Area -->
    <div class="main-content">

        <!-- Topbar with Highlighted Back Button -->
        <div class="topbar">

            <div>
                <h1>Admin Dashboard</h1>
                <p class="welcome">
                    Signed in as <strong><?php echo htmlspecialchars($_SESSION["name"]); ?></strong>
                    <span class="system-status" role="img" aria-label="System online" title="System online"></span>
                </p>
            </div>

            <div class="admin-account-actions">
                <!-- Theme Toggle Button -->
                <button class="theme-toggle" type="button" aria-label="Toggle visual theme" aria-pressed="false">
                    <span class="theme-toggle-icon" aria-hidden="true">☾</span>
                </button>

                <a href="../logout.php" class="btn-secondary">
                    Sign Out
                </a>
            </div>

        </div>

        <div class="telemetry-heading">
            <h2 class="section-heading">Dashboard overview</h2>
            <p>Campus activity and moderation at a glance.</p>
        </div>

        <!-- 6 Live Database Statistics Cards - Palette Aligned -->
        <div class="cards">

            <div class="card">
                <span class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="9" cy="8" r="3"></circle>
                        <path d="M3.5 19v-1.2A4.8 4.8 0 0 1 8.3 13h1.4a4.8 4.8 0 0 1 4.8 4.8V19"></path>
                        <path d="M16 5.3a3 3 0 0 1 0 5.8M17 13.4a4.5 4.5 0 0 1 3.5 4.4V19"></path>
                    </svg>
                </span>
                <h3>Registered Users</h3>
                <p><?php echo $total_users; ?></p>
            </div>

            <div class="card">
                <span class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <rect x="4" y="4" width="16" height="16" rx="3"></rect>
                        <path d="M8 9h8M8 13h8M8 17h4"></path>
                    </svg>
                </span>
                <h3>Total Item Reports</h3>
                <p><?php echo $total_items; ?></p>
            </div>

            <div class="card">
                <span class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="10.5" cy="10.5" r="6.5"></circle>
                        <path d="m15.3 15.3 4.2 4.2M8 10.5h5"></path>
                    </svg>
                </span>
                <h3>Lost-Item Reports</h3>
                <p><?php echo $total_lost; ?></p>
            </div>

            <div class="card">
                <span class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 12.5 9.2 18 20 6.5"></path>
                        <circle cx="12" cy="12" r="9"></circle>
                    </svg>
                </span>
                <h3>Found-Item Reports</h3>
                <p><?php echo $total_found; ?></p>
            </div>

            <div class="card">
                <span class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2"></path>
                    </svg>
                </span>
                <h3>Awaiting Review</h3>
                <p><?php echo $total_pending; ?></p>
            </div>

            <div class="card">
                <span class="metric-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m8 12 3 3 5-6"></path>
                        <path d="M12 3v3M5.6 5.6l2.1 2.1M3 12h3M18 12h3"></path>
                        <circle cx="12" cy="13" r="8"></circle>
                    </svg>
                </span>
                <h3>Claim Requests</h3>
                <p><?php echo $total_claims; ?></p>
            </div>

        </div>

        <!-- Quick Administration Controls -->
        <div class="section">
            <h2 class="section-title">Quick actions</h2>
            <p class="section-description">Review reports, verify claims, and manage student accounts.</p>

            <div class="quick-links">
                <a href="manage_items.php" class="quick-link">
                    Review Item Reports (<?php echo $total_pending; ?> Pending)
                </a>
                <a href="manage_users.php" class="quick-link">
                    Manage Registered Accounts (<?php echo $total_users; ?>)
                </a>
                <a href="manage_claims.php" class="quick-link">
                    Inspect Claim Requests (<?php echo $total_claims; ?>)
                </a>
            </div>
        </div>

        <!-- Recent Activity Feed -->
        <div class="section">
            <div class="activity-heading">
                <h2 class="section-title">Recent reports</h2>
                <a href="manage_items.php" class="btn-secondary" style="font-size: 0.82rem; padding: 6px 14px;">View All Reports &rarr;</a>
            </div>

            <div class="admin-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Reporter</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_admin_result && mysqli_num_rows($recent_admin_result) > 0) { ?>
                            <?php while ($row = mysqli_fetch_assoc($recent_admin_result)) { ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row["item_name"]); ?></strong></td>
                                    <td>
                                        <span class="item-type <?php echo htmlspecialchars($row["item_type"]); ?>">
                                            <?php echo ucfirst($row["item_type"]); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row["category_name"] ?? "General"); ?></td>
                                    <td><?php echo htmlspecialchars($row["reporter_name"] ?? "Unknown"); ?></td>
                                    <td><?php echo htmlspecialchars($row["item_date"]); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo htmlspecialchars($row["status"]); ?>">
                                            <?php echo ucfirst($row["status"]); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="manage_items.php" class="btn-secondary" style="padding: 6px 12px; font-size: 0.82rem;">
                                            Moderate
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--catchit-text-muted);">No records found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="admin-copyright">&copy; 2026 re:claim &bull; Campus Lost &amp; Found</footer>
    </div>

    <script src="<?php echo htmlspecialchars($base_url); ?>/js/script.js?v=<?php echo filemtime(__DIR__ . '/../js/script.js'); ?>" defer></script>
</body> 
</html>
