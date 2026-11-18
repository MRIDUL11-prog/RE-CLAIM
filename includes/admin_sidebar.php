<?php
// Shared, always-visible admin sidebar (used by admin subpages).
$admin_nav_current = basename($_SERVER["SCRIPT_NAME"]);
$admin_nav_base = isset($base_url) ? $base_url : "";
$admin_nav_links = [
    ["admin/dashboard.php", "⎔", "Overview", "dashboard.php"],
    ["admin/manage_items.php", "◈", "Item Reports", "manage_items.php"],
    ["admin/manage_users.php", "👥", "Accounts", "manage_users.php"],
    ["admin/manage_claims.php", "⚖", "Claims", "manage_claims.php"],
    ["index.php", "↗", "Visit Website", null],
    ["help.php", "?", "Help & Support", null],
    ["feedback.php", "✦", "Feedback", null],
];
?>
<style>
    body.admin-has-sidebar { padding-left: 260px !important; }

    .reclaim-admin-nav {
        position: fixed; top: 0; left: 0; bottom: 0; width: 260px;
        padding: 28px 20px; overflow-y: auto; z-index: 1000;
        background: rgba(12, 12, 22, 0.96);
        border-right: 1px solid rgba(201, 232, 255, 0.16);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.6);
        font-family: "DM Sans", -apple-system, BlinkMacSystemFont, sans-serif;
        color: #F5F5FF;
    }
    .reclaim-admin-nav .ran-brand { display: block; text-align: center; margin-bottom: 36px; text-decoration: none; }
    .reclaim-admin-nav .ran-brand img { max-width: 100%; height: auto; display: block; margin: 0 auto 8px; }
    .reclaim-admin-nav .ran-brand .brand-logo-light { display: none; }
    .reclaim-admin-nav .ran-brand span {
        display: block; font-size: 0.8rem; font-weight: 800; letter-spacing: 0.14em;
        text-transform: uppercase; color: #F5F5FF;
    }
    .reclaim-admin-nav a.ran-link {
        display: flex; align-items: center; gap: 12px; padding: 12px 16px; margin-bottom: 8px;
        border-radius: 12px; border: 1px solid transparent; text-decoration: none;
        color: #B8BAFF; font-weight: 600; font-size: 0.92rem; transition: all 0.2s ease;
    }
    .reclaim-admin-nav a.ran-link:hover {
        color: #F5F5FF; background: rgba(102, 102, 255, 0.16); border-color: rgba(184, 186, 255, 0.32);
    }
    .reclaim-admin-nav a.ran-link.is-active {
        color: #F5F5FF;
        background: linear-gradient(135deg, rgba(102, 102, 255, 0.32), rgba(184, 186, 255, 0.16));
        border-color: #6666FF; box-shadow: 0 0 22px rgba(102, 102, 255, 0.35);
    }
    .reclaim-admin-nav a.ran-logout {
        display: flex; justify-content: center; margin-top: 36px; padding: 12px 16px;
        border-radius: 12px; text-decoration: none; font-weight: 800;
        background: linear-gradient(135deg, #6666FF, #B8BAFF); color: #0C0C16;
        border: 1px solid rgba(201, 232, 255, 0.4); box-shadow: 0 6px 20px rgba(102, 102, 255, 0.3);
    }

    html[data-theme="light"] .reclaim-admin-nav {
        background: rgba(255, 255, 255, 0.97); border-right-color: rgba(0, 0, 0, 0.08);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); color: #0C0C16;
    }
    html[data-theme="light"] .reclaim-admin-nav .ran-brand span { color: #0C0C16; }
    html[data-theme="light"] .reclaim-admin-nav .ran-brand .brand-logo-dark { display: none; }
    html[data-theme="light"] .reclaim-admin-nav .ran-brand .brand-logo-light { display: block; }
    html[data-theme="light"] .reclaim-admin-nav a.ran-link { color: #2F2A56; }
    html[data-theme="light"] .reclaim-admin-nav a.ran-link:hover { color: #0C0C16; background: rgba(102, 102, 255, 0.1); }
    html[data-theme="light"] .reclaim-admin-nav a.ran-link.is-active { color: #0C0C16; }

    @media (max-width: 860px) {
        body.admin-has-sidebar { padding-left: 0 !important; }
        .reclaim-admin-nav { position: static; width: auto; bottom: auto; }
    }
</style>
<script>document.body.classList.add("admin-has-sidebar");</script>

<nav class="reclaim-admin-nav" aria-label="Admin navigation">
    <a href="<?php echo htmlspecialchars($admin_nav_base); ?>/admin/dashboard.php" class="ran-brand">
        <img class="brand-logo-dark" src="<?php echo htmlspecialchars($admin_nav_base); ?>/images/brand/reclaim-logo-dark.png" alt="RE:CLAIM - You lost we find">
        <img class="brand-logo-light" src="<?php echo htmlspecialchars($admin_nav_base); ?>/images/brand/reclaim-logo-light.png" alt="">
        <span>Control room</span>
    </a>

    <?php foreach ($admin_nav_links as $link) { ?>
        <?php $is_active = $link[3] !== null && $admin_nav_current === $link[3]; ?>
        <a class="ran-link<?php echo $is_active ? " is-active" : ""; ?>"
           href="<?php echo htmlspecialchars($admin_nav_base . "/" . $link[0]); ?>"
           <?php echo $is_active ? 'aria-current="page"' : ""; ?>>
            <span aria-hidden="true"><?php echo $link[1]; ?></span> <?php echo htmlspecialchars($link[2]); ?>
        </a>
    <?php } ?>

    <a href="<?php echo htmlspecialchars($admin_nav_base); ?>/logout.php" class="ran-logout">Sign Out</a>
</nav>
