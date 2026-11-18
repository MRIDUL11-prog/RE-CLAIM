
<?php

require_once __DIR__ . "/session.php";

$current_page = basename(parse_url($_SERVER["SCRIPT_NAME"], PHP_URL_PATH));

$show_header_back = !in_array($current_page, ["index.php", "dashboard.php"], true);

$active_item_pages = ["search.php", "item_details.php"];

$active_report_pages = [
    "my_reports.php",
    "report_lost.php",
    "report_found.php"
];

$nav_active = function ($pages) use ($current_page) {
    return in_array($current_page, $pages, true);
};

?>

<nav class="navbar" aria-label="Main navigation">

    <div class="navbar-brand-group">

        <?php if ($show_header_back) { ?>
            <a
                href="<?php echo $base_url; ?>/index.php"
                class="header-back"
                data-history-back
                aria-label="Go to the previous page"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path d="M19 12H5m7 7-7-7 7-7"></path>
                </svg>
            </a>
        <?php } ?>

        <!-- Website Logo -->
        <div class="logo">
            <a href="<?php echo $base_url; ?>/index.php">
                <picture class="brand-lockup">

                    <img
                        class="brand-logo-dark"
                        src="<?php echo $base_url; ?>/images/brand/reclaim-logo-dark.png"
                        alt="RE:CLAIM - You lost we find"
                    >

                    <img
                        class="brand-logo-light"
                        src="<?php echo $base_url; ?>/images/brand/reclaim-logo-light.png"
                        alt=""
                    >

                </picture>
            </a>
        </div>

    </div>

    <div class="navbar-controls">

        <button
            class="theme-toggle"
            type="button"
            aria-label="Toggle color scheme"
            aria-pressed="false"
        >
            <span class="theme-toggle-icon" aria-hidden="true">☾</span>
        </button>

        <details class="navbar-menu">

            <summary class="menu-toggle" aria-label="Toggle navigation menu">

                <span class="menu-toggle-lines" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>

            </summary>

            <div class="menu-panel">

                <ul class="nav-links">

                    <!-- Home -->
                    <li>
                        <a
                            href="<?php echo $base_url; ?>/index.php"
                            class="<?php echo $current_page === "index.php" ? "is-active" : ""; ?>"
                            <?php echo $current_page === "index.php" ? 'aria-current="page"' : ""; ?>
                        >
                            Home
                        </a>
                    </li>

                    <!-- Help -->
                    <li>
                        <a
                            href="<?php echo $base_url; ?>/help.php"
                            class="<?php echo $current_page === "help.php" ? "is-active" : ""; ?>"
                            <?php echo $current_page === "help.php" ? 'aria-current="page"' : ""; ?>
                        >
                            Help
                        </a>
                    </li>

                    <!-- Feedback -->
                    <li>
                        <a
                            href="<?php echo $base_url; ?>/feedback.php"
                            class="<?php echo $current_page === "feedback.php" ? "is-active" : ""; ?>"
                            <?php echo $current_page === "feedback.php" ? 'aria-current="page"' : ""; ?>
                        >
                            Feedback
                        </a>
                    </li>

                    <?php if (isset($_SESSION["user_id"])) { ?>

                        <?php if ($_SESSION["role"] === "admin") { ?>

                            <!-- Admin Navigation -->

                            <li>
                                <a
                                    href="<?php echo $base_url; ?>/admin/dashboard.php"
                                    class="<?php echo $current_page === "dashboard.php" ? "is-active" : ""; ?>"
                                    <?php echo $current_page === "dashboard.php" ? 'aria-current="page"' : ""; ?>
                                >
                                    Overview
                                </a>
                            </li>

                            <li>
                                <a
                                    href="<?php echo $base_url; ?>/admin/manage_items.php"
                                    class="<?php echo $current_page === "manage_items.php" ? "is-active" : ""; ?>"
                                    <?php echo $current_page === "manage_items.php" ? 'aria-current="page"' : ""; ?>
                                >
                                    Items
                                </a>
                            </li>

                            <li>
                                <a
                                    href="<?php echo $base_url; ?>/admin/manage_users.php"
                                    class="<?php echo $current_page === "manage_users.php" ? "is-active" : ""; ?>"
                                    <?php echo $current_page === "manage_users.php" ? 'aria-current="page"' : ""; ?>
                                >
                                    Accounts
                                </a>
                            </li>

                            <li>
                                <a
                                    href="<?php echo $base_url; ?>/admin/manage_claims.php"
                                    class="<?php echo $current_page === "manage_claims.php" ? "is-active" : ""; ?>"
                                    <?php echo $current_page === "manage_claims.php" ? 'aria-current="page"' : ""; ?>
                                >
                                    Claims
                                </a>
                            </li>

                        <?php } else { ?>

                            <!-- Student Navigation -->

                            <li>
                                <a
                                    href="<?php echo $base_url; ?>/student/dashboard.php"
                                    class="<?php echo $current_page === "dashboard.php" ? "is-active" : ""; ?>"
                                    <?php echo $current_page === "dashboard.php" ? 'aria-current="page"' : ""; ?>
                                >
                                    Overview
                                </a>
                            </li>

                            <li>
                                <a
                                    href="<?php echo $base_url; ?>/student/search.php"
                                    class="<?php echo $nav_active($active_item_pages) ? "is-active" : ""; ?>"
                                    <?php echo $nav_active($active_item_pages) ? 'aria-current="page"' : ""; ?>
                                >
                                    Find items
                                </a>
                            </li>

                            <li>
                                <a
                                    href="<?php echo $base_url; ?>/student/my_reports.php"
                                    class="<?php echo $nav_active($active_report_pages) ? "is-active" : ""; ?>"
                                    <?php echo $nav_active($active_report_pages) ? 'aria-current="page"' : ""; ?>
                                >
                                    My reports
                                </a>
                            </li>

                        <?php } ?>

                    <?php } ?>

                </ul>

                <!-- Authentication Actions -->

                <div class="navbar-actions">

                    <?php if (isset($_SESSION["user_id"])) { ?>

                        <?php if ($_SESSION["role"] === "student") { ?>

                            <a
                                href="<?php echo $base_url; ?>/student/profile.php"
                                class="profile-action <?php echo $current_page === "profile.php" ? "is-active" : ""; ?>"
                                <?php echo $current_page === "profile.php" ? 'aria-current="page"' : ""; ?>
                            >
                                <span class="profile-avatar" aria-hidden="true">
                                    <?php echo htmlspecialchars(strtoupper(substr($_SESSION["name"], 0, 1))); ?>
                                </span>

                                <span>My profile</span>
                            </a>

                        <?php } ?>

                        <a
                            href="<?php echo $base_url; ?>/logout.php"
                            class="auth-action sign-out"
                        >
                            Sign out
                        </a>

                    <?php } else { ?>

                        <!-- Sign In -->
                        <a
                            href="<?php echo $base_url; ?>/login.php"
                            class="auth-action sign-in <?php echo $current_page === "login.php" ? "is-active" : ""; ?>"
                            <?php echo $current_page === "login.php" ? 'aria-current="page"' : ""; ?>
                        >
                            Sign in
                        </a>

                        <!-- Create Account: No default active class -->
                        <a
                            href="<?php echo $base_url; ?>/register.php"
                            class="auth-action create-account"
                        >
                            Create account
                        </a>

                    <?php } ?>

                </div>

            </div>

        </details>

    </div>

</nav>
