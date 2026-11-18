<?php
require_once __DIR__ . "/includes/session.php";

require_once "config/database.php";

$error = "";
$email = "";

$password_reset_success = !empty($_SESSION["password_reset_success"]);
unset($_SESSION["password_reset_success"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {

        $error = "Enter your email address and password to continue.";

    } else {

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 1) {

                $user = mysqli_fetch_assoc($result);

                if (password_verify($password, $user["password"])) {

                    session_regenerate_id(true);

                    $_SESSION["user_id"] = $user["user_id"];
                    $_SESSION["name"] = $user["name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];

                    if ($user["role"] === "admin") {

                        header("Location: admin/dashboard.php");
                        exit();

                    } elseif ($user["role"] === "student") {

                        header("Location: student/dashboard.php");
                        exit();

                    } else {

                        $error = "Invalid user role.";
                    }

                } else {

                    $error = "Incorrect email or password.";
                }

            } else {

                $error = "Incorrect email or password.";
            }

            mysqli_stmt_close($stmt);

        } else {

            $error = "Something went wrong. Please try again.";
        }
    }
}
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<style>

/* =================================
   RE:CLAIM LOGIN PAGE
   Original Design Preserved
================================= */

:root {
    --primary: #4f46e5;
    --secondary: #0891b2;
    --dark: #172554;
    --muted: #64748b;
}

/* Main Background */

body {
    min-height: 100vh;

    background:
        radial-gradient(
            ellipse at 12% 12%,
            rgba(129,140,248,.20),
            transparent 34%
        ),
        radial-gradient(
            ellipse at 88% 80%,
            rgba(34,211,238,.18),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f4f7ff,
            #eef7ff,
            #f7f4ff
        );
}

/* Main Page */

.auth-page {
    position: relative;
    isolation: isolate;

    display: flex;
    align-items: center;
    justify-content: center;

    min-height: min(760px, calc(100vh - 120px));

    padding: 54px 22px 64px;

    overflow: hidden;
}

.auth-page::before,
.auth-page::after {
    content: "";

    position: absolute;
    z-index: -1;

    border-radius: 50%;

    pointer-events: none;
}

.auth-page::before {
    width: 270px;
    height: 270px;

    top: 5%;
    left: 5%;

    background: linear-gradient(
        135deg,
        rgba(99,102,241,.16),
        rgba(56,189,248,.08)
    );
}

.auth-page::after {
    width: 340px;
    height: 340px;

    right: 3%;
    bottom: 0;

    background: linear-gradient(
        135deg,
        rgba(14,165,233,.12),
        rgba(167,139,250,.13)
    );
}

/* Layout */

.auth-layout {
    width: 100%;
    max-width: 1040px;

    display: grid;

    grid-template-columns:
        minmax(0,1fr)
        minmax(360px,470px);

    align-items: center;

    gap: clamp(32px,6vw,76px);
}

/* Left Section */

.auth-intro {
    padding: 22px 0;
    color: var(--dark);
}

.auth-brand {
    display: inline-flex;
    align-items: center;

    gap: 10px;
    margin-bottom: 25px;

    color: #4338ca;

    font-size: 14px;
    font-weight: 800;

    letter-spacing: .12em;
    text-transform: uppercase;
}

.auth-intro h1 {
    max-width: 520px;

    margin: 0 0 18px;

    color: #172554;

    font-size: clamp(35px,4.5vw,54px);

    line-height: 1.08;

    font-weight: 850;

    letter-spacing: -.045em;
}

.auth-intro h1 span {
    color: #4f46e5;
}

.auth-intro > p {
    max-width: 470px;

    margin: 0;

    color: #64748b;

    font-size: 17px;
    line-height: 1.75;
}

/* Features */

.auth-points {
    display: grid;

    gap: 14px;

    margin: 30px 0 0;
    padding: 0;

    list-style: none;
}

.auth-points li {
    display: flex;
    align-items: center;

    gap: 12px;

    color: #334155;

    font-size: 14px;
    font-weight: 600;
}

.auth-point-icon {
    display: grid;

    flex: 0 0 28px;

    width: 28px;
    height: 28px;

    place-items: center;

    border-radius: 9px;

    color: #0e7490;
    background: #cffafe;
}

/* Login Card */

