<?php

require_once "config/database.php";
require_once "config/mail.php";
require_once "includes/mailer.php";

$message = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {

        $message = "Please enter your email address.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } else {

        // Find registered user
        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id, name FROM users WHERE email = ? LIMIT 1"
        );

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($user) {

                $user_id = (int) $user["user_id"];

                // Generate secure random token
                $token = bin2hex(random_bytes(32));

                // Store only the token hash
                $token_hash = hash("sha256", $token);

                // Delete previous reset requests for this user
                $delete_old_stmt = mysqli_prepare(
                    $conn,
                    "DELETE FROM password_resets WHERE user_id = ?"
                );

                if ($delete_old_stmt) {

                    mysqli_stmt_bind_param(
                        $delete_old_stmt,
                        "i",
                        $user_id
                    );

                    if (!mysqli_stmt_execute($delete_old_stmt)) {

                        error_log(
                            "Old reset token deletion failed: " .
                            mysqli_stmt_error($delete_old_stmt)
                        );
                    }

                    mysqli_stmt_close($delete_old_stmt);
                }

                /*
                 * IMPORTANT:
                 * MySQL itself calculates expiry time.
                 * This avoids PHP/MySQL timezone mismatch.
                 *
                 * Token expires exactly one hour after insertion.
                 */

                $insert_stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO password_resets
                    (user_id, token_hash, expires_at)
                    VALUES (
                        ?,
                        ?,
                        DATE_ADD(NOW(), INTERVAL 1 HOUR)
                    )"
                );

                if ($insert_stmt) {

                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "is",
                        $user_id,
                        $token_hash
                    );

                    if (mysqli_stmt_execute($insert_stmt)) {

                        mysqli_stmt_close($insert_stmt);

                        try {
                            $base_url = catchit_base_url();
                            $reset_url = $base_url === null
                                ? null
                                : $base_url . "/reset_password.php?token="
                                    . rawurlencode($token);

                            if (
                                $reset_url === null ||
                                !catchit_send_password_reset_email(
                                    $email,
                                    $user["name"],
                                    $reset_url
                                )
                            ) {
                                throw new RuntimeException(
                                    "Password reset email could not be sent."
                                );
                            }

                        } catch (Throwable $e) {

                            error_log("Password reset email delivery failed.");

                            // Remove unusable token if email fails
                            $cleanup_stmt = mysqli_prepare(
                                $conn,
                                "DELETE FROM password_resets
                                 WHERE user_id = ?
                                 AND token_hash = ?"
                            );

                            if ($cleanup_stmt) {

                                mysqli_stmt_bind_param(
                                    $cleanup_stmt,
                                    "is",
                                    $user_id,
                                    $token_hash
                                );

                                mysqli_stmt_execute($cleanup_stmt);
                                mysqli_stmt_close($cleanup_stmt);
                            }
                        }

                    } else {

                        error_log(
                            "Password reset token insertion failed: " .
                            mysqli_stmt_error($insert_stmt)
                        );

                        mysqli_stmt_close($insert_stmt);
                    }

                } else {

                    error_log(
                        "Password reset insert preparation failed: " .
                        mysqli_error($conn)
                    );
                }

            }

            // Do not reveal whether an email is registered
            $message =
                "If an account exists with this email address, " .
                "a password reset link will be sent shortly.";

        } else {

            error_log(
                "User lookup preparation failed: " .
                mysqli_error($conn)
            );

            $message =
                "Something went wrong. Please try again later.";
        }
    }
}

?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<style>

/* =================================
   FORGOT PASSWORD PAGE
================================= */

.forgot-page {
    min-height: calc(100vh - 120px);
    padding: 45px 20px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f3f6fc;
}

.forgot-card {
    width: 100%;
    max-width: 450px;

    padding: 40px;

    background: #ffffff;
    border-radius: 18px;

    box-shadow: 0 12px 40px rgba(20, 45, 85, 0.12);
    text-align: center;
}

