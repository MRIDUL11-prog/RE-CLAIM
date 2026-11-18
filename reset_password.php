<?php

require_once "config/database.php";
require_once "config/mail.php";

$message = "";
$message_type = "";

$token = trim($_POST["token"] ?? $_GET["token"] ?? "");

$show_form = false;
$reset_success = false;

// Validate token format
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {

    $message = "Invalid or expired password reset link.";
    $message_type = "error";

} else {

    // Generate SHA-256 hash of received token
    $token_hash = hash("sha256", $token);

    // Check token validity and expiry
    $check_stmt = mysqli_prepare(
        $conn,
        "SELECT user_id
         FROM password_resets
         WHERE token_hash = ?
         AND expires_at > NOW()
         LIMIT 1"
    );

    if ($check_stmt) {

        mysqli_stmt_bind_param(
            $check_stmt,
            "s",
            $token_hash
        );

        mysqli_stmt_execute($check_stmt);

        $result = mysqli_stmt_get_result($check_stmt);

        $reset_record = mysqli_fetch_assoc($result);

        mysqli_stmt_close($check_stmt);

        if (!$reset_record) {

            $message = "This password reset link is invalid or has expired. Please request a new link.";
            $message_type = "error";

        } else {

            $show_form = true;

            // Process password reset
            if ($_SERVER["REQUEST_METHOD"] === "POST") {

                $password = $_POST["password"] ?? "";
                $confirm_password = $_POST["confirm_password"] ?? "";

                // Validate empty fields
                if (empty($password) || empty($confirm_password)) {

                    $message = "Please complete both password fields.";
                    $message_type = "error";

                }

                // Validate minimum password length
                elseif (strlen($password) < 6) {

                    $message = "Your password must contain at least 6 characters.";
                    $message_type = "error";

                }

                // Confirm password
                elseif ($password !== $confirm_password) {

                    $message = "The passwords do not match.";
                    $message_type = "error";

                } else {

                    // Hash new password
                    $hashed_password = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    try {

                        // Start database transaction
                        mysqli_begin_transaction($conn);

                        // Verify token again and lock its row
                        $lock_stmt = mysqli_prepare(
                            $conn,
                            "SELECT user_id
                             FROM password_resets
                             WHERE token_hash = ?
                             AND expires_at > NOW()
                             LIMIT 1
                             FOR UPDATE"
                        );

                        if (!$lock_stmt) {
                            throw new Exception("Could not verify reset token.");
                        }

                        mysqli_stmt_bind_param(
                            $lock_stmt,
                            "s",
                            $token_hash
                        );

                        mysqli_stmt_execute($lock_stmt);

                        $lock_result = mysqli_stmt_get_result($lock_stmt);

                        $locked_record = mysqli_fetch_assoc($lock_result);

                        mysqli_stmt_close($lock_stmt);

                        if (!$locked_record) {
                            throw new Exception("Reset link is invalid or expired.");
                        }

                        $user_id = (int) $locked_record["user_id"];

                        // Update password in users table
                        $update_stmt = mysqli_prepare(
                            $conn,
                            "UPDATE users
                             SET password = ?
                             WHERE user_id = ?"
                        );

                        if (!$update_stmt) {
                            throw new Exception("Could not prepare password update.");
                        }

                        mysqli_stmt_bind_param(
                            $update_stmt,
                            "si",
                            $hashed_password,
                            $user_id
                        );

                        if (!mysqli_stmt_execute($update_stmt)) {

                            mysqli_stmt_close($update_stmt);

                            throw new Exception("Password update failed.");
                        }

                        mysqli_stmt_close($update_stmt);

                        // Delete used reset token
                        $delete_stmt = mysqli_prepare(
                            $conn,
                            "DELETE FROM password_resets
                             WHERE user_id = ?"
                        );

                        if (!$delete_stmt) {
                            throw new Exception("Could not prepare token deletion.");
                        }

                        mysqli_stmt_bind_param(
                            $delete_stmt,
                            "i",
                            $user_id
                        );

                        if (!mysqli_stmt_execute($delete_stmt)) {

                            mysqli_stmt_close($delete_stmt);

                            throw new Exception("Could not delete reset token.");
                        }

                        mysqli_stmt_close($delete_stmt);

                        // Commit transaction
                        mysqli_commit($conn);

                        $show_form = false;
                        $reset_success = true;

                        $message = "Your password has been reset successfully! You can now sign in with your new password.";
                        $message_type = "success";

                    } catch (Throwable $e) {

                        mysqli_rollback($conn);

                        error_log(
                            "Password reset error: " . $e->getMessage()
                        );

                        $show_form = false;

                        $message = "Something went wrong or your reset link has expired. Please request a new reset link.";
                        $message_type = "error";
                    }
                }
            }
        }

    } else {

        error_log("Password reset query preparation failed.");

        $message = "Something went wrong. Please try again later.";
        $message_type = "error";
    }
}

