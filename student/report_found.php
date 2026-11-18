
<?php

require_once "../includes/auth.php";
checkStudent();

require_once "../config/database.php";
require_once "../includes/mailer.php";
require_once "../includes/item_image.php";

$errors = [];
$success = "";
$mail_warning = "";

$item_name = "";
$category_id = "";
$description = "";
$location = "";
$item_date = "";

// Fetch categories
$category_sql = "SELECT * FROM categories ORDER BY category_name";
$category_result = mysqli_query($conn, $category_sql);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $item_name = trim($_POST["item_name"] ?? "");
    $category_id = $_POST["category_id"] ?? "";
    $description = trim($_POST["description"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $item_date = $_POST["item_date"] ?? "";

    // Validate required fields
    if (
        empty($item_name) ||
        empty($category_id) ||
        empty($description) ||
        empty($location) ||
        empty($item_date)
    ) {
        $errors[] = "All fields are required.";
    }

    // Validate category
    if (!empty($category_id)) {

        $check = mysqli_prepare(
            $conn,
            "SELECT category_id FROM categories WHERE category_id = ?"
        );

        mysqli_stmt_bind_param($check, "i", $category_id);
        mysqli_stmt_execute($check);

        $check_result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($check_result) == 0) {
            $errors[] = "Please select a valid category.";
        }

        mysqli_stmt_close($check);
    }

    // Validate found date
    $date_object = DateTime::createFromFormat('Y-m-d', $item_date);

    if (
        !$date_object ||
        $date_object->format('Y-m-d') !== $item_date ||
        $item_date > date('Y-m-d')
    ) {
        $errors[] = "Please enter a valid found date.";
    }

    // Validate and store optional image
    $image_name = null;

    if (empty($errors)) {
        $image_name = catchit_save_item_image_upload(
            $_FILES["item_image"] ?? null,
            $errors
        );
    }

    // Save found item
    if (empty($errors)) {

        $user_id = $_SESSION["user_id"];

        $sql = "INSERT INTO items
        (
            user_id,
            category_id,
            item_name,
            description,
            item_type,
            location,
            item_date,
            image
        )
        VALUES (?, ?, ?, ?, 'found', ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "iisssss",
            $user_id,
            $category_id,
            $item_name,
            $description,
            $location,
            $item_date,
            $image_name
        );

        if (mysqli_stmt_execute($stmt)) {

            $report_query = mysqli_prepare(
                $conn,
                "SELECT users.name, users.email, categories.category_name
                 FROM users
                 INNER JOIN categories ON categories.category_id = ?
                 WHERE users.user_id = ?"
            );

            mysqli_stmt_bind_param(
                $report_query,
                "ii",
                $category_id,
                $user_id
            );

            mysqli_stmt_execute($report_query);

            $report_result = mysqli_stmt_get_result($report_query);
            $report_user = mysqli_fetch_assoc($report_result);

            mysqli_stmt_close($report_query);

            $mail_results = [
                "student" => false,
                "admin" => false
            ];

            if ($report_user) {

                $mail_results = catchit_send_report_emails([
                    "student_name" => $report_user["name"],
                    "student_email" => $report_user["email"],
                    "category_name" => $report_user["category_name"],
                    "item_name" => $item_name,
                    "location" => $location,
                    "item_type" => "found"
                ]);

            } else {
                error_log("CatchIt email: report confirmation details could not be loaded.");
            }

            $success = "Your found-item report has been submitted for review.";

            if (!$mail_results["student"] || !$mail_results["admin"]) {
                $mail_warning = " The report was saved, but one or more notification emails could not be delivered.";
            }

            $item_name = "";
            $category_id = "";
            $description = "";
            $location = "";
            $item_date = "";

        } else {

            if ($image_name !== null) {

                $image_path = dirname(__DIR__) . "/uploads/items/" . $image_name;

                if (is_file($image_path)) {
                    unlink($image_path);
                }
            }

            $errors[] = "We could not submit your report. Please try again.";
        }

        mysqli_stmt_close($stmt);
    }
}

?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>


<style>

/* ===============================
   MAIN PAGE
================================ */

.found-page {
    max-width: 1200px;
    margin: 35px auto;
    padding: 20px;
}

.found-layout {
    display: grid;
    grid-template-columns: 0.95fr 1.05fr;
    gap: 30px;
    align-items: stretch;
}


