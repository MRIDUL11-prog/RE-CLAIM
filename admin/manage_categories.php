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

$message = "";
$message_type = "success";

// Handle Category Creation or Deletion
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $category_name = trim($_POST["category_name"] ?? "");

        if (!empty($category_name)) {
            // Check duplicate
            $check_sql = "SELECT category_id FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1";
            $check_stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "s", $category_name);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                $message = "Category '$category_name' already exists in the system.";
                $message_type = "error";
            } else {
                $insert_sql = "INSERT INTO categories (category_name) VALUES (?)";
                $insert_stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($insert_stmt, "s", $category_name);
                if (mysqli_stmt_execute($insert_stmt)) {
                    $message = "Category '$category_name' successfully added to taxonomy.";
                    $message_type = "success";
                } else {
                    $message = "Database error while adding category.";
                    $message_type = "error";
                }
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        } else {
            $message = "Please enter a valid category name.";
            $message_type = "error";
        }
    } elseif ($action === "delete") {
        $category_id = intval($_POST["category_id"] ?? 0);

        if ($category_id > 0) {
            // Check if any items are linked to this category
            $item_check = "SELECT COUNT(*) AS total FROM items WHERE category_id = ?";
            $item_stmt = mysqli_prepare($conn, $item_check);
            mysqli_stmt_bind_param($item_stmt, "i", $category_id);
            mysqli_stmt_execute($item_stmt);
            $res = mysqli_stmt_get_result($item_stmt);
            $count_row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($item_stmt);

            if ((int)$count_row["total"] > 0) {
                $message = "Cannot remove category: " . $count_row["total"] . " items are currently assigned to it.";
                $message_type = "error";
            } else {
                $del_sql = "DELETE FROM categories WHERE category_id = ?";
                $del_stmt = mysqli_prepare($conn, $del_sql);
                mysqli_stmt_bind_param($del_stmt, "i", $category_id);
                if (mysqli_stmt_execute($del_stmt)) {
                    $message = "Category successfully removed.";
                    $message_type = "success";
                } else {
                    $message = "Error removing category.";
                    $message_type = "error";
                }
                mysqli_stmt_close($del_stmt);
            }
        }
    }
}

// Fetch all categories with item counts
$categories = [];
$cat_query = "SELECT c.category_id, c.category_name, COUNT(i.item_id) AS item_count
              FROM categories c
              LEFT JOIN items i ON c.category_id = i.category_id
              GROUP BY c.category_id, c.category_name
              ORDER BY c.category_name ASC";
if ($res = mysqli_query($conn, $cat_query)) {
    while ($row = mysqli_fetch_assoc($res)) {
        $categories[] = $row;
    }
}

// Counts for sidebar badges
function getCount($conn, $sql) {
    if (!$conn) return 0;
    $result = mysqli_query($conn, $sql);
    if (!$result) return 0;
    $data = mysqli_fetch_assoc($result);
    return (int)($data["total"] ?? 0);
}

$total_users = getCount($conn, "SELECT COUNT(*) AS total FROM users");
$total_items = getCount($conn, "SELECT COUNT(*) AS total FROM items");
$total_pending_items = getCount($conn, "SELECT COUNT(*) AS total FROM items WHERE status = 'pending'");
$total_pending_claims = getCount($conn, "SELECT COUNT(*) AS total FROM claims WHERE claim_status = 'pending'");
$total_claims = getCount($conn, "SELECT COUNT(*) AS total FROM claims");