?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<style>

/* =================================
   RESET PASSWORD PAGE
================================= */

.reset-page {
    min-height: calc(100vh - 120px);
    padding: 45px 20px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f3f6fc;
}

.reset-card {
    width: 100%;
    max-width: 450px;

    padding: 40px;

    background: #ffffff;
    border-radius: 18px;

    box-shadow: 0 12px 40px rgba(20, 45, 85, 0.12);

    text-align: center;
}

.reset-icon {
    width: 65px;
    height: 65px;

    margin: 0 auto 22px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 18px;

    background: #e7f0fc;
    color: #174d85;

    font-size: 28px;
}

.reset-card h2 {
    margin: 0 0 12px;

    color: #17365d;
    font-size: 27px;
}

.reset-description {
    color: #718096;
    font-size: 14px;
    line-height: 1.7;

    margin-bottom: 28px;
}

.reset-form {
    text-align: left;
}

.reset-form label {
    display: block;

    margin-bottom: 8px;

    color: #34445a;
    font-size: 13px;
    font-weight: 600;
}

.reset-input-wrapper {
    position: relative;
    margin-bottom: 18px;
}

.reset-input-wrapper input {
    width: 100%;
    height: 48px;

    box-sizing: border-box;

    padding: 0 48px 0 14px;

    border: 1px solid #d9e0eb;
    border-radius: 9px;

    background: #f9fbfe;
    color: #24344a;

    font-size: 14px;
    outline: none;

    transition: 0.25s ease;
}

.reset-input-wrapper input:focus {
    border-color: #2878c8;
    background: #ffffff;

    box-shadow: 0 0 0 3px rgba(40, 120, 200, 0.12);
}

.reset-input-wrapper input::placeholder {
    color: #a0a9b7;
}

/* PASSWORD VISIBILITY BUTTON */

.reset-toggle-password {
    position: absolute;

    right: 7px;
    top: 50%;

    transform: translateY(-50%);

    width: 36px;
    height: 36px;

    border: none;
    background: transparent;

    color: #718096;

    cursor: pointer;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 6px;
}

.reset-toggle-password:hover {
    background: rgba(40, 120, 200, 0.10);
    color: #246bb2;
}

.reset-toggle-password:focus-visible {
    outline: 2px solid #2878c8;
    outline-offset: 2px;
}

.reset-toggle-password svg {
    width: 21px;
    height: 21px;
    display: block;
}

/* SUBMIT BUTTON */

