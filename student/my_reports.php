
<?php

require_once "../includes/auth.php";
checkStudent();

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];
$message = "";

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["delete_id"])) {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"] ?? "", $_POST["csrf_token"])
    ) {
        $message = "We could not verify that request. Please try again.";

    } else {

        $delete_id = filter_input(
            INPUT_POST,
            "delete_id",
            FILTER_VALIDATE_INT
        );

        if ($delete_id) {

            // Only allow deleting your own pending or rejected reports
            $delete_sql = "DELETE FROM items
                           WHERE item_id = ?
                           AND user_id = ?
                           AND status IN ('pending', 'rejected')";

            $stmt = mysqli_prepare($conn, $delete_sql);

            mysqli_stmt_bind_param(
                $stmt,
                "ii",
                $delete_id,
                $user_id
            );

            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $message = "Your report has been removed.";
            } else {
                $message = "This report could not be removed.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Fetch student's reports

// Get logged-in student ID
$user_id = (int) $_SESSION["user_id"];

// Prepare SQL query
$sql = "SELECT
            items.*,
            categories.category_name
        FROM items
        LEFT JOIN categories
        ON items.category_id = categories.category_id
        WHERE items.user_id = ?
        ORDER BY items.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);

// Check query preparation
if (!$stmt) {
    die("SQL Preparation Error: " . mysqli_error($conn));
}

// Bind user ID
mysqli_stmt_bind_param($stmt, "i", $user_id);

// Execute query
if (!mysqli_stmt_execute($stmt)) {
    die("SQL Execution Error: " . mysqli_stmt_error($stmt));
}

// Get results
$result = mysqli_stmt_get_result($stmt);

// Debug information (temporary)
//echo "<p>Logged-in User ID: " . $user_id . "</p>";
//echo "<p>Total Reports: " . mysqli_num_rows($result) . "</p>";

?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<style>

/* =====================================
   MY REPORTS - LIGHT MODE DEFAULT
===================================== */

.reports-container {
    --reports-bg: #ffffff;
    --reports-text: #172554;
    --reports-muted: #475569;
    --reports-border: #e2e8f0;
    --reports-card-shadow: rgba(0,0,0,0.07);

    max-width: 1100px;
    margin: 40px auto;
    padding: 20px;

    color: var(--reports-text);
}

.reports-container > h2 {
    color: var(--reports-text);
    font-size: 28px;
    margin-bottom: 10px;
}

.reports-container > p {
    color: var(--reports-muted);
    margin-bottom: 25px;
}

.report-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 22px;
    margin-top: 25px;
}

.report-card {
    background: var(--reports-bg);
    padding: 22px;
    border-radius: 12px;
    border: 1px solid var(--reports-border);
    box-shadow: 0 4px 15px var(--reports-card-shadow);
    transition: 0.3s;
}

.report-card:hover {
    transform: translateY(-4px);
}

.report-card h3 {
    color: var(--reports-text);
    font-size: 21px;
    margin-bottom: 15px;
}

.report-card p {
    color: var(--reports-muted);
    margin: 10px 0;
    font-size: 14px;
    line-height: 1.65;
}

.report-card strong {
    color: var(--reports-text);
    font-weight: 800;
}

/* =====================================
   STATUS BADGE
===================================== */

.status-badge {
    display: inline-block;
    padding: 5px 11px;
    border-radius: 20px;
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: bold;
    font-size: 12px;
}

/* =====================================
   REPORT IMAGE
===================================== */

.report-item-image {
    display: block;
    width: 100%;
    height: 190px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 17px;
}

.report-image-placeholder {
    height: 190px;
    background: #eff6ff;
    color: #475569;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    margin-bottom: 17px;
    font-weight: 700;
}

/* =====================================
   VIEW AND DELETE BUTTONS
===================================== */

.view-btn,
.delete-btn {
    display: block;
    width: 100%;
    box-sizing: border-box;
    text-align: center;
    padding: 11px;
    border: none;
    border-radius: 7px;
    text-decoration: none;
    margin-top: 13px;
    font-size: 14px;
    cursor: pointer;
}

.view-btn {
    background: #2563eb;
    color: white;
}

.view-btn:hover {
    background: #1d4ed8;
}

