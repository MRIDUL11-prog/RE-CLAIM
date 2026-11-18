<?php

require_once "../includes/auth.php";
checkStudent();

require_once "../config/database.php";

$errors = [];
$success = "";

// Get item ID from URL
$item_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$item_id || $item_id <= 0) {
    die("Invalid item ID.");
}

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Fetch item details
$sql = "SELECT items.*,
               categories.category_name,
               users.name AS reporter_name
        FROM items
        INNER JOIN categories
            ON items.category_id = categories.category_id
        INNER JOIN users
            ON items.user_id = users.user_id
        WHERE items.item_id = ?
        AND items.status = 'approved'";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$item) {
    die("Item not found or not available.");
}

// Check whether this is the owner's own item
$is_owner = (
    (int)$item["user_id"] === (int)$_SESSION["user_id"]
);

// Process claim request
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {

        $errors[] = "Invalid request. Please refresh the page.";

    } elseif ($item["item_type"] !== "found") {

        $errors[] = "Only found items can be claimed.";

    } elseif ($is_owner) {

        $errors[] = "You cannot claim your own item.";

    } elseif ($item["status"] !== "approved") {

        $errors[] = "This item is no longer available.";

    } else {

        $message = trim($_POST["message"] ?? "");

        if ($message === "") {

            $errors[] = "Please provide a claim message.";

        } else {

            // Check duplicate claim
            $check_sql = "SELECT claim_id
                          FROM claims
                          WHERE item_id = ?
                          AND claimant_id = ?
                          AND claim_status IN ('pending', 'approved')";

            $check_stmt = mysqli_prepare($conn, $check_sql);

            mysqli_stmt_bind_param(
                $check_stmt,
                "ii",
                $item_id,
                $_SESSION["user_id"]
            );

            mysqli_stmt_execute($check_stmt);

            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {

                $errors[] = "You have already submitted a claim.";

            } else {

                // Insert claim
                $insert_sql = "INSERT INTO claims
                    (item_id, claimant_id, message)
                    VALUES (?, ?, ?)";

                $insert_stmt = mysqli_prepare($conn, $insert_sql);

                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "iis",
                    $item_id,
                    $_SESSION["user_id"],
                    $message
                );

                if (mysqli_stmt_execute($insert_stmt)) {

                    $success = "Your claim request has been submitted.";

                } else {

                    $errors[] = "We could not submit your claim. Please try again.";

                }

                mysqli_stmt_close($insert_stmt);
            }

            mysqli_stmt_close($check_stmt);
        }
    }
}

?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>


<style>

/* =====================================
   RE:CLAIM ITEM DETAILS PAGE
===================================== */

.details-container {
    --details-card: #ffffff;
    --details-text: #183449;
    --details-muted: #536b7d;
    --details-border: #dceaf0;
    --details-soft: #edf9f8;
    --details-accent: #078f91;
    --details-accent-2: #5366e8;

    max-width: 1050px;
    margin: 38px auto 55px;
    padding: 22px;

    color: var(--details-text);
}


/* =====================================
   PAGE HEADING
===================================== */

.details-container > h2 {
    position: relative;

    margin: 0 0 25px;
    padding: 23px 28px;

    border-radius: 22px;

    color: #ffffff;

    font-size: clamp(27px, 4vw, 36px);
    font-weight: 900;
    letter-spacing: -.6px;

    background: linear-gradient(
        115deg,
        #087f8c 0%,
        #16b7a5 48%,
        #5969e8 100%
    );

    box-shadow: 0 14px 30px rgba(18, 139, 155, .20);

    overflow: hidden;
}

.details-container > h2::after {
    content: "🔎";

    position: absolute;

    right: 28px;
    top: 50%;

    transform: translateY(-50%);

    font-size: 43px;

    opacity: .9;
}


/* =====================================
   MAIN DETAILS CARD
===================================== */

.details-card {
    position: relative;
    overflow: hidden;

    background: var(--details-card);

    padding: clamp(22px, 4vw, 38px);

    border-radius: 24px;

    border: 1px solid var(--details-border);

    box-shadow: 0 16px 42px rgba(26, 66, 90, .10);
}

