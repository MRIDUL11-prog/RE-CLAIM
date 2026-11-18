
<?php

require_once "../includes/auth.php";
checkStudent();

require_once "../config/database.php";

$user_id = $_SESSION["user_id"];

$errors = [];
$success = $_SESSION["profile_success"] ?? "";
unset($_SESSION["profile_success"]);
$is_editing = isset($_GET["edit"]) && $_GET["edit"] === "1";

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Fetch current profile
$sql = "SELECT * FROM users WHERE user_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    die("User profile not found.");
}

// Process profile update
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $is_editing = true;
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {

        $errors[] = "Invalid request. Please refresh the page.";

    } else {

        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $student_id = trim($_POST["student_id"] ?? "");
        $department = trim($_POST["department"] ?? "");

        $new_password = $_POST["new_password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if (
            $name === "" ||
            $email === "" ||
            $student_id === "" ||
            $department === ""
        ) {
            $errors[] = "All profile fields are required.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Enter a valid email address.";
        }

        $change_password = (
            $new_password !== "" || $confirm_password !== ""
        );

        if ($change_password) {

            if (strlen($new_password) < 6) {
                $errors[] = "New password must have at least 6 characters.";
            }

            if ($new_password !== $confirm_password) {
                $errors[] = "Passwords do not match.";
            }
        }

        // Check duplicate email or student ID
        if (empty($errors)) {

            $check_sql = "SELECT user_id FROM users
                          WHERE (email = ? OR student_id = ?)
                          AND user_id != ?";

            $check_stmt = mysqli_prepare($conn, $check_sql);

            mysqli_stmt_bind_param(
                $check_stmt,
                "ssi",
                $email,
                $student_id,
                $user_id
            );

            mysqli_stmt_execute($check_stmt);

            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $errors[] = "Email or Student ID is already in use.";
            }

            mysqli_stmt_close($check_stmt);
        }

        // Update profile
        if (empty($errors)) {

            if ($change_password) {

                $hashed_password = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

                $update_sql = "UPDATE users
                               SET name = ?,
                                   email = ?,
                                   student_id = ?,
                                   department = ?,
                                   password = ?
                               WHERE user_id = ?";

                $update_stmt = mysqli_prepare($conn, $update_sql);

                mysqli_stmt_bind_param(
                    $update_stmt,
                    "sssssi",
                    $name,
                    $email,
                    $student_id,
                    $department,
                    $hashed_password,
                    $user_id
                );

            } else {

                $update_sql = "UPDATE users
                               SET name = ?,
                                   email = ?,
                                   student_id = ?,
                                   department = ?
                               WHERE user_id = ?";

                $update_stmt = mysqli_prepare($conn, $update_sql);

                mysqli_stmt_bind_param(
                    $update_stmt,
                    "ssssi",
                    $name,
                    $email,
                    $student_id,
                    $department,
                    $user_id
                );
            }

            if (mysqli_stmt_execute($update_stmt)) {

                $_SESSION["name"] = $name;
                $_SESSION["email"] = $email;

                $_SESSION["profile_success"] =
                    "Your profile has been updated.";

                header("Location: profile.php");
                exit();

            } else {

                $errors[] = "Unable to update profile.";
            }

            mysqli_stmt_close($update_stmt);
        }
    }
}

?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<style>

/* =========================================
   RE:CLAIM PROFILE PAGE
========================================= */

.profile-page {
    width: min(1120px, calc(100% - 40px));
    margin: 0 auto;
    padding: clamp(34px, 5vw, 66px) 0 76px;
    color: #172b49;
}

.profile-page-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 30px;
}

.profile-page-heading .dashboard-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
    color: #2878c8;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2.2px;
}

.profile-page-heading .dashboard-eyebrow::before {
    content: "";
    width: 24px;
    height: 3px;
    border-radius: 10px;
    background: linear-gradient(90deg, #2878c8, #8b5cf6);
}

.profile-page-heading h1 {
    margin: 0;
    color: #17365d;
    font-size: clamp(34px, 5vw, 48px);
    line-height: 1.08;
    font-weight: 850;
    letter-spacing: -1.8px;
}

.profile-page-heading > div > p:last-child {
    margin: 12px 0 0;
    color: #718096;
    font-size: 15px;
    line-height: 1.7;
}

.profile-back-link,
.profile-secondary-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 43px;
    padding: 0 17px;
    border: 1px solid #dce6f2;
    border-radius: 12px;
    background: #fff;
    color: #31577f;
    font-size: 13px;
    font-weight: 750;
    text-decoration: none;
    transition: .2s ease;
}

.profile-back-link:hover,
.profile-secondary-action:hover {
    transform: translateY(-2px);
    border-color: #9fc5ec;
    box-shadow: 0 8px 20px rgba(34, 91, 148, .10);
}