.delete-btn {
    background: #fee2e2;
    color: #b91c1c;
    font-weight: bold;
}

.delete-btn:hover {
    background: #fecaca;
}

/* =====================================
   BACK BUTTON
===================================== */

.back-link {
    display: inline-block;
    margin-top: 30px;
    padding: 11px 22px;
    background: linear-gradient(110deg, #6666FF, #5050df);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 700;
    box-shadow: 0 4px 14px rgba(102, 102, 255, 0.35);
    transition: transform 160ms ease, box-shadow 160ms ease, filter 160ms ease;
}

.back-link:hover {
    filter: brightness(1.12);
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(102, 102, 255, 0.5);
}

/* =====================================
   SUCCESS MESSAGE
===================================== */

.success-message {
    background: #dcfce7;
    color: #15803d;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 18px;
    border: 1px solid #bbf7d0;
    font-weight: 600;
}


/* =====================================
   MY REPORTS INTRO PRESENTATION BOX
===================================== */

.reports-intro-box {
    position: relative;
    overflow: hidden;

    padding: 30px 32px;

    margin-bottom: 27px;

    border-radius: 22px;

    background: linear-gradient(
        120deg,
        #d9fff6 0%,
        #dceeff 48%,
        #eee4ff 100%
    );

    border: 1px solid rgba(74, 157, 190, 0.25);

    box-shadow:
        0 12px 30px rgba(52, 119, 174, 0.12),
        inset 0 1px 0 rgba(255,255,255,0.7);

    animation: introAppear 0.8s ease both;

    transition:
        transform 0.35s ease,
        box-shadow 0.35s ease;
}

.reports-intro-box:hover {
    transform: translateY(-4px);

    box-shadow:
        0 18px 38px rgba(52, 119, 174, 0.18),
        inset 0 1px 0 rgba(255,255,255,0.8);
}

/* Decorative glow */

.reports-intro-box::before {
    content: "";

    position: absolute;

    width: 210px;
    height: 210px;

    top: -115px;
    right: -45px;

    border-radius: 50%;

    background: rgba(255,255,255,0.55);

    pointer-events: none;
}

.reports-intro-box::after {
    content: "";

    position: absolute;

    width: 130px;
    height: 130px;

    bottom: -80px;
    left: 35%;

    border-radius: 50%;

    background: rgba(65, 190, 185, 0.13);

    pointer-events: none;
}

/* Intro content */

.reports-intro-content {
    position: relative;
    z-index: 2;
}

/* Small label */

.reports-intro-label {
    display: inline-block;

    padding: 7px 13px;

    margin-bottom: 13px;

    border-radius: 30px;

    background: rgba(255,255,255,0.65);

    color: #087b83;

    font-size: 11px;

    font-weight: 900;

    letter-spacing: 2px;
}

/* Main heading */

.reports-intro-box h2 {
    color: #173c59 !important;

    font-size: clamp(27px, 4vw, 36px);

    font-weight: 900;

    letter-spacing: -0.7px;

    margin: 0 0 10px;

    line-height: 1.25;
}

/* Description */

.reports-intro-box p {
    color: #3b596e !important;

    font-size: 15px;

    font-weight: 600;

    line-height: 1.75;

    max-width: 650px;

    margin: 0 !important;
}

/* Intro animation */

@keyframes introAppear {
    from {
        opacity: 0;
        transform: translateY(18px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}


/* =====================================
   DARK MODE
===================================== */

body.dark-mode .reports-container,
html.dark .reports-container,
[data-theme="dark"] .reports-container,
.dark-mode .reports-container {

    --reports-bg: #172b3d;
    --reports-text: #f1f5f9;
    --reports-muted: #cbd5e1;
    --reports-border: #344b60;
    --reports-card-shadow: rgba(0,0,0,0.22);

    color: #f1f5f9;
}

/* Dark mode intro box */

body.dark-mode .reports-intro-box,
html.dark .reports-intro-box,
[data-theme="dark"] .reports-intro-box,
.dark-mode .reports-intro-box {

    background: linear-gradient(
        120deg,
        #123e4c 0%,
        #1d355d 50%,
        #392c60 100%
    );

    border-color: #385c79;

    box-shadow:
        0 12px 30px rgba(0,0,0,0.22),
        inset 0 1px 0 rgba(255,255,255,0.05);
}

body.dark-mode .reports-intro-box:hover,
html.dark .reports-intro-box:hover,
[data-theme="dark"] .reports-intro-box:hover,
.dark-mode .reports-intro-box:hover {

    box-shadow:
        0 18px 38px rgba(0,0,0,0.32),
        inset 0 1px 0 rgba(255,255,255,0.08);
}

body.dark-mode .reports-intro-box::before,
html.dark .reports-intro-box::before,
[data-theme="dark"] .reports-intro-box::before,
.dark-mode .reports-intro-box::before {

    background: rgba(255,255,255,0.06);
}

body.dark-mode .reports-intro-box::after,
html.dark .reports-intro-box::after,
[data-theme="dark"] .reports-intro-box::after,
.dark-mode .reports-intro-box::after {

    background: rgba(100,255,230,0.08);
}

/* Dark mode intro label */

body.dark-mode .reports-intro-label,
html.dark .reports-intro-label,
[data-theme="dark"] .reports-intro-label,
.dark-mode .reports-intro-label {

    background: rgba(255,255,255,0.10);

    color: #8fffe8 !important;

    border: 1px solid rgba(143,255,232,0.18);
}

/* Dark mode heading */

body.dark-mode .reports-intro-box h2,
html.dark .reports-intro-box h2,
[data-theme="dark"] .reports-intro-box h2,
.dark-mode .reports-intro-box h2 {

    color: #f0fffd !important;
}

/* Dark mode description */

body.dark-mode .reports-intro-box p,
html.dark .reports-intro-box p,
[data-theme="dark"] .reports-intro-box p,
.dark-mode .reports-intro-box p {

    color: #d2e8f0 !important;
}

/* Dark mode page heading */

body.dark-mode .reports-container > h2,
html.dark .reports-container > h2,
[data-theme="dark"] .reports-container > h2,
.dark-mode .reports-container > h2 {
    color: #f8fafc !important;
}

/* Dark mode page description */

body.dark-mode .reports-container > p,
html.dark .reports-container > p,
[data-theme="dark"] .reports-container > p,
.dark-mode .reports-container > p {
    color: #cbd5e1 !important;
}

/* Dark mode report cards */

body.dark-mode .report-card,
html.dark .report-card,
[data-theme="dark"] .report-card,
.dark-mode .report-card {
    background: #172b3d !important;
    border-color: #344b60 !important;
    box-shadow: 0 5px 18px rgba(0,0,0,0.22);
}

/* Dark mode item name */

body.dark-mode .report-card h3,
html.dark .report-card h3,
[data-theme="dark"] .report-card h3,
.dark-mode .report-card h3 {
    color: #f8fafc !important;
}

/* Dark mode report information */

body.dark-mode .report-card p,
html.dark .report-card p,
[data-theme="dark"] .report-card p,
.dark-mode .report-card p {
    color: #cbd5e1 !important;
}

/* Dark mode labels */

body.dark-mode .report-card strong,
html.dark .report-card strong,
[data-theme="dark"] .report-card strong,
.dark-mode .report-card strong {
    color: #f1f5f9 !important;
}

/* Dark mode status badge */

body.dark-mode .status-badge,
html.dark .status-badge,
[data-theme="dark"] .status-badge,
.dark-mode .status-badge {
    background: #1e3a5f;
    color: #bfdbfe;
}

/* Dark mode image placeholder */

body.dark-mode .report-image-placeholder,
html.dark .report-image-placeholder,
[data-theme="dark"] .report-image-placeholder,
.dark-mode .report-image-placeholder {
    background: #243b50;
    color: #e2e8f0;
}

/* Dark mode delete button */

body.dark-mode .delete-btn,
html.dark .delete-btn,
[data-theme="dark"] .delete-btn,
.dark-mode .delete-btn {
    background: #512b35;
    color: #fecdd3;
}

body.dark-mode .delete-btn:hover,
html.dark .delete-btn:hover,
[data-theme="dark"] .delete-btn:hover {
    background: #713744;
}

/* Dark mode success message */

body.dark-mode .success-message,
html.dark .success-message,
[data-theme="dark"] .success-message,
.dark-mode .success-message {
    background: #164333;
    color: #bbf7d0;
    border-color: #28694e;
}


/* =====================================
   RESPONSIVE DESIGN
===================================== */

@media (max-width: 600px) {

    .reports-container {
        margin: 22px auto;
        padding: 14px;
    }

    .reports-intro-box {
        padding: 24px 20px;

        border-radius: 18px;
    }

    .reports-intro-box h2 {
        font-size: 27px;
    }

    .reports-intro-box p {
        font-size: 14px;
    }

    .reports-container > h2 {
        font-size: 25px;
    }

    .report-list {
        grid-template-columns: 1fr;
        gap: 17px;
    }

}

/* Respect reduced-motion preferences */

@media (prefers-reduced-motion: reduce) {

    .reports-intro-box {
        animation: none;
        transition: none;
    }

    .reports-intro-box:hover {
        transform: none;
    }

    .report-card {
        transition: none;
    }

}

</style>


<div class="reports-container">

    <!-- MY REPORTS PRESENTATION BOX -->

    <div class="reports-intro-box">

        <div class="reports-intro-content">

            <span class="reports-intro-label">
                RE:CLAIM • YOUR ACTIVITY
            </span>

            <h2>My Reports</h2>

            <p>
                View all your submitted lost and found reports.
                Keep track of your reports and manage your activity
                from one place.
            </p>

        </div>

    </div>


    <?php if (!empty($message)) { ?>

        <div class="success-message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php } ?>


    <div class="report-list">

        <?php if (mysqli_num_rows($result) > 0) { ?>

            <?php while ($item = mysqli_fetch_assoc($result)) { ?>

                <div class="report-card">

                    <?php if (!empty($item["image"])) { ?>

                        <img
                            src="../uploads/items/<?php
                            echo rawurlencode(basename($item["image"]));
                            ?>"
                            alt="Image of <?php echo htmlspecialchars($item["item_name"]); ?>"
                            class="report-item-image image-skeleton"
                            data-loading-skeleton
                            loading="lazy"
                            decoding="async"
                        >

                    <?php } else { ?>

                        <div class="no-image report-image-placeholder">
                            No Image Available
                        </div>

                    <?php } ?>

                    <h3>
                        <?php echo htmlspecialchars($item["item_name"]); ?>
                    </h3>

                    <p>
                        <strong>Category:</strong>
                        <?php
                        echo htmlspecialchars($item["category_name"] ?? "Uncategorized");
                        ?>
                    </p>

                    <p>
                        <strong>Type:</strong>
                        <?php echo ucfirst(htmlspecialchars($item["item_type"])); ?>
                    </p>

                    <p>
                        <strong>Location:</strong>
                        <?php echo htmlspecialchars($item["location"]); ?>
                    </p>

                    <p>
                        <strong>Date:</strong>
                        <?php echo htmlspecialchars($item["item_date"]); ?>
                    </p>

                    <p>
                        <strong>Status:</strong>

                        <span class="status-badge">
                            <?php echo ucfirst(htmlspecialchars($item["status"])); ?>
                        </span>
                    </p>

                    <a
                        href="item_details.php?id=<?php
                        echo (int)$item["item_id"];
                        ?>"
                        class="view-btn"
                    >
                        View Details
                    </a>

                    <?php if (
                        in_array($item["status"], ["pending", "rejected"])
                    ) { ?>

                        <form method="POST"
                              onsubmit="return confirm('Are you sure you want to delete this report?');">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
                            >

                            <input
                                type="hidden"
                                name="delete_id"
                                value="<?php echo (int)$item["item_id"]; ?>"
                            >

                            <button type="submit" class="delete-btn">
                                Delete Report
                            </button>

                        </form>

                    <?php } ?>

                </div>

            <?php } ?>

        <?php } else { ?>

            <p>You have not submitted any reports yet.</p>

        <?php } ?>

    </div>

    <a href="dashboard.php" class="back-link">
        Back to Dashboard
    </a>

</div>

<?php
mysqli_stmt_close($stmt);
include "../includes/footer.php";
?>