/* ===============================
   LEFT BRAND PANEL - LIGHT MODE
================================ */

.found-brand-panel {
    position: relative;
    overflow: hidden;
    padding: 55px 42px;
    border-radius: 28px;

    background: linear-gradient(
        145deg,
        #b8fff1,
        #78e5dc,
        #36bfc0
    );

    color: #102b3a;

    box-shadow: 0 18px 45px rgba(24, 145, 145, 0.18);

    display: flex;
    flex-direction: column;
    justify-content: center;

    min-height: 720px;
}

.found-brand-panel::before {
    content: "";
    position: absolute;

    width: 300px;
    height: 300px;

    top: -130px;
    right: -100px;

    border-radius: 50%;

    background: rgba(255,255,255,0.25);
}

.found-brand-panel::after {
    content: "";
    position: absolute;

    width: 240px;
    height: 240px;

    bottom: -100px;
    left: -90px;

    border-radius: 50%;

    background: rgba(255,255,255,0.18);
}

.found-brand-content {
    position: relative;
    z-index: 2;
}


/* ===============================
   BRAND EYEBROW
================================ */

.found-eyebrow {
    display: inline-block;

    font-size: 13px;
    font-weight: 800;
    letter-spacing: 3px;

    color: #075c61 !important;

    margin-bottom: 25px;
}


/* ===============================
   MAIN HEADING
================================ */

.found-main-heading {
    font-size: clamp(38px, 4vw, 55px);
    line-height: 1.12;

    font-weight: 900;
    letter-spacing: -2px;

    color: #102b3a !important;

    margin: 0 0 22px;
}

/* Found Something - single line */

.found-main-heading .found-heading-line {
    display: block;

    white-space: nowrap;

    font-size: clamp(30px, 3.5vw, 43px);

    letter-spacing: -1.5px;

    color: #102b3a !important;

    margin-bottom: 5px;
}

/* Give It Back - second line */

.found-main-heading > span:not(.found-heading-line) {
    display: block;
    color: #087b78 !important;
}


/* ===============================
   DESCRIPTION
================================ */

.found-description {
    font-size: 17px;
    line-height: 1.8;

    font-weight: 600;

    color: #244653 !important;

    max-width: 470px;

    margin-bottom: 35px;
}


/* ===============================
   TYPOGRAPHY SHOWCASE
================================ */

.found-brand-showcase {
    padding: 32px 25px;

    margin: 15px 0 35px;

    border: 1px solid rgba(255,255,255,0.55);

    border-radius: 22px;

    background: rgba(255,255,255,0.28);

    backdrop-filter: blur(14px);

    text-align: center;

    box-shadow: 0 12px 30px rgba(13, 87, 91, 0.10);
}

.found-showcase-title {
    font-size: 28px;
    line-height: 1.3;

    font-weight: 900;

    color: #123b49 !important;

    margin: 0 0 12px;
}

.found-showcase-text {
    font-size: 15px;
    line-height: 1.7;

    font-weight: 600;

    color: #315663 !important;

    margin: 0 0 22px;
}

.found-brand-name {
    font-size: 36px;

    font-weight: 900;
    letter-spacing: 5px;

    color: #075c61 !important;

    margin: 0;
}

.found-brand-tagline {
    font-size: 11px;

    font-weight: 900;
    letter-spacing: 4px;

    color: #24535d !important;

    margin-top: 9px;
}


/* ===============================
   LEFT INFORMATION CARDS
================================ */

.found-info-list {
    display: grid;
    gap: 13px;
}

.found-info-card {
    padding: 17px 20px;

    border-radius: 15px;

    background: rgba(255,255,255,0.30);

    border: 1px solid rgba(255,255,255,0.48);

    transition: 0.3s ease;
}

.found-info-card:hover {
    transform: translateX(7px);

    background: rgba(255,255,255,0.48);
}

.found-info-card h4 {
    font-size: 16px;
    font-weight: 800;

    color: #123b49 !important;

    margin: 0 0 5px;
}

.found-info-card p {
    font-size: 13px;
    line-height: 1.6;

    color: #315663 !important;

    margin: 0;
}


/* ===============================
   RIGHT FORM PANEL
================================ */