.auth-card {
    width: 100%;

    box-sizing: border-box;

    padding: clamp(28px,4vw,42px);

    border: 1px solid rgba(255,255,255,.85);

    border-radius: 26px;

    background: rgba(255,255,255,.90);

    box-shadow:
        0 24px 70px rgba(30,41,99,.13),
        0 4px 14px rgba(30,41,99,.05);

    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.auth-card-header {
    margin-bottom: 28px;
}

.auth-card-kicker {
    display: inline-block;

    margin-bottom: 12px;

    padding: 6px 11px;

    border-radius: 999px;

    color: #4338ca;
    background: #eef2ff;

    font-size: 11px;
    font-weight: 800;

    letter-spacing: .1em;
    text-transform: uppercase;
}

.auth-card h2 {
    margin: 0 0 9px;

    color: #172554;

    font-size: 29px;
    font-weight: 800;

    letter-spacing: -.035em;
}

.auth-card-header p {
    margin: 0;

    color: #64748b;

    font-size: 14px;
    line-height: 1.6;
}

/* Form */

.auth-field {
    margin-bottom: 19px;
}

.auth-field label {
    display: block;

    margin-bottom: 8px;

    color: #334155;

    font-size: 13px;
    font-weight: 750;
}

/* Input Fields */

.auth-field input {
    display: block;

    width: 100%;
    min-height: 50px;

    box-sizing: border-box;

    padding: 13px 15px;

    border: 1.5px solid #dbe3f0;

    border-radius: 12px;

    outline: none;

    color: #172554;
    background: #f8faff;

    font: inherit;
    font-size: 14px;

    transition: .2s ease;
}

.auth-field input::placeholder {
    color: #9aa8bd;
}

.auth-field input:hover {
    border-color: #b7c5df;
}

.auth-field input:focus {
    border-color: #6366f1;

    background: white;

    box-shadow:
        0 0 0 4px rgba(99,102,241,.12);
}

/* Password Field Wrapper */

.password-input-wrap {
    position: relative;
    width: 100%;
}

.password-input-wrap input {
    padding-right: 52px;
}

/* Eye Button */

.password-toggle {
    position: absolute;

    top: 50%;
    right: 12px;

    transform: translateY(-50%);

    display: flex;
    align-items: center;
    justify-content: center;

    width: 34px;
    height: 34px;

    padding: 0;

    border: none;
    border-radius: 8px;

    color: #64748b;
    background: transparent;

    cursor: pointer;

    z-index: 2;
}

.password-toggle:hover {
    color: #4f46e5;
    background: #eef2ff;
}

.password-toggle:focus-visible {
    outline: 2px solid #6366f1;
    outline-offset: 2px;
}

.password-toggle svg {
    width: 21px;
    height: 21px;

    pointer-events: none;
}

/* Sign In Button */

.auth-submit {
    display: flex;

    width: 100%;
    min-height: 52px;

    align-items: center;
    justify-content: center;

    margin-top: 8px;

    border: 0;
    border-radius: 12px;

    color: white;

    background: linear-gradient(
        110deg,
        #4f46e5,
        #2563eb,
        #0891b2
    );

    box-shadow: 0 10px 22px rgba(79,70,229,.22);

    font-size: 15px;
    font-weight: 800;

    cursor: pointer;

    transition: .2s ease;
}

.auth-submit:hover {
    transform: translateY(-2px);

    box-shadow: 0 14px 26px rgba(79,70,229,.28);
}

/* Messages */

.auth-message {
    margin-bottom: 18px;

    padding: 12px 14px;

    border-radius: 11px;

    font-size: 13px;
    line-height: 1.5;
}

.auth-error {
    border: 1px solid #fecaca;

    color: #991b1b;
    background: #fff1f2;
}

.auth-success {
    border: 1px solid #a7f3d0;

    color: #065f46;
    background: #ecfdf5;
}

/* Links */

.auth-links {
    display: grid;

    gap: 17px;

    margin-top: 23px;

    text-align: center;
}

.auth-links p {
    margin: 0;

    color: #64748b;

    font-size: 13px;
}

.auth-links a {
    color: #4f46e5;

    font-weight: 750;

    text-decoration: none;
}

.auth-links a:hover {
    color: #0891b2;
    text-decoration: underline;
}

.auth-links .create-account {
    padding-top: 17px;

    border-top: 1px solid #e8edf5;
}

/* Responsive Design */

@media (max-width: 780px) {

    .auth-page {
        min-height: auto;
        padding: 38px 16px 48px;
    }

    .auth-layout {
        max-width: 480px;
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .auth-intro {
        padding: 0;
        text-align: center;
    }

    .auth-brand {
        margin-bottom: 16px;
    }

    .auth-intro h1 {
        margin-left: auto;
        margin-right: auto;

        font-size: clamp(32px,9vw,43px);
    }

    .auth-intro > p {
        margin: 0 auto;
        font-size: 15px;
    }

    .auth-points {
        display: none;
    }

    .auth-card {
        padding: 28px 23px;
        border-radius: 22px;
    }
}

@media (max-width: 380px) {

    .auth-card h2 {
        font-size: 25px;
    }

    .auth-card {
        padding: 24px 18px;
    }
}

/* =================================
   DARK MODE
   Uses existing Re:Claim theme system
================================= */

html[data-theme="dark"] .auth-page {
    color: #f4f5fb;
}

/* Left Section */

html[data-theme="dark"] .auth-intro,
html[data-theme="dark"] .auth-intro h1 {
    color: #f4f5fb !important;
}

html[data-theme="dark"] .auth-intro h1 span {
    color: #a4b5ff !important;
}

html[data-theme="dark"] .auth-brand {
    color: #b8baff !important;
}

html[data-theme="dark"] .auth-intro > p {
    color: #b0b8ca !important;
}

html[data-theme="dark"] .auth-points li {
    color: #e2e8f0 !important;
}

html[data-theme="dark"] .auth-point-icon {
    background: #173d45 !important;
    color: #67e8f9 !important;
}

/* Login Card */

html[data-theme="dark"] .auth-card {
    background: rgba(26, 32, 48, 0.96) !important;
    border-color: #343c50 !important;

    box-shadow:
        0 24px 70px rgba(0, 0, 0, 0.35),
        0 4px 14px rgba(0, 0, 0, 0.2);
}

html[data-theme="dark"] .auth-card h2 {
    color: #f4f5fb !important;
}

html[data-theme="dark"] .auth-card-header p {
    color: #b0b8ca !important;
}

html[data-theme="dark"] .auth-card-kicker {
    background: #292b4a !important;
    color: #b8baff !important;
}

/* Form Labels */

html[data-theme="dark"] .auth-field label {
    color: #e2e8f0 !important;
}

/* Input Fields */

html[data-theme="dark"] .auth-field input {
    background: #252d3e !important;
    color: #f4f5fb !important;
    border-color: #454f65 !important;
}

html[data-theme="dark"] .auth-field input::placeholder {
    color: #9ca8bd !important;
}

html[data-theme="dark"] .auth-field input:focus {
    background: #252d3e !important;
    border-color: #818cf8 !important;

    box-shadow:
        0 0 0 4px rgba(129,140,248,.15) !important;
}

/* Password Eye Button */

html[data-theme="dark"] .password-toggle {
    color: #cbd5e1 !important;
    background: transparent !important;
}

html[data-theme="dark"] .password-toggle:hover {
    color: #a4b5ff !important;
    background: #343c50 !important;
}

/* Sign In Button */

html[data-theme="dark"] .auth-submit {
    color: #ffffff !important;
}

/* Error and Success Messages */

html[data-theme="dark"] .auth-success {
    background: #12382f !important;
    border-color: #246b55 !important;
    color: #a7f3d0 !important;
}

html[data-theme="dark"] .auth-error {
    background: #451f2a !important;
    border-color: #7f3344 !important;
    color: #fecaca !important;
}

/* Links */

html[data-theme="dark"] .auth-links p {
    color: #b0b8ca !important;
}

html[data-theme="dark"] .auth-links a {
    color: #a4b5ff !important;
}

html[data-theme="dark"] .auth-links a:hover {
    color: #67e8f9 !important;
}

html[data-theme="dark"] .auth-links .create-account {
    border-top-color: #343c50 !important;
}

</style>


<!-- MAIN LOGIN PAGE -->

<main class="auth-page">

    <div class="auth-layout">

        <!-- LEFT SECTION -->

        <section class="auth-intro">

            <div class="auth-brand">
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

            </div>

            <h1>
                Lost something?
                <br>
                <span>Let's find it together.</span>
            </h1>

            <p>
                Your campus lost and found space.
                Sign in to report missing items,
                discover found belongings, and
                reconnect people with what matters.
            </p>

            <ul class="auth-points">

                <li>
                    <span class="auth-point-icon">✓</span>
                    Browse reported lost and found items
                </li>

                <li>
                    <span class="auth-point-icon">✓</span>
                    Submit and manage your claims
                </li>

                <li>
                    <span class="auth-point-icon">✓</span>
                    Stay connected with your campus community
                </li>

            </ul>

        </section>


        <!-- LOGIN CARD -->

        <section class="auth-card">

            <div class="auth-card-header">

                <span class="auth-card-kicker">
                    Welcome back
                </span>

                <h2>Sign in to Re:Claim</h2>

                <p>
                    Enter your account details to continue your journey.
                </p>

            </div>


            <!-- SUCCESS MESSAGE -->

            <?php if ($password_reset_success) { ?>

                <div class="auth-message auth-success">

                    Your password has been updated.
                    Sign in with your new password.

                </div>

            <?php } ?>


            <!-- ERROR MESSAGE -->

            <?php if (!empty($error)) { ?>

                <div class="auth-message auth-error">

                    <?php echo htmlspecialchars($error); ?>

                </div>

            <?php } ?>


            <!-- LOGIN FORM -->

            <form method="POST" action="">

                <!-- EMAIL FIELD -->

                <div class="auth-field">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="Enter your email"
                        autocomplete="username"
                        required
                    >

                </div>


                <!-- PASSWORD FIELD -->

                <div class="auth-field">

                    <label for="password">
                        Password
                    </label>

                    <div class="password-input-wrap">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            data-password-toggle="password"
                            aria-label="Show password"
                            aria-pressed="false"
                            title="Show password"
                        >

                            <svg
                                id="eyeIcon"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >

                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/>

                                <circle cx="12" cy="12" r="3"/>

                            </svg>

                        </button>

                    </div>

                </div>


                <!-- SIGN IN BUTTON -->

                <button class="auth-submit" type="submit">

                    Sign in to your account

                </button>

            </form>


            <!-- NAVIGATION LINKS -->

            <div class="auth-links">

                <p>
                    <a href="forgot_password.php">
                        Forgot your password?
                    </a>
                </p>

                <p class="create-account">

                    New to RE:CLAIM?

                    <a href="register.php">
                        Create an account
                    </a>

                </p>

            </div>

        </section>

    </div>

</main>


<?php include "includes/footer.php"; ?>