.details-card::before {
    content: "";

    display: block;

    height: 6px;

    margin:
        calc(clamp(22px, 4vw, 38px) * -1)
        calc(clamp(22px, 4vw, 38px) * -1)
        28px;

    background: linear-gradient(
        90deg,
        #12b8a6,
        #4d72e8,
        #a66be8
    );
}


/* =====================================
   ITEM IMAGE
===================================== */

.details-image {
    display: block;

    width: 100%;
    max-height: 410px;

    object-fit: contain;

    background: var(--details-soft);

    border: 1px solid var(--details-border);

    border-radius: 18px;

    margin: 0 auto 26px;

    padding: 10px;

    box-sizing: border-box;
}


/* =====================================
   NO IMAGE AVAILABLE
===================================== */

.details-card .no-image {
    min-height: 230px;

    background: linear-gradient(
        135deg,
        #e8f8f7,
        #edf0ff
    );

    color: #476579;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px dashed #9dc9ce;

    border-radius: 18px;

    margin-bottom: 26px;

    font-size: 17px;
    font-weight: 800;
}


/* =====================================
   ITEM TITLE
===================================== */

.details-card h3 {
    color: var(--details-text);

    font-size: clamp(22px, 3vw, 28px);

    font-weight: 900;

    margin: 0 0 18px;
}


/* =====================================
   ITEM INFORMATION
===================================== */

.details-card p {
    color: var(--details-muted);

    line-height: 1.8;

    font-size: 15px;

    margin: 12px 0;

    overflow-wrap: anywhere;
}

.details-card strong {
    color: var(--details-text);

    font-weight: 800;
}


/* =====================================
   DIVIDER
===================================== */

.details-card hr {
    border: 0;

    height: 1px;

    background: var(--details-border);

    margin: 28px 0;
}


/* =====================================
   ITEM TYPE BADGES
===================================== */

.details-card .item-type {
    display: inline-flex;
    align-items: center;

    padding: 6px 13px;

    border-radius: 999px;

    font-size: 12px;

    font-weight: 900;

    letter-spacing: .5px;

    text-transform: uppercase;
}

.details-card .item-type.found {
    color: #087653;
    background: #d9f8e9;
}

.details-card .item-type.lost {
    color: #a34d08;
    background: #fff0d7;
}


/* =====================================
   CLAIM FORM
===================================== */

.details-card form {
    margin-top: 16px;

    padding: 22px;

    border-radius: 18px;

    background: var(--details-soft);

    border: 1px solid var(--details-border);
}


/* =====================================
   CLAIM FORM LABEL
===================================== */

.details-card label {
    display: block;

    color: var(--details-text);

    font-weight: 800;

    margin: 12px 0 9px;
}


/* =====================================
   TEXTAREA
===================================== */

.details-card textarea {
    display: block;

    width: 100%;

    min-height: 125px;

    box-sizing: border-box;

    padding: 14px 15px;

    border: 1px solid #bfd3dc;

    border-radius: 12px;

    margin-top: 10px;

    background: var(--details-card);

    color: var(--details-text);

    font: inherit;

    font-size: 15px;

    line-height: 1.6;

    resize: vertical;

    outline: none;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;
}

.details-card textarea:focus {
    border-color: var(--details-accent);

    box-shadow: 0 0 0 4px rgba(7, 143, 145, .13);
}

.details-card textarea::placeholder {
    color: #8295a3;
}


/* =====================================
   SUBMIT CLAIM BUTTON
===================================== */

.details-card button {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    background: linear-gradient(
        110deg,
        #078f91,
        #5366e8
    );

    color: #ffffff;

    border: 0;

    padding: 13px 22px;

    border-radius: 11px;

    margin-top: 15px;

    cursor: pointer;

    font-size: 14px;

    font-weight: 900;

    box-shadow: 0 8px 18px rgba(50, 105, 180, .20);

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        filter .2s ease;
}

.details-card button:hover {
    transform: translateY(-2px);

    filter: brightness(1.06);

    box-shadow: 0 12px 24px rgba(50, 105, 180, .27);
}