.found-form-panel {
    padding: 42px;

    border-radius: 28px;

    background: var(--card-bg, #ffffff);

    border: 1px solid var(--border-color, #e5e7eb);

    box-shadow: 0 18px 45px rgba(0,0,0,0.07);
}

.found-form-heading {
    margin-bottom: 28px;
}

.found-form-heading span {
    display: inline-block;

    font-size: 12px;
    font-weight: 800;

    letter-spacing: 2px;

    color: #0d9b91;

    margin-bottom: 12px;
}

.found-form-heading h2 {
    font-size: 32px;
    font-weight: 900;

    color: var(--text-color, #172b3a);

    margin: 0 0 10px;
}

.found-form-heading p {
    font-size: 15px;
    line-height: 1.7;

    color: var(--muted-text, #64748b);

    margin: 0;
}


/* ===============================
   FORM ELEMENTS
================================ */

.found-form-panel form label {
    display: block;

    margin: 20px 0 9px;

    font-size: 14px;
    font-weight: 800;

    color: var(--text-color, #243746);
}

.found-form-panel input,
.found-form-panel select,
.found-form-panel textarea {
    width: 100%;
    box-sizing: border-box;

    padding: 15px 17px;

    border-radius: 12px;

    border: 1px solid var(--border-color, #dce3e9);

    background: var(--input-bg, #f8fafc);

    color: var(--text-color, #243746);

    font-size: 15px;
    font-family: inherit;

    outline: none;

    transition: 0.25s ease;
}

.found-form-panel textarea {
    resize: vertical;
    min-height: 125px;
}

.found-form-panel input:focus,
.found-form-panel select:focus,
.found-form-panel textarea:focus {
    border-color: #13aaa0;

    box-shadow: 0 0 0 4px rgba(19,170,160,0.13);

    background: var(--card-bg, #ffffff);
}

.found-form-panel input::placeholder,
.found-form-panel textarea::placeholder {
    color: #94a3b8;
}

.found-form-panel input[type="file"] {
    padding: 12px;
    cursor: pointer;
}

.found-form-panel input[type="file"]::file-selector-button {
    margin-right: 12px;
    padding: 9px 14px;
    border: 0;
    border-radius: 9px;
    background: linear-gradient(135deg, #6666ff, #4f4fd8);
    box-shadow: 0 5px 13px rgba(102, 102, 255, 0.22);
    color: #fff;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
    transition: background 180ms ease, box-shadow 180ms ease, transform 180ms ease;
}

.found-form-panel input[type="file"]:hover::file-selector-button {
    background: linear-gradient(135deg, #7878ff, #5f5fe4);
    box-shadow: 0 7px 17px rgba(102, 102, 255, 0.3);
    transform: translateY(-1px);
}

.found-form-panel .calendar-field {
    position: relative;
    width: 100%;
}

.found-form-panel .calendar-field input[type="date"] {
    padding-right: 52px;
}

.found-form-panel small {
    display: block;

    margin-top: 9px;

    font-size: 12px;

    color: var(--muted-text, #64748b);

    line-height: 1.6;
}


/* ===============================
   IMAGE PREVIEW
================================ */

.found-form-panel .image-preview {
    margin-top: 18px;

    padding: 15px;

    border-radius: 14px;

    border: 1px solid var(--border-color, #dce3e9);

    background: var(--input-bg, #f8fafc);
}

.found-form-panel .image-preview img {
    display: block;

    max-width: 100%;
    max-height: 230px;

    margin: 0 auto 12px;

    border-radius: 10px;

    object-fit: contain;
}

.found-form-panel .image-preview-details {
    display: flex;

    align-items: center;
    justify-content: space-between;

    flex-wrap: wrap;

    gap: 12px;

    color: var(--text-color, #243746);
}

.found-form-panel .image-remove-button {
    width: auto;

    padding: 9px 14px;

    border: none;

    border-radius: 8px;

    background: #fee2e2;

    color: #b91c1c;

    font-weight: 700;

    cursor: pointer;
}

.found-form-panel .image-upload-status {
    font-size: 13px;
    margin-top: 10px;
}


/* ===============================
   SUBMIT BUTTON
================================ */

.found-submit-button {
    width: 100%;

    margin-top: 28px;

    padding: 17px 20px;

    border: none;

    border-radius: 13px;

    background: linear-gradient(110deg, #079b91, #087b9a);

    color: #ffffff;

    font-size: 16px;
    font-weight: 900;

    letter-spacing: 0.5px;

    cursor: pointer;

    box-shadow: 0 10px 22px rgba(8, 139, 145, 0.22);

    transition: 0.3s ease;
}

.found-submit-button:hover {
    transform: translateY(-3px);

    box-shadow: 0 15px 28px rgba(8, 139, 145, 0.32);
}


/* ===============================
   ALERT MESSAGES
================================ */

.found-alert {
    padding: 15px 18px;

    border-radius: 12px;

    margin-bottom: 20px;

    font-size: 14px;
    line-height: 1.6;

    font-weight: 600;
}

.found-error {
    background: #fee2e2;

    border: 1px solid #fecaca;

    color: #991b1b;
}

.found-success {
    background: #dcfce7;

    border: 1px solid #bbf7d0;

    color: #166534;
}

.found-alert p {
    margin: 4px 0;
}


/* ===============================
   BACK LINK
================================ */

.found-back-link {
    display: inline-block;

    margin-top: 22px;

    color: #078b85;

    font-size: 14px;
    font-weight: 800;

    text-decoration: none;

    transition: 0.2s ease;
}

.found-back-link:hover {
    color: #075c61;

    transform: translateX(-3px);
}


/* ===============================
   DARK MODE
================================ */

body.dark-mode .found-brand-panel,
html.dark .found-brand-panel,
[data-theme="dark"] .found-brand-panel {
    background: linear-gradient(
        145deg,
        #071827,
        #103c58,
        #075b64
    );

    box-shadow: 0 18px 45px rgba(0,0,0,0.25);
}

body.dark-mode .found-eyebrow,
html.dark .found-eyebrow,
[data-theme="dark"] .found-eyebrow {
    color: #75ffe6 !important;
}

body.dark-mode .found-main-heading,
html.dark .found-main-heading,
[data-theme="dark"] .found-main-heading {
    color: #f0fffd !important;
}

body.dark-mode .found-main-heading .found-heading-line,
html.dark .found-main-heading .found-heading-line,
[data-theme="dark"] .found-main-heading .found-heading-line {
    color: #f0fffd !important;
}

body.dark-mode .found-main-heading > span:not(.found-heading-line),
html.dark .found-main-heading > span:not(.found-heading-line),
[data-theme="dark"] .found-main-heading > span:not(.found-heading-line) {
    color: #75ffe6 !important;
}

body.dark-mode .found-description,
html.dark .found-description,
[data-theme="dark"] .found-description {
    color: #d0f4f0 !important;
}

body.dark-mode .found-brand-showcase,
html.dark .found-brand-showcase,
[data-theme="dark"] .found-brand-showcase {
    background: rgba(255,255,255,0.08);

    border-color: rgba(255,255,255,0.15);
}

body.dark-mode .found-showcase-title,
html.dark .found-showcase-title,
[data-theme="dark"] .found-showcase-title {
    color: #f0fffd !important;
}

body.dark-mode .found-showcase-text,
html.dark .found-showcase-text,
[data-theme="dark"] .found-showcase-text {
    color: #c4e9e7 !important;
}

body.dark-mode .found-brand-name,
html.dark .found-brand-name,
[data-theme="dark"] .found-brand-name {
    color: #75ffe6 !important;
}

body.dark-mode .found-brand-tagline,
html.dark .found-brand-tagline,
[data-theme="dark"] .found-brand-tagline {
    color: #b7eae5 !important;
}

body.dark-mode .found-info-card,
html.dark .found-info-card,
[data-theme="dark"] .found-info-card {
    background: rgba(255,255,255,0.07);

    border-color: rgba(255,255,255,0.12);
}

body.dark-mode .found-info-card:hover,
html.dark .found-info-card:hover,
[data-theme="dark"] .found-info-card:hover {
    background: rgba(255,255,255,0.13);
}

body.dark-mode .found-info-card h4,
html.dark .found-info-card h4,
[data-theme="dark"] .found-info-card h4 {
    color: #f0fffd !important;
}

body.dark-mode .found-info-card p,
html.dark .found-info-card p,
[data-theme="dark"] .found-info-card p {
    color: #c4e9e7 !important;
}

body.dark-mode .found-form-panel,
html.dark .found-form-panel,
[data-theme="dark"] .found-form-panel {
    background: #142536;

    border-color: #294052;
}

body.dark-mode .found-form-heading h2,
html.dark .found-form-heading h2,
[data-theme="dark"] .found-form-heading h2 {
    color: #f1f5f9;
}

body.dark-mode .found-form-heading p,
html.dark .found-form-heading p,
[data-theme="dark"] .found-form-heading p {
    color: #b7c7d6;
}

body.dark-mode .found-form-panel form label,
html.dark .found-form-panel form label,
[data-theme="dark"] .found-form-panel form label {
    color: #e2eaf2;
}

body.dark-mode .found-form-panel input,
body.dark-mode .found-form-panel select,
body.dark-mode .found-form-panel textarea,

html.dark .found-form-panel input,
html.dark .found-form-panel select,
html.dark .found-form-panel textarea,

[data-theme="dark"] .found-form-panel input,
[data-theme="dark"] .found-form-panel select,
[data-theme="dark"] .found-form-panel textarea {
    background: #0d1c2b;

    border-color: #354b5e;

    color: #f1f5f9;
}

body.dark-mode .found-form-panel input::placeholder,
body.dark-mode .found-form-panel textarea::placeholder,

html.dark .found-form-panel input::placeholder,
html.dark .found-form-panel textarea::placeholder,

[data-theme="dark"] .found-form-panel input::placeholder,
[data-theme="dark"] .found-form-panel textarea::placeholder {
    color: #91a4b8;
}

body.dark-mode .found-form-panel small,
html.dark .found-form-panel small,
[data-theme="dark"] .found-form-panel small {
    color: #b7c7d6;
}

body.dark-mode .found-form-panel .image-preview,
html.dark .found-form-panel .image-preview,
[data-theme="dark"] .found-form-panel .image-preview {
    background: #0d1c2b;

    border-color: #354b5e;
}

body.dark-mode .found-form-panel .image-preview-details,
html.dark .found-form-panel .image-preview-details,
[data-theme="dark"] .found-form-panel .image-preview-details {
    color: #f1f5f9;
}

body.dark-mode .found-back-link,
html.dark .found-back-link,
[data-theme="dark"] .found-back-link {
    color: #75ffe6;
}


/* ===============================
   RESPONSIVE DESIGN
================================ */

@media (max-width: 1000px) {

    .found-layout {
        grid-template-columns: 1fr;
    }

    .found-brand-panel {
        min-height: auto;
        padding: 45px 35px;
    }

    .found-form-panel {
        padding: 35px;
    }

    .found-main-heading .found-heading-line {
        font-size: clamp(30px, 5vw, 43px);
    }
}

@media (max-width: 600px) {

    .found-page {
        margin: 20px auto;
        padding: 12px;
    }

    .found-brand-panel {
        padding: 35px 24px;

        border-radius: 20px;
    }

    .found-main-heading {
        font-size: 38px;
    }

    .found-main-heading .found-heading-line {
        font-size: clamp(27px, 7vw, 36px);

        letter-spacing: -1px;
    }

    .found-showcase-title {
        font-size: 23px;
    }

    .found-brand-name {
        font-size: 29px;
    }

    .found-form-panel {
        padding: 25px 20px;

        border-radius: 20px;
    }

    .found-form-heading h2 {
        font-size: 27px;
    }
}

</style>


<main class="found-page">

    <div class="found-layout">

        <!-- LEFT BRAND PANEL -->

        <section class="found-brand-panel">

            <div class="found-brand-content">

                <span class="found-eyebrow">
                    WELCOME TO RE:CLAIM
                </span>


                <!-- MAIN HEADING -->

                <h1 class="found-main-heading">

                    <span class="found-heading-line">
                        Found Something?
                    </span>

                    <span>
                        Give It Back.
                    </span>

                </h1>


                <p class="found-description">
                    A small act of honesty can make someone's day.
                    Report the item you found and help it reach
                    the person who is looking for it.
                </p>


                <!-- PREMIUM TYPOGRAPHY DESIGN -->

                <div class="found-brand-showcase">

                    <h2 class="found-showcase-title">
                        Every Found Item
                        Deserves a Way Home.
                    </h2>

                    <p class="found-showcase-text">
                        Your report could be the missing connection
                        between someone and something they lost.
                    </p>

                    <picture class="brand-lockup report-brand-lockup">
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

                </div>


                <!-- INFORMATION CARDS -->

                <div class="found-info-list">

                    <div class="found-info-card">

                        <h4>📝 Share What You Found</h4>

                        <p>
                            Add the item's name, category and a
                            clear description.
                        </p>

                    </div>


                    <div class="found-info-card">

                        <h4>📍 Tell Us Where</h4>

                        <p>
                            Mention the location and date where
                            you found the item.
                        </p>

                    </div>


                    <div class="found-info-card">

                        <h4>🤝 Help Reunite</h4>

                        <p>
                            Your report helps the rightful owner
                            identify and reclaim their belongings.
                        </p>

                    </div>

                </div>

            </div>

        </section>


        <!-- RIGHT FORM PANEL -->

        <section class="found-form-panel">

            <div class="found-form-heading">

                <span>FOUND ITEM REGISTRATION</span>

                <h2>Report a Found Item</h2>

                <p>
                    Provide the details needed to help identify
                    the item's owner.
                </p>

            </div>


            <!-- ERROR MESSAGE -->

            <?php if (!empty($errors)) { ?>

                <div class="found-alert found-error">

                    <?php foreach ($errors as $error) { ?>

                        <p>
                            <?php echo htmlspecialchars($error); ?>
                        </p>

                    <?php } ?>

                </div>

            <?php } ?>


            <!-- SUCCESS MESSAGE -->

            <?php if (!empty($success)) { ?>

                <div class="found-alert found-success">

                    <?php echo htmlspecialchars($success . $mail_warning); ?>

                </div>

            <?php } ?>


            <!-- REPORT FORM -->

            <form method="POST" enctype="multipart/form-data">

                <label>Item Name</label>

                <input
                    type="text"
                    name="item_name"
                    value="<?php echo htmlspecialchars($item_name); ?>"
                    placeholder="Example: Black Wallet"
                    required
                >


                <label>Item Category</label>

                <select name="category_id" required>

                    <option value="">Select Category</option>

                    <?php while ($cat = mysqli_fetch_assoc($category_result)) { ?>

                        <option
                            value="<?php echo $cat['category_id']; ?>"
                            <?php
                            echo ($category_id == $cat['category_id'])
                                ? "selected"
                                : "";
                            ?>
                        >
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>

                    <?php } ?>

                </select>


                <label>Description</label>

                <textarea
                    name="description"
                    rows="4"
                    placeholder="Describe the item you found..."
                    required
                ><?php echo htmlspecialchars($description); ?></textarea>


                <label>Found Location</label>

                <input
                    type="text"
                    name="location"
                    value="<?php echo htmlspecialchars($location); ?>"
                    placeholder="Example: College Canteen"
                    required
                >


                <label for="found-item-date">Found Date</label>

                <input
                    type="date"
                    id="found-item-date"
                    name="item_date"
                    value="<?php echo htmlspecialchars($item_date); ?>"
                    max="<?php echo date('Y-m-d'); ?>"
                    required
                >


                <label for="item-image">
                    Upload Item Image (Optional)
                </label>

                <input
                    type="file"
                    id="item-image"
                    name="item_image"
                    accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                    aria-describedby="item-image-help item-image-status"
                >

                <small id="item-image-help">
                    JPG, JPEG, PNG or WEBP. Maximum size: 2 MB.
                </small>


                <!-- IMAGE PREVIEW -->

                <div class="image-preview" data-image-preview hidden>

                    <img
                        data-image-preview-image
                        alt="Selected item image preview"
                    >

                    <div class="image-preview-details">

                        <span data-image-name></span>

                        <button
                            type="button"
                            class="image-remove-button"
                            data-image-remove
                        >
                            Remove image
                        </button>

                    </div>

                </div>


                <p
                    id="item-image-status"
                    class="image-upload-status"
                    data-image-status
                    role="status"
                    aria-live="polite"
                    hidden
                ></p>


                <button
                    type="submit"
                    class="found-submit-button"
                >
                    Submit Found Item Report
                </button>

            </form>


            <a href="dashboard.php" class="found-back-link">
                ← Back to Dashboard
            </a>

        </section>

    </div>

</main>


<?php include "../includes/footer.php"; ?>