.profile-card {
    position: relative;
    overflow: hidden;
    padding: clamp(24px, 4vw, 38px);
    border: 1px solid rgba(206, 222, 240, .85);
    border-radius: 24px;
    background: linear-gradient(
        145deg,
        #ffffff 0%,
        #fbfdff 75%,
        #f2f7ff 100%
    );
    box-shadow: 0 18px 55px rgba(30, 67, 112, .09);
}

.profile-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(
        90deg,
        #2878c8,
        #6d7ff2,
        #a78bfa
    );
}

.profile-overview-header {
    display: flex;
    align-items: center;
    gap: 20px;
    padding-bottom: 28px;
    border-bottom: 1px solid #e8eef6;
}

.profile-large-avatar {
    display: grid;
    flex: 0 0 82px;
    width: 82px;
    height: 82px;
    place-items: center;
    border: 5px solid #fff;
    border-radius: 25px;
    background: linear-gradient(135deg, #2878c8, #735ce7);
    box-shadow: 0 9px 24px rgba(57, 105, 190, .25);
    color: #fff;
    font-size: 34px;
    font-weight: 850;
}

.profile-account-type {
    margin: 0 0 7px;
    color: #2878c8;
    font-size: 11px;
    font-weight: 850;
    letter-spacing: 1.8px;
}

.profile-overview-header h2 {
    margin: 0;
    color: #17365d;
    font-size: clamp(22px, 3vw, 29px);
    font-weight: 850;
    letter-spacing: -.7px;
}

.profile-overview-header > div > p:last-child {
    margin: 7px 0 0;
    color: #718096;
    font-size: 14px;
    overflow-wrap: anywhere;
}

.profile-edit-action,
.profile-save-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 45px;
    padding: 0 20px;
    border: 0;
    border-radius: 12px;
    background: linear-gradient(135deg, #2878c8, #174d85);
    box-shadow: 0 8px 18px rgba(40, 120, 200, .20);
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
}

.profile-edit-action {
    margin-left: auto;
    white-space: nowrap;
}

.profile-edit-action:hover,
.profile-save-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(40, 120, 200, .28);
}

.profile-details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
    padding-top: 26px;
}

.profile-detail {
    min-width: 0;
    padding: 19px 20px;
    border: 1px solid #e6edf6;
    border-radius: 16px;
    background: rgba(255,255,255,.8);
    transition: .2s ease;
}

.profile-detail:hover {
    transform: translateY(-3px);
    border-color: #c8ddf3;
    box-shadow: 0 10px 22px rgba(35, 79, 128, .07);
}

.profile-detail span {
    display: block;
    margin-bottom: 9px;
    color: #8190a4;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.1px;
    text-transform: uppercase;
}

.profile-detail strong {
    display: block;
    color: #203c60;
    font-size: 15px;
    font-weight: 750;
    line-height: 1.55;
    overflow-wrap: anywhere;
}

/* Edit Profile */

.profile-card-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 27px;
}

.profile-card-heading h2 {
    margin: 0;
    color: #17365d;
    font-size: 27px;
    font-weight: 850;
    letter-spacing: -.8px;
}

.profile-card-heading p {
    margin: 8px 0 0;
    color: #718096;
    font-size: 14px;
    line-height: 1.6;
}

.profile-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.profile-field label {
    display: block;
    margin-bottom: 9px;
    color: #344b68;
    font-size: 13px;
    font-weight: 800;
}

.profile-field input {
    width: 100%;
    min-height: 49px;
    box-sizing: border-box;
    padding: 0 15px;
    border: 1px solid #dce5f0;
    border-radius: 12px;
    outline: none;
    background: #f9fbfe;
    color: #203c60;
    font: inherit;
    font-size: 14px;
    transition: .2s ease;
}

.profile-field input:focus {
    border-color: #438bd0;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(40, 120, 200, .12);
}

.profile-password-field {
    position: relative;
}

.profile-password-field input {
    padding-right: 54px;
}

.profile-password-toggle {
    position: absolute;
    top: 50%;
    right: 8px;
    display: grid;
    width: 36px;
    height: 36px;
    padding: 0;
    place-items: center;
    transform: translateY(-50%);
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    transition: background 180ms ease, color 180ms ease, transform 180ms ease;
}

.profile-password-toggle:hover {
    background: rgba(102, 102, 255, 0.1);
    color: #5050c9;
}

.profile-password-toggle:focus-visible {
    outline: 2px solid #6666ff;
    outline-offset: 2px;
}

.profile-password-section {
    margin-top: 30px;
    padding: 24px;
    border: 1px solid #e3eaf4;
    border-radius: 17px;
    background: #f7faff;
}

.profile-password-section h3 {
    margin: 0;
    color: #17365d;
    font-size: 18px;
    font-weight: 850;
}

