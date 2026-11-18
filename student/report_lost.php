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

// Process form
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

        $check_category = mysqli_prepare(
            $conn,
            "SELECT category_id FROM categories WHERE category_id = ?"
        );

        mysqli_stmt_bind_param(
            $check_category,
            "i",
            $category_id
        );

        mysqli_stmt_execute($check_category);

        $category_check_result =
            mysqli_stmt_get_result($check_category);

        if (mysqli_num_rows($category_check_result) == 0) {
            $errors[] = "Please select a valid category.";
        }

        mysqli_stmt_close($check_category);
    }

    // Validate date
    $date_object = DateTime::createFromFormat('Y-m-d', $item_date);

    if (
        !$date_object ||
        $date_object->format('Y-m-d') !== $item_date ||
        $item_date > date('Y-m-d')
    ) {
        $errors[] = "Please enter a valid lost date.";
    }

    // Validate and store optional image
    $image_name = null;

    if (empty($errors)) {
        $image_name = catchit_save_item_image_upload(
            $_FILES["item_image"] ?? null,
            $errors
        );
    }

    // Insert into database
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
        VALUES (?, ?, ?, ?, 'lost', ?, ?, ?)";

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
                    "item_type" => "lost"
                ]);

            } else {
                error_log("CatchIt email: report confirmation details could not be loaded.");
            }

            $success = "Your lost-item report has been submitted for review.";

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

.lost-page {
    max-width: 1160px;
    margin: 25px auto;
    padding: 15px;
    box-sizing: border-box;
}

.lost-layout {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 24px;
    align-items: stretch;
}


/* ===============================
   LEFT BRAND PANEL
   LIGHT MODE
================================ */

.lost-brand-panel {
    position: relative;
    overflow: hidden;

    width: 100%;
    min-width: 0;
    min-height: unset;
    height: 100%;

    box-sizing: border-box;

    padding: 35px 30px;

    border-radius: 25px;

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
}

.lost-brand-panel::before {
    content: "";
    position: absolute;

    width: 300px;
    height: 300px;

    top: -130px;
    right: -100px;

    border-radius: 50%;

    background: rgba(255,255,255,0.25);
}

.lost-brand-panel::after {
    content: "";
    position: absolute;

    width: 240px;
    height: 240px;

    bottom: -100px;
    left: -90px;

    border-radius: 50%;

    background: rgba(255,255,255,0.18);
}

.lost-brand-content {
    position: relative;
    z-index: 2;
}


/* ===============================
   BRAND EYEBROW
================================ */

.lost-eyebrow {
    display: inline-block;

    font-size: 13px;
    font-weight: 800;

    letter-spacing: 3px;

    color: #075c61 !important;

    margin-bottom: 20px;
}


/* ===============================
   MAIN HEADING
================================ */

.lost-main-heading {
    margin: 0 0 18px;

    font-weight: 900;

    line-height: 1.2;
}

/* First Line */

.lost-main-heading .lost-heading-line {
    display: block;

    white-space: nowrap;

    font-size: clamp(34px, 3.5vw, 44px);

    line-height: 1.25;

    letter-spacing: -1.5px;

    color: #102b3a !important;

    margin-bottom: 7px;
}

/* Second Line */

.lost-main-heading .lost-heading-highlight {
    display: block;

    white-space: nowrap;

    font-size: clamp(27px, 3vw, 38px);

    line-height: 1.3;

    letter-spacing: -1px;

    color: #087b78 !important;
}


/* ===============================
   DESCRIPTION
================================ */

.lost-description {
    font-size: 16px;

    line-height: 1.7;

    font-weight: 600;

    color: #244653 !important;

    max-width: 470px;

    margin-bottom: 24px;
}


/* ===============================
   TYPOGRAPHY SHOWCASE
================================ */

.lost-brand-showcase {
    padding: 24px 18px;

    margin: 10px 0 22px;

    border: 1px solid rgba(255,255,255,0.55);

    border-radius: 20px;

    background: rgba(255,255,255,0.28);

    backdrop-filter: blur(14px);

    text-align: center;

    box-shadow: 0 12px 30px rgba(13, 87, 91, 0.10);
}

.lost-showcase-eyebrow {
    font-size: 10px;

    font-weight: 900;

    letter-spacing: 3px;

    color: #087b9b !important;

    margin-bottom: 13px;
}