.reset-submit {
    width: 100%;

    margin-top: 5px;
    padding: 14px;

    border: none;
    border-radius: 9px;

    background: linear-gradient(135deg, #2878c8, #174d85);
    color: #ffffff;

    font-size: 15px;
    font-weight: 700;

    cursor: pointer;
    transition: 0.25s ease;
}

.reset-submit:hover {
    transform: translateY(-2px);

    box-shadow: 0 7px 18px rgba(36, 107, 178, 0.25);
}

/* MESSAGE */

.reset-message {
    margin-bottom: 20px;
    padding: 13px;

    border-radius: 8px;

    font-size: 13px;
    line-height: 1.6;
}

.reset-message.error {
    background: #fff0f0;
    border: 1px solid #ffcaca;
    color: #b00020;
}

.reset-message.success {
    background: #eaf8ef;
    border: 1px solid #bce8ca;
    color: #176b36;
}

/* LOGIN LINK */

.reset-login {
    margin-top: 24px;

    font-size: 14px;
    color: #778397;
}

.reset-login a {
    color: #246bb2;
    font-weight: 700;
    text-decoration: none;
}

.reset-login a:hover {
    text-decoration: underline;
}

/* =================================
   DARK MODE
================================= */

html[data-theme="dark"] .reset-page {
    background: #101827;
}

html[data-theme="dark"] .reset-card {
    background: #1b2638;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

html[data-theme="dark"] .reset-icon {
    background: rgba(85, 156, 229, 0.15);
    color: #70b4ff;
}

html[data-theme="dark"] .reset-card h2 {
    color: #f1f5fb;
}

html[data-theme="dark"] .reset-description {
    color: #aab6c8;
}

html[data-theme="dark"] .reset-form label {
    color: #dce5f2;
}

html[data-theme="dark"] .reset-input-wrapper input {
    background: #111c2d;
    border-color: #39475b;
    color: #f1f5fb;
}

html[data-theme="dark"] .reset-input-wrapper input::placeholder {
    color: #8794a8;
}

html[data-theme="dark"] .reset-input-wrapper input:focus {
    border-color: #559ce5;
    background: #152238;
}

html[data-theme="dark"] .reset-toggle-password {
    color: #aab6c8;
}

html[data-theme="dark"] .reset-toggle-password:hover {
    background: rgba(85, 156, 229, 0.15);
    color: #70b4ff;
}

html[data-theme="dark"] .reset-message.error {
    background: #3a2028;
    color: #ffb6c1;
    border-color: #713744;
}

html[data-theme="dark"] .reset-message.success {
    background: #183629;
    color: #9be2b3;
    border-color: #285a40;
}

html[data-theme="dark"] .reset-login {
    color: #aab6c8;
}

html[data-theme="dark"] .reset-login a {
    color: #70b4ff;
}

/* =================================
   RESPONSIVE DESIGN
================================= */

@media (max-width: 480px) {

    .reset-page {
        padding: 25px 14px;
    }

    .reset-card {
        padding: 30px 22px;
    }

    .reset-card h2 {
        font-size: 23px;
    }

}

</style>


<!-- =================================
     RESET PASSWORD CONTENT
================================= -->

<div class="reset-page">

    <div class="reset-card">

        <div class="reset-icon">
            🔐
        </div>

        <h2>Reset Your Password</h2>

        <p class="reset-description">
            Create a new password for your Re:Claim account.
            Your new password must contain at least 6 characters.
        </p>

        <?php if (!empty($message)) { ?>

            <div class="reset-message <?php echo htmlspecialchars($message_type); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>


        <?php if ($show_form && !$reset_success) { ?>

            <form method="POST" action="" class="reset-form">

                <input
                    type="hidden"
                    name="token"
                    value="<?php echo htmlspecialchars($token); ?>"
                >

                <label for="password">New Password</label>

                <div class="reset-input-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter new password"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="reset-toggle-password"
                        data-password-toggle="password"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             stroke-linecap="round"
                             stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>

                </div>


                <label for="confirm_password">Confirm New Password</label>

                <div class="reset-input-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="button"
                        class="reset-toggle-password"
                        data-password-toggle="confirm_password"
                        aria-label="Show confirm password"
                        aria-pressed="false"
                    >
                        <svg viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="2"
                             stroke-linecap="round"
                             stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>

                </div>


                <button type="submit" class="reset-submit">
                    Update Password →
                </button>

            </form>

        <?php } ?>


        <p class="reset-login">

            <?php if ($reset_success) { ?>

                <a href="login.php">Sign In to Your Account →</a>

            <?php } elseif (!$show_form) { ?>

                <a href="forgot_password.php">Request a New Reset Link</a>

            <?php } else { ?>

                Remember your password?
                <a href="login.php">Back to Login</a>

            <?php } ?>

        </p>

    </div>

</div>




<?php include "includes/footer.php"; ?>