.profile-password-section > p {
    margin: 7px 0 20px;
    color: #718096;
    font-size: 13px;
    line-height: 1.6;
}

.profile-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 28px;
}

/* Messages */

.profile-notice {
    margin: 0 0 20px;
    padding: 15px 18px;
    border-radius: 13px;
    font-size: 14px;
    line-height: 1.6;
}

.profile-notice p {
    margin: 3px 0;
}

/* Animation */

.profile-page [data-reveal] {
    animation: reclaimProfileRise .55s ease both;
}

@keyframes reclaimProfileRise {
    from {
        opacity: 0;
        transform: translateY(12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Dark Mode */

html[data-theme="dark"] .profile-page {
    color: #e8f0fb;
}

html[data-theme="dark"] .profile-page-heading h1,
html[data-theme="dark"] .profile-overview-header h2,
html[data-theme="dark"] .profile-card-heading h2,
html[data-theme="dark"] .profile-password-section h3 {
    color: #f0f5ff;
}

html[data-theme="dark"] .profile-page-heading > div > p:last-child,
html[data-theme="dark"] .profile-overview-header > div > p:last-child,
html[data-theme="dark"] .profile-card-heading p,
html[data-theme="dark"] .profile-password-section > p {
    color: #aab9ce;
}

html[data-theme="dark"] .profile-card {
    border-color: #34445b;
    background: linear-gradient(145deg, #1b293d, #172438);
    box-shadow: 0 18px 55px rgba(0,0,0,.22);
}

html[data-theme="dark"] .profile-overview-header {
    border-color: #34445b;
}

html[data-theme="dark"] .profile-detail,
html[data-theme="dark"] .profile-password-section {
    border-color: #34445b;
    background: #202f45;
}

html[data-theme="dark"] .profile-detail strong,
html[data-theme="dark"] .profile-field label {
    color: #e0eafa;
}

html[data-theme="dark"] .profile-detail span {
    color: #9aabc2;
}

html[data-theme="dark"] .profile-field input {
    border-color: #3b4c65;
    background: #142238;
    color: #f0f5ff;
}

html[data-theme="dark"] .profile-field input:focus {
    border-color: #70b4ff;
    background: #182a43;
}

html[data-theme="dark"] .profile-password-toggle {
    color: #b8baff;
}

html[data-theme="dark"] .profile-password-toggle:hover {
    background: rgba(184, 186, 255, 0.12);
    color: #c9e8ff;
}

html[data-theme="dark"] .profile-back-link,
html[data-theme="dark"] .profile-secondary-action {
    border-color: #3b4c65;
    background: #202f45;
    color: #d8e7fa;
}

/* Responsive */

@media (max-width: 700px) {

    .profile-page {
        width: min(100% - 28px, 560px);
        padding-top: 30px;
    }

    .profile-page-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 16px;
    }

    .profile-overview-header {
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .profile-edit-action {
        margin-left: 0;
    }

    .profile-details-grid,
    .profile-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 440px) {

    .profile-card {
        padding: 22px 17px;
        border-radius: 19px;
    }

    .profile-large-avatar {
        flex-basis: 66px;
        width: 66px;
        height: 66px;
        border-radius: 20px;
        font-size: 28px;
    }

    .profile-overview-header {
        gap: 14px;
    }

    .profile-overview-header > div:nth-child(2) {
        min-width: 0;
        flex: 1;
    }

    .profile-form-actions {
        flex-direction: column-reverse;
    }

    .profile-form-actions > * {
        width: 100%;
        box-sizing: border-box;
    }

    .profile-password-section {
        padding: 18px 14px;
    }
}

</style>


<!-- =========================================
     PROFILE PAGE CONTENT
========================================= -->

<main class="profile-page">

    <header class="profile-page-heading" data-reveal>

        <div>

            <p class="dashboard-eyebrow">YOUR ACCOUNT</p>

            <h1>My Profile</h1>

            <p>
                View and manage your Re:Claim account information.
            </p>

        </div>

        <a href="dashboard.php" class="profile-back-link">
            Back to overview
        </a>

    </header>


    <?php if (!empty($errors)) { ?>

        <div class="error-message profile-notice" role="alert">

            <?php foreach ($errors as $error) { ?>

                <p>
                    <?php echo htmlspecialchars($error); ?>
                </p>

            <?php } ?>

        </div>

    <?php } ?>


    <?php if (!empty($success)) { ?>

        <div class="success-message profile-notice" role="status">

            <?php echo htmlspecialchars($success); ?>

        </div>

    <?php } ?>


    <?php if ($is_editing) { ?>

        <!-- EDIT PROFILE -->

        <section class="profile-card profile-edit-card" data-reveal>

            <div class="profile-card-heading">

                <div>

                    <h2>Edit Profile</h2>

                    <p>
                        Update your contact and student information.
                    </p>

                </div>

                <a href="profile.php" class="profile-secondary-action">
                    Cancel
                </a>

            </div>


            <form method="POST" action="profile.php?edit=1">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
                >


                <div class="profile-form-grid">

                    <div class="profile-field">

                        <label for="profile-name">
                            Full Name
                        </label>

                        <input
                            id="profile-name"
                            type="text"
                            name="name"
                            value="<?php echo htmlspecialchars($_POST["name"] ?? $user["name"]); ?>"
                            autocomplete="name"
                            required
                        >

                    </div>


                    <div class="profile-field">

                        <label for="profile-email">
                            Email Address
                        </label>

                        <input
                            id="profile-email"
                            type="email"
                            name="email"
                            value="<?php echo htmlspecialchars($_POST["email"] ?? $user["email"]); ?>"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <div class="profile-field">

                        <label for="profile-student-id">
                            Student ID
                        </label>

                        <input
                            id="profile-student-id"
                            type="text"
                            name="student_id"
                            value="<?php echo htmlspecialchars($_POST["student_id"] ?? $user["student_id"]); ?>"
                            required
                        >

                    </div>


                    <div class="profile-field">

                        <label for="profile-department">
                            Department
                        </label>

                        <input
                            id="profile-department"
                            type="text"
                            name="department"
                            value="<?php echo htmlspecialchars($_POST["department"] ?? $user["department"]); ?>"
                            required
                        >

                    </div>

                </div>


                <!-- CHANGE PASSWORD -->

                <div class="profile-password-section">

                    <h3>Change Password</h3>

                    <p>
                        Leave these fields blank to keep your current password.
                    </p>


                    <div class="profile-form-grid">

                        <div class="profile-field">

                            <label for="profile-new-password">
                                New Password
                            </label>

                            <div class="profile-password-field">
                                <input
                                    id="profile-new-password"
                                    type="password"
                                    name="new_password"
                                    autocomplete="new-password"
                                    placeholder="At least 6 characters"
                                >
                                <button
                                    type="button"
                                    class="profile-password-toggle"
                                    data-password-toggle="profile-new-password"
                                    aria-label="Show new password"
                                    aria-pressed="false"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>

                        </div>


                        <div class="profile-field">

                            <label for="profile-confirm-password">
                                Confirm New Password
                            </label>

                            <div class="profile-password-field">
                                <input
                                    id="profile-confirm-password"
                                    type="password"
                                    name="confirm_password"
                                    autocomplete="new-password"
                                    placeholder="Re-enter new password"
                                >
                                <button
                                    type="button"
                                    class="profile-password-toggle"
                                    data-password-toggle="profile-confirm-password"
                                    aria-label="Show confirm password"
                                    aria-pressed="false"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round"
                                         stroke-linejoin="round" aria-hidden="true">
                                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>

                        </div>

                    </div>

                </div>


                <div class="profile-form-actions">

                    <a href="profile.php" class="profile-secondary-action">
                        Cancel
                    </a>

                    <button type="submit" class="profile-save-action">
                        Save Changes
                    </button>

                </div>

            </form>

        </section>


    <?php } else { ?>

        <!-- PROFILE OVERVIEW -->

        <section class="profile-card profile-overview-card" data-reveal>

            <div class="profile-overview-header">

                <div class="profile-large-avatar" aria-hidden="true">

                    <?php
                    echo htmlspecialchars(
                        strtoupper(substr($user["name"], 0, 1))
                    );
                    ?>

                </div>


                <div>

                    <p class="profile-account-type">
                        STUDENT ACCOUNT
                    </p>

                    <h2>
                        <?php echo htmlspecialchars($user["name"]); ?>
                    </h2>

                    <p>
                        <?php echo htmlspecialchars($user["email"]); ?>
                    </p>

                </div>


                <a href="profile.php?edit=1" class="profile-edit-action">
                    Edit Profile
                </a>

            </div>


            <div class="profile-details-grid">

                <div class="profile-detail">

                    <span>Full Name</span>

                    <strong>
                        <?php echo htmlspecialchars($user["name"]); ?>
                    </strong>

                </div>


                <div class="profile-detail">

                    <span>Email Address</span>

                    <strong>
                        <?php echo htmlspecialchars($user["email"]); ?>
                    </strong>

                </div>


                <div class="profile-detail">

                    <span>Student ID</span>

                    <strong>
                        <?php echo htmlspecialchars($user["student_id"]); ?>
                    </strong>

                </div>


                <div class="profile-detail">

                    <span>Department</span>

                    <strong>
                        <?php echo htmlspecialchars($user["department"]); ?>
                    </strong>

                </div>

            </div>

        </section>

    <?php } ?>

</main>


<?php include "../includes/footer.php"; ?>