/* =====================================
   BACK TO SEARCH BUTTON
===================================== */

.back-link {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    margin-top: 25px;

    background: linear-gradient(
        110deg,
        #5366e8,
        #078f91
    );

    color: #ffffff !important;

    padding: 12px 21px;

    border-radius: 11px;

    text-decoration: none;

    font-weight: 800;

    box-shadow: 0 7px 18px rgba(83, 102, 232, .24);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}

.back-link:hover {
    transform: translateY(-2px);

    box-shadow: 0 11px 24px rgba(83, 102, 232, .32);
}


/* =====================================
   ERROR AND SUCCESS MESSAGES
===================================== */

.error-message,
.success-message {
    padding: 14px 18px;

    border-radius: 12px;

    margin-bottom: 18px;

    font-size: 14px;

    font-weight: 700;

    line-height: 1.6;
}

.error-message {
    background: #fff0f0;

    color: #a51d2d;

    border: 1px solid #ffcaca;
}

.success-message {
    background: #e5fbef;

    color: #12643d;

    border: 1px solid #b8efd0;
}

.error-message p {
    color: inherit;
    margin: 4px 0;
}


/* =====================================
   DARK MODE
===================================== */

body.dark-mode .details-container,
html.dark .details-container,
[data-theme="dark"] .details-container,
.dark-mode .details-container {

    --details-card: #142638;
    --details-text: #f1f7fc;
    --details-muted: #c2d1df;
    --details-border: #30485c;
    --details-soft: #1b3448;

    color: #f1f7fc;
}


body.dark-mode .details-card,
html.dark .details-card,
[data-theme="dark"] .details-card,
.dark-mode .details-card {

    background: #142638;

    border-color: #30485c;

    box-shadow: 0 18px 44px rgba(0,0,0,.25);
}


/* Dark mode headings and labels */

body.dark-mode .details-card h3,
html.dark .details-card h3,
[data-theme="dark"] .details-card h3,
.dark-mode .details-card h3,

body.dark-mode .details-card strong,
html.dark .details-card strong,
[data-theme="dark"] .details-card strong,
.dark-mode .details-card strong,

body.dark-mode .details-card label,
html.dark .details-card label,
[data-theme="dark"] .details-card label,
.dark-mode .details-card label {

    color: #f1f7fc !important;
}


/* Dark mode paragraph text */

body.dark-mode .details-card p,
html.dark .details-card p,
[data-theme="dark"] .details-card p,
.dark-mode .details-card p {

    color: #c2d1df !important;
}


/* Dark mode image */

body.dark-mode .details-image,
html.dark .details-image,
[data-theme="dark"] .details-image,
.dark-mode .details-image {

    background: #1b3448;

    border-color: #30485c;
}


/* Dark mode no image */

body.dark-mode .details-card .no-image,
html.dark .details-card .no-image,
[data-theme="dark"] .details-card .no-image,
.dark-mode .details-card .no-image {

    background: linear-gradient(
        135deg,
        #1b3448,
        #26365b
    );

    color: #d8e7f2;

    border-color: #47677d;
}


/* Dark mode claim form */

body.dark-mode .details-card form,
html.dark .details-card form,
[data-theme="dark"] .details-card form,
.dark-mode .details-card form {

    background: #1b3448;

    border-color: #30485c;
}


/* Dark mode textarea */

body.dark-mode .details-card textarea,
html.dark .details-card textarea,
[data-theme="dark"] .details-card textarea,
.dark-mode .details-card textarea {

    background: #0e1d2c;

    color: #f1f7fc;

    border-color: #476176;
}


/* Dark mode placeholder */

body.dark-mode .details-card textarea::placeholder,
html.dark .details-card textarea::placeholder,
[data-theme="dark"] .details-card textarea::placeholder,
.dark-mode .details-card textarea::placeholder {

    color: #9eb1c2;
}


/* Dark mode error message */

body.dark-mode .error-message,
html.dark .error-message,
[data-theme="dark"] .error-message,
.dark-mode .error-message {

    background: #48242b;

    color: #ffd6dc;

    border-color: #85414d;
}