$script_directory = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
$base_url = rtrim(preg_replace("~/(student|admin)$~", "", $script_directory), "/");
$current_page = basename($_SERVER["SCRIPT_NAME"]);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0C0C16">
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($base_url); ?>/images/brand/reclaim-icon.png">

    <title>Category Management // RE:CLAIM Control Room</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Space+Grotesk:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>/css/style.css?v=18">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>/css/brand.css?v=<?php echo filemtime(__DIR__ . '/../css/brand.css'); ?>">
    <script src="<?php echo htmlspecialchars($base_url); ?>/js/navigation.js?v=<?php echo filemtime(__DIR__ . '/../js/navigation.js'); ?>" defer></script>

    <style>
        :root {
            --font-heading: 'Space Grotesk', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-body: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --c-slate-blue: #6666FF;
            --c-periwinkle: #B8BAFF;
            --c-columbia: #C9E8FF;
            --c-rich-black: #0C0C16;
            --c-celadon: #B9F0D7;
            --glass-bg: rgba(16, 16, 30, 0.72);
            --glass-border: rgba(184, 186, 255, 0.18);
            --glass-border-hover: rgba(184, 186, 255, 0.45);
            --glass-shine: inset 0 1px 1px 0 rgba(201, 232, 255, 0.35);
            --glass-shadow: 0 16px 36px -10px rgba(0, 0, 0, 0.65), 0 0 24px -6px rgba(102, 102, 255, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: var(--font-body);
            background-color: var(--c-rich-black);
            background-image:
                radial-gradient(circle at 10% 8%, rgba(102, 102, 255, 0.18), transparent 440px),
                radial-gradient(circle at 92% 16%, rgba(185, 240, 215, 0.12), transparent 400px),
                radial-gradient(rgba(102, 102, 255, 0.08) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 32px 32px;
            color: #f3f4ff;
            min-height: 100vh;
            display: flex;
        }

        .admin-sidebar {
            width: 290px;
            min-height: 100vh;
            background: rgba(12, 12, 22, 0.82);
            backdrop-filter: blur(24px) saturate(190%);
            -webkit-backdrop-filter: blur(24px) saturate(190%);
            position: fixed;
            top: 0; left: 0; bottom: 0;
            padding: 28px 20px 22px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--glass-border);
            box-shadow: 10px 0 40px rgba(0, 0, 0, 0.55);
            z-index: 100;
        }

        .sidebar-brand {
            display: flex; align-items: center; gap: 14px;
            padding-bottom: 24px; margin-bottom: 22px;
            border-bottom: 1px solid rgba(184, 186, 255, 0.14);
            text-decoration: none;
        }

        .brand-emblem {
            width: 44px; height: 44px; border-radius: 12px;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            flex: 0 0 44px;
        }

        .brand-emblem-image { border-radius: 12px; }
        .brand-title { font-family: var(--font-heading); font-weight: 800; font-size: 20px; color: #ffffff; line-height: 1.1; }
        .brand-sub { font-size: 11px; font-weight: 600; letter-spacing: 0.12em; text-transform: uppercase; color: var(--c-periwinkle); }

        .sidebar-links { list-style: none; display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .nav-item a {
            display: flex; align-items: center; gap: 13px;
            padding: 12px 14px; border-radius: 12px;
            color: var(--c-columbia); text-decoration: none;
            font-size: 14px; font-weight: 500;
            border: 1px solid transparent; transition: all 0.25s ease;
            position: relative;
        }
        .nav-item a svg { width: 19px; height: 19px; fill: none; stroke: currentColor; stroke-width: 2; }
        .nav-item a:hover {
            color: #ffffff; background: rgba(102, 102, 255, 0.14);
            border-color: rgba(184, 186, 255, 0.28); transform: translateX(5px);
        }
        .nav-item a.is-active {
            color: #ffffff; font-weight: 600;
            background: linear-gradient(135deg, rgba(102, 102, 255, 0.32) 0%, rgba(184, 186, 255, 0.12) 100%);
            border: 1px solid rgba(184, 186, 255, 0.45);
            box-shadow: 0 8px 24px rgba(102, 102, 255, 0.3);
        }
        .nav-item a.is-active::before {
            content: ''; position: absolute; left: 0; top: 6px; bottom: 6px; width: 4px;
            border-radius: 0 4px 4px 0; background: linear-gradient(180deg, var(--c-slate-blue) 0%, var(--c-periwinkle) 100%);
        }
        .nav-pill-badge {
            margin-left: auto; padding: 2px 8px; border-radius: 9999px;
            font-family: var(--font-heading); font-size: 11px; font-weight: 700;
            background: rgba(102, 102, 255, 0.25); border: 1px solid rgba(184, 186, 255, 0.3);
            color: var(--c-columbia);
        }

        .control-main {
            margin-left: 290px;
            flex: 1;
            padding: clamp(20px, 3.2vw, 42px);
            min-width: 0;
        }

        .hud-header {
            background: rgba(16, 16, 30, 0.76);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 20px 26px;
            margin-bottom: 30px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: var(--glass-shadow), var(--glass-shine);
        }

        .hud-header h1 {
            font-family: var(--font-heading); font-size: 24px; font-weight: 800; color: #ffffff;
        }

        .glass-panel {
            background: linear-gradient(135deg, rgba(201, 232, 255, 0.08) 0%, rgba(16, 16, 30, 0.74) 100%);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid var(--glass-border);
            border-radius: 18px;
            padding: 28px;
            box-shadow: var(--glass-shadow), var(--glass-shine);
            margin-bottom: 26px;
        }

        .form-glass-row {
            display: flex; gap: 14px; margin-top: 18px; flex-wrap: wrap;
        }

        .glass-input {
            flex: 1; min-width: 260px;
            background: rgba(12, 12, 22, 0.7);
            border: 1px solid rgba(184, 186, 255, 0.25);
            border-radius: 12px;
            padding: 12px 18px;
            color: #ffffff; font-size: 14px; font-family: var(--font-body);
            transition: all 0.25s ease;
        }

        .glass-input:focus {
            outline: none; border-color: var(--c-slate-blue);
            box-shadow: 0 0 16px rgba(102, 102, 255, 0.35);
        }

        .btn-submit-glass {
            background: linear-gradient(135deg, var(--c-slate-blue) 0%, #5050df 100%);
            border: 1px solid rgba(201, 232, 255, 0.4);
            border-radius: 12px;
            padding: 12px 24px;
            color: #ffffff; font-family: var(--font-heading);
            font-size: 14px; font-weight: 700;
            cursor: pointer; transition: all 0.25s ease;
        }

        .btn-submit-glass:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 102, 255, 0.45);
        }

        .category-grid-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
            margin-top: 20px;
        }

        .cat-card-glass {
            background: rgba(12, 12, 22, 0.6);
            border: 1px solid rgba(184, 186, 255, 0.16);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex; justify-content: space-between; align-items: center;
            transition: all 0.25s ease;
        }

        .cat-card-glass:hover {
            border-color: rgba(184, 186, 255, 0.4);
            background: rgba(102, 102, 255, 0.12);
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
        }

        .cat-card-name { font-weight: 700; font-size: 15px; color: #ffffff; }
        .cat-card-badge {
            font-family: var(--font-heading); font-size: 11px; font-weight: 700;
            padding: 3px 8px; border-radius: 6px;
            background: rgba(102, 102, 255, 0.2); border: 1px solid rgba(184, 186, 255, 0.3);
            color: var(--c-periwinkle);
        }

        .btn-del-cat {
            background: rgba(239, 68, 68, 0.16);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 5px 10px; border-radius: 6px; font-size: 11.5px;
            cursor: pointer; transition: all 0.2s ease;
        }
        .btn-del-cat:hover {
            background: rgba(239, 68, 68, 0.35); color: #ffffff;
        }

        .alert-box {
            padding: 14px 18px; border-radius: 12px; margin-bottom: 22px;
            font-size: 13.5px; font-weight: 500;
        }
        .alert-box.success {
            background: rgba(185, 240, 215, 0.15); border: 1px solid rgba(185, 240, 215, 0.35); color: var(--c-celadon);
        }
        .alert-box.error {
            background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5;
        }
    </style>
</head>

<body>
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <a href="<?php echo htmlspecialchars($base_url); ?>/index.php" class="header-back header-back--sidebar" data-history-back aria-label="Go to the previous page">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M19 12H5m7 7-7-7 7-7"></path>
            </svg>
        </a>

        <a href="dashboard.php" class="sidebar-brand">
            <div class="brand-emblem">
                <img class="brand-emblem-image" src="<?php echo htmlspecialchars($base_url); ?>/images/brand/reclaim-icon.png" alt="">
            </div>
            <div>
                <span class="brand-title">RE:CLAIM</span>
                <span class="brand-sub">CONTROL ROOM</span>
            </div>
        </a>

        <ul class="sidebar-links">
            <li class="nav-item">
                <a href="dashboard.php">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_users.php">
                    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    <span>User Management</span>
                    <span class="nav-pill-badge"><?php echo $total_users; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_items.php">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Report Management</span>
                    <span class="nav-pill-badge"><?php echo $total_pending_items > 0 ? $total_pending_items : $total_items; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_claims.php">
                    <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span>Claim Management</span>
                    <span class="nav-pill-badge"><?php echo $total_claims; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_categories.php" class="is-active" aria-current="page">
                    <svg viewBox="0 0 24 24"><path d="M4 9h16M4 15h16M10 3v18M14 3v18"/></svg>
                    <span>Category Management</span>
                    <span class="nav-pill-badge"><?php echo count($categories); ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a href="dashboard.php#analytics-section">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <span>Reports &amp; Statistics</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../index.php">
                    <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>Visit Website</span>
                </a>
            </li>
        </ul>

        <div style="padding-top: 18px; border-top: 1px solid rgba(184, 186, 255, 0.14);">
            <a href="../logout.php" style="display: block; text-align: center; padding: 10px; border-radius: 10px; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; text-decoration: none; font-weight: 600; font-size: 13px;">Sign Out</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="control-main">
        <header class="hud-header">
            <div>
                <h1>Category Taxonomy Console</h1>
                <p style="color: var(--c-columbia); opacity: 0.8; font-size: 13px; margin-top: 3px;">Configure campus item categories, tags, and classification quotas</p>
            </div>
            <a href="dashboard.php" style="display: inline-flex; align-items: center; gap: 8px; color: var(--c-columbia); text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 14px; border-radius: 10px; background: rgba(102, 102, 255, 0.15); border: 1px solid rgba(184, 186, 255, 0.25);">
                &larr; Return to Dashboard
            </a>
        </header>

        <?php if (!empty($message)) { ?>
            <div class="alert-box <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <!-- Add Category Form Panel -->
        <div class="glass-panel">
            <h3 style="font-family: var(--font-heading); font-size: 17px; color: #ffffff;">Create New Item Category</h3>
            <p style="color: var(--c-columbia); opacity: 0.8; font-size: 13px; margin-top: 4px;">Define a new item classification available to students when reporting lost or found goods.</p>

            <form method="POST" action="manage_categories.php" class="form-glass-row">
                <input type="hidden" name="action" value="add">
                <input type="text" name="category_name" class="glass-input" placeholder="e.g. Headphones, Water Bottle, Umbrella..." required maxlength="100">
                <button type="submit" class="btn-submit-glass">+ Add Category</button>
            </form>
        </div>

        <!-- Categories List Grid -->
        <div class="glass-panel">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-family: var(--font-heading); font-size: 17px; color: #ffffff;">Existing Categories (<?php echo count($categories); ?>)</h3>
            </div>

            <div class="category-grid-cards">
                <?php foreach ($categories as $cat) { ?>
                    <div class="cat-card-glass">
                        <div>
                            <div class="cat-card-name"><?php echo htmlspecialchars($cat["category_name"]); ?></div>
                            <span class="cat-card-badge"><?php echo $cat["item_count"]; ?> items registered</span>
                        </div>
                        <?php if ((int)$cat["item_count"] === 0) { ?>
                            <form method="POST" action="manage_categories.php" onsubmit="return confirm('Remove category <?php echo htmlspecialchars($cat['category_name']); ?>?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="category_id" value="<?php echo $cat["category_id"]; ?>">
                                <button type="submit" class="btn-del-cat">Delete</button>
                            </form>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>
</body>
</html>