.lost-showcase-title {
    font-size: 24px;

    line-height: 1.4;

    font-weight: 900;

    color: #123b49 !important;

    margin: 0 0 10px;
}

.lost-showcase-text {
    font-size: 14px;

    line-height: 1.7;

    font-weight: 600;

    color: #315663 !important;

    margin: 0 0 17px;
}

.lost-brand-name {
    font-size: 32px;

    font-weight: 900;

    letter-spacing: 5px;

    color: #075c61 !important;

    margin: 0;
}

.lost-brand-tagline {
    font-size: 10px;

    font-weight: 900;

    letter-spacing: 3px;

    color: #24535d !important;

    margin-top: 8px;
}


/* ===============================
   INFORMATION CARDS
================================ */

.lost-info-list {
    display: grid;
    gap: 10px;
}

.lost-info-card {
    padding: 13px 17px;

    border-radius: 14px;

    background: rgba(255,255,255,0.30);

    border: 1px solid rgba(255,255,255,0.48);

    transition: 0.3s ease;
}

.lost-info-card:hover {
    transform: translateX(5px);

    background: rgba(255,255,255,0.48);
}

.lost-info-card h4 {
    font-size: 16px;

    font-weight: 800;

    color: #123b49 !important;

    margin: 0 0 5px;
}

.lost-info-card p {
    font-size: 13px;

    line-height: 1.65;

    color: #315663 !important;

    margin: 0;
}


/* ===============================
   RIGHT FORM PANEL
================================ */