/* Dark mode success message */

body.dark-mode .success-message,
html.dark .success-message,
[data-theme="dark"] .success-message,
.dark-mode .success-message {

    background: #153e32;

    color: #c9f9df;

    border-color: #287454;
}


/* Dark mode item badges */

body.dark-mode .details-card .item-type.found,
html.dark .details-card .item-type.found,
[data-theme="dark"] .details-card .item-type.found,
.dark-mode .details-card .item-type.found {

    background: #164b3a;

    color: #a9f5ce;
}


body.dark-mode .details-card .item-type.lost,
html.dark .details-card .item-type.lost,
[data-theme="dark"] .details-card .item-type.lost,
.dark-mode .details-card .item-type.lost {

    background: #573a1d;

    color: #ffdda8;
}


/* =====================================
   RESPONSIVE DESIGN
===================================== */

@media (max-width: 650px) {

    .details-container {
        margin: 22px auto 35px;

        padding: 13px;
    }

    .details-container > h2 {
        padding: 20px 68px 20px 20px;

        border-radius: 17px;
    }

    .details-container > h2::after {
        right: 18px;

        font-size: 34px;
    }

    .details-card {
        padding: 21px 17px;

        border-radius: 18px;
    }

    .details-card::before {
        margin: -21px -17px 22px;
    }

    .details-card form {
        padding: 16px;
    }

    .details-card button,
    .back-link {
        width: 100%;

        box-sizing: border-box;
    }

}

</style>


<div class="details-container">

    <h2>Item Details</h2>


    <?php if (!empty($errors)) { ?>

        <div class="error-message">

            <?php foreach ($errors as $error) { ?>

                <p><?php echo htmlspecialchars($error); ?></p>

            <?php } ?>

        </div>

    <?php } ?>


    <?php if (!empty($success)) { ?>

        <div class="success-message">
            <?php echo htmlspecialchars($success); ?>
        </div>

    <?php } ?>


    <div class="details-card">


        <?php if (!empty($item["image"])) { ?>

            <img
                src="../uploads/items/<?php
                echo rawurlencode(basename($item["image"]));
                ?>"
                alt="Item image"
                width="800"
                height="500"
                class="details-image image-skeleton"
                data-loading-skeleton
                decoding="async"
            >

        <?php } else { ?>

            <div class="no-image">
                No Image Available
            </div>

        <?php } ?>


        <h3>
            <?php echo htmlspecialchars($item["item_name"]); ?>
        </h3>


        <p>
            <strong>Category:</strong>
            <?php echo htmlspecialchars($item["category_name"]); ?>
        </p>


        <p>
            <strong>Description:</strong>
            <?php echo nl2br(htmlspecialchars($item["description"])); ?>
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
            <strong>Reported By:</strong>
            <?php echo htmlspecialchars($item["reporter_name"]); ?>
        </p>


        <p>
            <strong>Item Type:</strong>

            <span class="item-type <?php
            echo htmlspecialchars($item["item_type"]);
            ?>">
                <?php echo ucfirst($item["item_type"]); ?>
            </span>

        </p>


        <hr>


        <?php if ($item["item_type"] === "found") { ?>

            <?php if ($is_owner) { ?>

                <p>You cannot claim your own found item.</p>

            <?php } else { ?>

                <h3>Claim This Item</h3>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php
                        echo htmlspecialchars($_SESSION["csrf_token"]);
                        ?>"
                    >


                    <label>
                        Why do you think this item belongs to you?
                    </label>


                    <textarea
                        name="message"
                        rows="4"
                        placeholder="Describe identifying details..."
                        required
                    ></textarea>


                    <button type="submit">
                        Submit Claim Request
                    </button>

                </form>

            <?php } ?>

        <?php } else { ?>

            <p>
                This is a lost item report.
                Review the recently found listings for possible matches.
            </p>

        <?php } ?>


        <a href="search.php" class="back-link">
            Back to Search
        </a>


    </div>

</div>


<?php include "../includes/footer.php"; ?>