.forgot-icon {
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

.forgot-card h2 {
    margin: 0 0 12px;

    color: #17365d;
    font-size: 27px;
}

.forgot-description {
    color: #718096;
    font-size: 14px;
    line-height: 1.7;

    margin-bottom: 28px;
}

.forgot-form {
    text-align: left;
}

.forgot-form label {
    display: block;

    margin-bottom: 8px;

    color: #34445a;
    font-size: 13px;
    font-weight: 600;
}

.forgot-form input {
    width: 100%;
    height: 48px;

    box-sizing: border-box;

    padding: 0 14px;

    border: 1px solid #d9e0eb;
    border-radius: 9px;

    background: #f9fbfe;
    color: #24344a;

    font-size: 14px;
    outline: none;

    transition: 0.25s ease;
}

.forgot-form input:focus {
    border-color: #2878c8;
    background: #ffffff;

    box-shadow: 0 0 0 3px rgba(40, 120, 200, 0.12);
}

.forgot-form input::placeholder {
    color: #a0a9b7;
}

.forgot-submit {
    width: 100%;

    margin-top: 20px;
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

.forgot-submit:hover {
    transform: translateY(-2px);

    box-shadow: 0 7px 18px rgba(36, 107, 178, 0.25);
}

.forgot-message {
    margin-bottom: 18px;
    padding: 12px;

    border-radius: 8px;

    background: #eaf8ef;
    border: 1px solid #bce8ca;

    color: #176b36;
    font-size: 13px;
    line-height: 1.6;
}

.back-login {
    margin-top: 24px;

    font-size: 14px;
    color: #778397;
}

.back-login a {
    color: #246bb2;
    font-weight: 700;
    text-decoration: none;
}

.back-login a:hover {
    text-decoration: underline;
}

/* =================================
   DARK MODE
================================= */

html[data-theme="dark"] .forgot-page {
    background: #101827;
}

html[data-theme="dark"] .forgot-card {
    background: #1b2638;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

html[data-theme="dark"] .forgot-icon {
    background: rgba(85, 156, 229, 0.15);
    color: #70b4ff;
}

html[data-theme="dark"] .forgot-card h2 {
    color: #f1f5fb;
}

html[data-theme="dark"] .forgot-description {
    color: #aab6c8;
}

html[data-theme="dark"] .forgot-form label {
    color: #dce5f2;
}

html[data-theme="dark"] .forgot-form input {
    background: #111c2d;
    border-color: #39475b;
    color: #f1f5fb;
}

html[data-theme="dark"] .forgot-form input::placeholder {
    color: #8794a8;
}

html[data-theme="dark"] .forgot-form input:focus {
    border-color: #559ce5;
    background: #152238;
}

html[data-theme="dark"] .forgot-message {
    background: #183629;
    color: #9be2b3;
    border-color: #285a40;
}

html[data-theme="dark"] .back-login {
    color: #aab6c8;
}

html[data-theme="dark"] .back-login a {
    color: #70b4ff;
}

/* =================================
   RESPONSIVE DESIGN
================================= */

@media (max-width: 480px) {

    .forgot-page {
        padding: 25px 14px;
    }

    .forgot-card {
        padding: 30px 22px;
    }

    .forgot-card h2 {
        font-size: 23px;
    }

}

</style>


<!-- =================================
     FORGOT PASSWORD CONTENT
================================= -->

<div class="forgot-page">

    <div class="forgot-card">

        <div class="forgot-icon">
            ✉
        </div>

        <h2>Forgot Your Password?</h2>

        <p class="forgot-description">
            Don't worry! Enter your registered email address.
            We will help you reset your password.
        </p>

        <?php if (!empty($message)) { ?>

            <div class="forgot-message">
                <?php echo htmlspecialchars($message); ?>
            </div>

        <?php } ?>

        <form method="POST" action="" class="forgot-form">

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo htmlspecialchars($email); ?>"
                placeholder="Enter your registered email"
                autocomplete="email"
                required
            >

            <button type="submit" class="forgot-submit">
                Send Reset Link →
            </button>

        </form>

        <p class="back-login">
            Remember your password?
            <a href="login.php">Back to Login</a>
        </p>

    </div>

</div>

<?php include "includes/footer.php"; ?>