.lost-form-panel {
    width: 100%;
    min-width: 0;
    height: 100%;

    box-sizing: border-box;

    padding: 35px 30px;

    border-radius: 25px;

    background: var(--card-bg, #ffffff);

    border: 1px solid var(--border-color, #e5e7eb);

    box-shadow: 0 18px 45px rgba(0,0,0,0.07);
}

.lost-form-heading {
    margin-bottom: 22px;
}

.lost-form-heading span {
    display: inline-block;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: 2px;

    color: #0d9b91;

    margin-bottom: 10px;
}

.lost-form-heading h2 {
    font-size: 30px;

    font-weight: 900;

    color: var(--text-color, #172b3a);

    margin: 0 0 9px;
}

.lost-form-heading p {
    font-size: 15px;

    line-height: 1.7;

    color: var(--muted-text, #64748b);

    margin: 0;
}


/* ===============================
   FORM ELEMENTS
================================ */

.lost-form-panel form label {
    display: block;

    margin: 17px 0 8px;

    font-size: 14px;

    font-weight: 800;

    color: var(--text-color, #243746);
}

.lost-form-panel input,
.lost-form-panel select,
.lost-form-panel textarea {
    width: 100%;

    box-sizing: border-box;

    padding: 13px 15px;

    border-radius: 11px;

    border: 1px solid var(--border-color, #dce3e9);

    background: var(--input-bg, #f8fafc);

    color: var(--text-color, #243746);

    font-size: 15px;

    font-family: inherit;

    outline: none;

    transition: 0.25s ease;
}

.lost-form-panel textarea {
    resize: vertical;

    min-height: 110px;
}

.lost-form-panel input:focus,
.lost-form-panel select:focus,
.lost-form-panel textarea:focus {
    border-color: #13aaa0;

    box-shadow: 0 0 0 4px rgba(19,170,160,0.13);

    background: var(--card-bg, #ffffff);
}

.lost-form-panel input::placeholder,
.lost-form-panel textarea::placeholder {
    color: #94a3b8;
}

.lost-form-panel input[type="file"] {
    padding: 11px;

    cursor: pointer;
}

.lost-form-panel input[type="file"]::file-selector-button {
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

.lost-form-panel input[type="file"]:hover::file-selector-button {
    background: linear-gradient(135deg, #7878ff, #5f5fe4);
    box-shadow: 0 7px 17px rgba(102, 102, 255, 0.3);
    transform: translateY(-1px);
}

.lost-form-panel .calendar-field {
    position: relative;
    width: 100%;
}

.lost-form-panel .calendar-field input[type="date"] {
    padding-right: 52px;
}

.lost-form-panel small {
    display: block;

    margin-top: 8px;

    font-size: 12px;

    color: var(--muted-text, #64748b);

    line-height: 1.6;
}


/* ===============================
   IMAGE PREVIEW
================================ */

.lost-form-panel .image-preview {
    margin-top: 15px;

    padding: 13px;

    border-radius: 13px;

    border: 1px solid var(--border-color, #dce3e9);

    background: var(--input-bg, #f8fafc);
}

.lost-form-panel .image-preview img {
    display: block;

    max-width: 100%;

    max-height: 210px;

    margin: 0 auto 12px;

    border-radius: 10px;

    object-fit: contain;
}

.lost-form-panel .image-preview-details {
    display: flex;

    align-items: center;

    justify-content: space-between;

    flex-wrap: wrap;

    gap: 12px;

    color: var(--text-color, #243746);
}

.lost-form-panel .image-remove-button {
    width: auto;

    padding: 9px 14px;

    border: none;

    border-radius: 8px;

    background: #fee2e2;

    color: #b91c1c;

    font-weight: 700;

    cursor: pointer;
}

.lost-form-panel .image-upload-status {
    font-size: 13px;

    margin-top: 9px;
}


/* ===============================
   SUBMIT BUTTON
================================ */

.lost-submit-button {
    width: 100%;

    margin-top: 23px;

    padding: 15px 18px;

    border: none;

    border-radius: 12px;

    background: linear-gradient(110deg, #079b91, #087b9a);

    color: #ffffff;

    font-size: 15px;

    font-weight: 900;

    letter-spacing: 0.5px;

    cursor: pointer;

    box-shadow: 0 10px 22px rgba(8, 139, 145, 0.22);

    transition: 0.3s ease;
}

.lost-submit-button:hover {
    transform: translateY(-3px);

    box-shadow: 0 15px 28px rgba(8, 139, 145, 0.32);
}


/* ===============================
   ALERT MESSAGES
================================ */

.lost-alert {
    padding: 14px 17px;

    border-radius: 12px;

    margin-bottom: 18px;

    font-size: 13px;

    line-height: 1.6;

    font-weight: 600;
}

.lost-error {
    background: #fee2e2;

    border: 1px solid #fecaca;

    color: #991b1b;
}

.lost-success {
    background: #dcfce7;

    border: 1px solid #bbf7d0;

    color: #166534;
}

.lost-alert p {
    margin: 4px 0;
}


/* ===============================
   BACK LINK
================================ */

.lost-back-link {
    display: inline-block;

    margin-top: 18px;

    color: #078b85;

    font-size: 14px;

    font-weight: 800;

    text-decoration: none;

    transition: 0.2s ease;
}

.lost-back-link:hover {
    color: #075c61;

    transform: translateX(-3px);
}


/* ===============================
   DARK MODE
================================ */

body.dark-mode .lost-brand-panel,
html.dark .lost-brand-panel,
[data-theme="dark"] .lost-brand-panel {
    background: linear-gradient(
        145deg,
        #071827,
        #103c58,
        #075b64
    );

    box-shadow: 0 18px 45px rgba(0,0,0,0.25);
}

body.dark-mode .lost-eyebrow,
html.dark .lost-eyebrow,
[data-theme="dark"] .lost-eyebrow {
    color: #75ffe6 !important;
}

/* First heading */

body.dark-mode .lost-main-heading .lost-heading-line,
html.dark .lost-main-heading .lost-heading-line,
[data-theme="dark"] .lost-main-heading .lost-heading-line {
    color: #f0fffd !important;
}

/* Second heading */

body.dark-mode .lost-main-heading .lost-heading-highlight,
html.dark .lost-main-heading .lost-heading-highlight,
[data-theme="dark"] .lost-main-heading .lost-heading-highlight {
    color: #75ffe6 !important;
}

body.dark-mode .lost-description,
html.dark .lost-description,
[data-theme="dark"] .lost-description {
    color: #d0f4f0 !important;
}

body.dark-mode .lost-brand-showcase,
html.dark .lost-brand-showcase,
[data-theme="dark"] .lost-brand-showcase {
    background: rgba(255,255,255,0.08);

    border-color: rgba(255,255,255,0.15);
}

body.dark-mode .lost-showcase-eyebrow,
html.dark .lost-showcase-eyebrow,
[data-theme="dark"] .lost-showcase-eyebrow {
    color: #75dff0 !important;
}

body.dark-mode .lost-showcase-title,
html.dark .lost-showcase-title,
[data-theme="dark"] .lost-showcase-title {
    color: #f0fffd !important;
}

body.dark-mode .lost-showcase-text,
html.dark .lost-showcase-text,
[data-theme="dark"] .lost-showcase-text {
    color: #c4e9e7 !important;
}

body.dark-mode .lost-brand-name,
html.dark .lost-brand-name,
[data-theme="dark"] .lost-brand-name {
    color: #75ffe6 !important;
}

body.dark-mode .lost-brand-tagline,
html.dark .lost-brand-tagline,
[data-theme="dark"] .lost-brand-tagline {
    color: #b7eae5 !important;
}

body.dark-mode .lost-info-card,
html.dark .lost-info-card,
[data-theme="dark"] .lost-info-card {
    background: rgba(255,255,255,0.07);

    border-color: rgba(255,255,255,0.12);
}

body.dark-mode .lost-info-card:hover,
html.dark .lost-info-card:hover,
[data-theme="dark"] .lost-info-card:hover {
    background: rgba(255,255,255,0.13);
}

body.dark-mode .lost-info-card h4,
html.dark .lost-info-card h4,
[data-theme="dark"] .lost-info-card h4 {
    color: #f0fffd !important;
}

body.dark-mode .lost-info-card p,
html.dark .lost-info-card p,
[data-theme="dark"] .lost-info-card p {
    color: #c4e9e7 !important;
}

body.dark-mode .lost-form-panel,
html.dark .lost-form-panel,
[data-theme="dark"] .lost-form-panel {
    background: #142536;

    border-color: #294052;
}

body.dark-mode .lost-form-heading h2,
html.dark .lost-form-heading h2,
[data-theme="dark"] .lost-form-heading h2 {
    color: #f1f5f9;
}

body.dark-mode .lost-form-heading p,
html.dark .lost-form-heading p,
[data-theme="dark"] .lost-form-heading p {
    color: #b7c7d6;
}

body.dark-mode .lost-form-panel form label,
html.dark .lost-form-panel form label,
[data-theme="dark"] .lost-form-panel form label {
    color: #e2eaf2;
}

body.dark-mode .lost-form-panel input,
body.dark-mode .lost-form-panel select,
body.dark-mode .lost-form-panel textarea,

html.dark .lost-form-panel input,
html.dark .lost-form-panel select,
html.dark .lost-form-panel textarea,

[data-theme="dark"] .lost-form-panel input,
[data-theme="dark"] .lost-form-panel select,
[data-theme="dark"] .lost-form-panel textarea {
    background: #0d1c2b;

    border-color: #354b5e;

    color: #f1f5f9;
}

body.dark-mode .lost-form-panel input::placeholder,
body.dark-mode .lost-form-panel textarea::placeholder,

html.dark .lost-form-panel input::placeholder,
html.dark .lost-form-panel textarea::placeholder,

[data-theme="dark"] .lost-form-panel input::placeholder,
[data-theme="dark"] .lost-form-panel textarea::placeholder {
    color: #91a4b8;
}

body.dark-mode .lost-form-panel small,
html.dark .lost-form-panel small,
[data-theme="dark"] .lost-form-panel small {
    color: #b7c7d6;
}

body.dark-mode .lost-form-panel .image-preview,
html.dark .lost-form-panel .image-preview,
[data-theme="dark"] .lost-form-panel .image-preview {
    background: #0d1c2b;

    border-color: #354b5e;
}

body.dark-mode .lost-form-panel .image-preview-details,
html.dark .lost-form-panel .image-preview-details,
[data-theme="dark"] .lost-form-panel .image-preview-details {
    color: #f1f5f9;
}

body.dark-mode .lost-back-link,
html.dark .lost-back-link,
[data-theme="dark"] .lost-back-link {
    color: #75ffe6;
}


/* ===============================
   RESPONSIVE DESIGN
================================ */

@media (max-width: 1000px) {

    .lost-page {
        max-width: 760px;
    }

    .lost-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .lost-brand-panel,
    .lost-form-panel {
        height: auto;
    }

    .lost-brand-panel {
        padding: 35px 30px;
    }

    .lost-form-panel {
        padding: 35px 30px;
    }

    .lost-main-heading .lost-heading-line {
        font-size: clamp(32px, 5.2vw, 44px);
    }

    .lost-main-heading .lost-heading-highlight {
        font-size: clamp(25px, 4.2vw, 35px);
    }
}

@media (max-width: 600px) {

    .lost-page {
        margin: 18px auto;
        padding: 12px;
    }

    .lost-brand-panel {
        padding: 30px 22px;
        border-radius: 20px;
    }

    .lost-main-heading .lost-heading-line {
        font-size: clamp(26px, 7vw, 36px);
        letter-spacing: -1px;
        white-space: nowrap;
    }

    .lost-main-heading .lost-heading-highlight {
        font-size: clamp(20px, 5.5vw, 28px);
        letter-spacing: -0.8px;
        white-space: nowrap;
    }

    .lost-showcase-title {
        font-size: 21px;
    }

    .lost-brand-name {
        font-size: 28px;
    }

    .lost-form-panel {
        padding: 25px 20px;
        border-radius: 20px;
    }

    .lost-form-heading h2 {
        font-size: 26px;
    }
}

</style>


<main class="lost-page">

    <div class="lost-layout">

        <!-- LEFT BRAND PANEL -->

        <section class="lost-brand-panel">

            <div class="lost-brand-content">

                <span class="lost-eyebrow">
                    RE:CLAIM COMMUNITY
                </span>


                <!-- MAIN HEADING -->

                <h1 class="lost-main-heading">

                    <span class="lost-heading-line">
                        Lost Something?
                    </span>

                    <span class="lost-heading-highlight">
                        Let's Find It Together!
                    </span>

                </h1>


                <p class="lost-description">
                    Don't worry if you have misplaced something on campus.
                    Report your lost item here and let the Re:Claim
                    community help you reconnect with your belongings.
                </p>


                <!-- PREMIUM TYPOGRAPHY SHOWCASE -->

                <div class="lost-brand-showcase">

                    <div class="lost-showcase-eyebrow">
                        A COMMUNITY THAT CARES
                    </div>

                    <h2 class="lost-showcase-title">
                        Every Item Has a Story.
                        Every Lost Item Deserves a Way Home.
                    </h2>

                    <p class="lost-showcase-text">
                        Together, we make lost things found again.
                        Your campus community is here to help.
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

                <div class="lost-info-list">

                    <div class="lost-info-card">

                        <h4>📝 Submit Your Report</h4>

                        <p>
                            Share the name, category and description
                            of the item you have lost.
                        </p>

                    </div>


                    <div class="lost-info-card">

                        <h4>📍 Share the Location</h4>

                        <p>
                            Mention where and when you last
                            saw your belongings.
                        </p>

                    </div>


                    <div class="lost-info-card">

                        <h4>🔎 Find Your Belongings</h4>

                        <p>
                            Browse reports and help reconnect
                            your lost item with you.
                        </p>

                    </div>

                </div>

            </div>

        </section>


        <!-- RIGHT FORM PANEL -->

        <section class="lost-form-panel">

            <div class="lost-form-heading">

                <span>LOST PROPERTY REGISTRATION</span>

                <h2>Report a Lost Item</h2>

                <p>
                    Fill in the details below to help others
                    identify and return your lost item.
                </p>

            </div>


            <!-- ERROR MESSAGE -->

            <?php if (!empty($errors)) { ?>

                <div class="lost-alert lost-error">

                    <?php foreach ($errors as $error) { ?>

                        <p>
                            <?php echo htmlspecialchars($error); ?>
                        </p>

                    <?php } ?>

                </div>

            <?php } ?>


            <!-- SUCCESS MESSAGE -->

            <?php if (!empty($success)) { ?>

                <div class="lost-alert lost-success">

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
                    placeholder="Example: Mobile Phone"
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
                    placeholder="Describe your lost item..."
                    required
                ><?php echo htmlspecialchars($description); ?></textarea>


                <label>Lost Location</label>

                <input
                    type="text"
                    name="location"
                    value="<?php echo htmlspecialchars($location); ?>"
                    placeholder="Example: College Library"
                    required
                >


                <label for="lost-item-date">Lost Date</label>

                <input
                    type="date"
                    id="lost-item-date"
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
                    class="lost-submit-button"
                >
                    Submit Lost Item Report
                </button>

            </form>


            <a href="dashboard.php" class="lost-back-link">
                ← Back to Dashboard
            </a>

        </section>

    </div>

</main>


<?php include "../includes/footer.php"; ?>