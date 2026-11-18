
<?php

require_once "config/database.php";

$errors = [];
$success = "";

$name = "";
$email = "";
$student_id = "";
$department = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $student_id = trim($_POST["student_id"] ?? "");
    $department = trim($_POST["department"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    // Validate empty fields
    if (
        empty($name) || empty($email) ||
        empty($student_id) || empty($department) ||
        empty($password) || empty($confirm_password)
    ) {
        $errors[] = "Complete all fields to continue.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    // Validate password
    if (strlen($password) < 6) {
        $errors[] = "Your password must contain at least 6 characters.";
    }

    // Confirm password
    if ($password !== $confirm_password) {
        $errors[] = "The passwords do not match.";
    }

    // Check duplicate email and student ID
    if (empty($errors)) {

        $check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users
             WHERE email = ? OR student_id = ?"
        );

        mysqli_stmt_bind_param(
            $check, "ss", $email, $student_id
        );

        mysqli_stmt_execute($check);

        $result = mysqli_stmt_get_result($check);

        if (mysqli_num_rows($result) > 0) {
            $errors[] = "An account with this email address or student ID already exists.";
        }

        mysqli_stmt_close($check);
    }

    // Insert student into database
    if (empty($errors)) {

        $hashed_password = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $sql = "INSERT INTO users
                (name, email, student_id, department, password)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "sssss",
            $name,
            $email,
            $student_id,
            $department,
            $hashed_password
        );

        if (mysqli_stmt_execute($stmt)) {

            $success = "Your account has been created. You can now sign in.";

            $name = "";
            $email = "";
            $student_id = "";
            $department = "";

        } else {
            $errors[] = "We could not create your account. Please try again.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<style>

/* =================================
   REGISTER PAGE
================================= */

.register-page {
    min-height: calc(100vh - 120px);
    padding: 45px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f6fc;
    transition: background 0.3s ease;
}

.register-layout {
    width: 100%;
    max-width: 1050px;
    display: grid;
    grid-template-columns: 0.9fr 1.1fr;
    background: #ffffff;
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 15px 50px rgba(20, 45, 85, 0.12);
    transition: background 0.3s ease;
}

/* =================================
   LEFT SIDE - DEEP LIGHT MODE
================================= */

.register-intro {
    padding: 55px 40px;

    background: linear-gradient(
        145deg,
        #c5d9f2 0%,
        #b2cbea 55%,
        #a5c1e3 100%
    );

    color: #17365d;

    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;

    border-right: 1px solid #a6bedc;
}

.register-intro::before {
    content: "";
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    background: rgba(36, 82, 140, 0.09);
    top: -90px;
    right: -90px;
}

.register-intro::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    border-radius: 50%;
    background: rgba(36, 82, 140, 0.08);
    bottom: -65px;
    left: -65px;
}

.intro-content {
    position: relative;
    z-index: 1;
}

.brand-mark {
    width: 60px;
    height: 60px;
    border-radius: 17px;

    background: #edf4ff;
    color: #174d85;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 29px;
    margin-bottom: 28px;

    box-shadow: 0 5px 18px rgba(23, 54, 93, 0.13);
}

.register-intro h1 {
    font-size: 34px;
    line-height: 1.3;
    margin: 0 0 17px;

    color: #102f52;
    font-weight: 750;
}

.register-intro p {
    color: #294765;
    line-height: 1.85;
    font-size: 15px;
    margin-bottom: 28px;
}

.intro-features {
    display: flex;
    flex-direction: column;
    gap: 17px;
    margin-top: 12px;
}

.intro-feature {
    display: flex;
    align-items: center;
    gap: 12px;

    color: #203d5d;
    font-size: 14px;
    font-weight: 600;
}

.feature-icon {
    width: 30px;
    height: 30px;
    min-width: 30px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #edf4ff;
    color: #174d85;

    border: 1px solid #c1d5ed;
    border-radius: 50%;

    font-size: 14px;
    box-shadow: 0 3px 10px rgba(23, 54, 93, 0.08);
}

/* =================================
   RIGHT SIDE / FORM
================================= */

.register-form-area {
    padding: 42px 45px;
}

.register-heading {
    text-align: center;
    margin-bottom: 25px;
}

.register-heading h2 {
    color: #17365d;
    font-size: 27px;
    margin: 0 0 8px;
}

.register-heading p {
    color: #777;
    font-size: 14px;
    margin: 0;
}

/* =================================
   FORM GRID
================================= */

.register-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px 18px;
}

.form-group {
    min-width: 0;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-size: 13px;
    font-weight: 600;
    color: #34445a;
}

.input-wrapper {
    position: relative;
}

.input-wrapper input {
    width: 100%;
    box-sizing: border-box;
    height: 46px;
    padding: 0 13px;

    border: 1px solid #d9e0eb;
    border-radius: 9px;

    background: #f9fbfe;
    color: #24344a;

    font-size: 14px;
    outline: none;
    transition: 0.25s ease;
}

.input-wrapper input:focus {
    border-color: #2878c8;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(40, 120, 200, 0.12);
}

.input-wrapper input::placeholder {
    color: #a0a9b7;
}

/* =================================
   PASSWORD EYE BUTTON
================================= */

.password-wrapper input {
    padding-right: 48px;
}

.toggle-password {
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
    font-size: 19px;
    padding: 0;
    z-index: 2;
}

.toggle-password:hover {
    background: rgba(40, 120, 200, 0.10);
    color: #246bb2;
}

.toggle-password:focus-visible {
    outline: 2px solid #2878c8;
    outline-offset: 2px;
}

.toggle-password svg {
    display: block;
    width: 21px;
    height: 21px;
}

/* =================================
   SUBMIT BUTTON
================================= */

.register-submit {
    grid-column: 1 / -1;

    width: 100%;
    border: none;
    border-radius: 9px;

    padding: 14px;
    margin-top: 5px;

    background: linear-gradient(135deg, #2878c8, #174d85);
    color: #ffffff;

    font-size: 15px;
    font-weight: 700;
    cursor: pointer;

    transition: 0.25s ease;
}

.register-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 18px rgba(36, 107, 178, 0.25);
}

/* =================================
   LOGIN LINK
================================= */

.login-link {
    text-align: center;
    margin: 23px 0 0;
    color: #778397;
    font-size: 14px;
}

.login-link a {
    color: #246bb2;
    font-weight: 700;
    text-decoration: none;
}

.login-link a:hover {
    text-decoration: underline;
}

/* =================================
   ERROR AND SUCCESS MESSAGES
================================= */

.error-message {
    background: #fff0f0;
    color: #b00020;
    border: 1px solid #ffcaca;
    border-left: 4px solid #dc3545;

    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;

    font-size: 13px;
}

.error-message p {
    margin: 4px 0;
}

.success-message {
    background: #eaf8ef;
    color: #176b36;
    border: 1px solid #bce8ca;
    border-left: 4px solid #28a745;

    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;

    text-align: center;
    font-size: 14px;
}

.success-message a {
    display: inline-block;
    margin-top: 9px;
    color: #176b36;
    font-weight: 700;
}

/* =================================
   DARK MODE
================================= */

html[data-theme="dark"] .register-page {
    background: #101827;
}

html[data-theme="dark"] .register-layout {
    background: #1b2638;
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
}

/* Dark Mode Intro */

html[data-theme="dark"] .register-intro {
    background: linear-gradient(
        145deg,
        #17365d,
        #246bb2
    );
    color: #ffffff;
}

html[data-theme="dark"] .register-intro h1 {
    color: #ffffff;
}

html[data-theme="dark"] .register-intro p {
    color: rgba(255, 255, 255, 0.85);
}

html[data-theme="dark"] .intro-feature {
    color: #ffffff;
}

html[data-theme="dark"] .brand-mark {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
    box-shadow: none;
}

html[data-theme="dark"] .feature-icon {
    background: rgba(255, 255, 255, 0.16);
    color: #ffffff;
    box-shadow: none;
}

/* Dark Mode Form */

html[data-theme="dark"] .register-heading h2 {
    color: #f1f5fb;
}

html[data-theme="dark"] .register-heading p {
    color: #aab6c8;
}

html[data-theme="dark"] .form-group label {
    color: #dce5f2;
}

html[data-theme="dark"] .input-wrapper input {
    background: #111c2d;
    border-color: #39475b;
    color: #f1f5fb;
}

html[data-theme="dark"] .input-wrapper input::placeholder {
    color: #8794a8;
}

html[data-theme="dark"] .input-wrapper input:focus {
    background: #152238;
    border-color: #559ce5;
    box-shadow: 0 0 0 3px rgba(85, 156, 229, 0.15);
}

html[data-theme="dark"] .toggle-password {
    color: #aab6c8;
}

html[data-theme="dark"] .toggle-password:hover {
    background: rgba(85, 156, 229, 0.15);
    color: #70b4ff;
}

html[data-theme="dark"] .login-link {
    color: #aab6c8;
}

html[data-theme="dark"] .login-link a {
    color: #70b4ff;
}

html[data-theme="dark"] .error-message {
    background: #3a2028;
    color: #ffb6c1;
    border-color: #713744;
}

html[data-theme="dark"] .success-message {
    background: #183629;
    color: #9be2b3;
    border-color: #285a40;
}

html[data-theme="dark"] .success-message a {
    color: #9be2b3;
}

/* =================================
   RESPONSIVE DESIGN
================================= */

@media (max-width: 800px) {

    .register-layout {
        max-width: 540px;
        grid-template-columns: 1fr;
    }

    .register-intro {
        padding: 30px;
    }

    .register-intro h1 {
        font-size: 27px;
    }

    .intro-features {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .register-form-area {
        padding: 35px 30px;
    }
}

@media (max-width: 480px) {

    .register-page {
        padding: 25px 12px;
    }

    .register-intro {
        padding: 25px;
    }

    .register-form-area {
        padding: 28px 20px;
    }

    .register-form {
        grid-template-columns: 1fr;
    }

    .form-group.full-width,
    .register-submit {
        grid-column: auto;
    }

    .register-heading h2 {
        font-size: 23px;
    }

    .intro-features {
        grid-template-columns: 1fr;
    }
}

</style>

<!-- =================================
     REGISTER PAGE CONTENT
================================= -->

<div class="register-page">

    <div class="register-layout">

        <!-- LEFT INTRO SECTION -->

        <div class="register-intro">

            <div class="intro-content">

                <picture class="brand-lockup register-brand-lockup">
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

                <h1>Join the RE:CLAIM community!</h1>

                <p>
                    Create your account and become part of a community
                    that helps lost belongings find their way home.
                    Together, we can make our campus a better place.
                </p>

                <div class="intro-features">

                    <div class="intro-feature">
                        <span class="feature-icon">✓</span>
                        <span>Easy account registration</span>
                    </div>

                    <div class="intro-feature">
                        <span class="feature-icon">✓</span>
                        <span>Report lost and found items</span>
                    </div>

                    <div class="intro-feature">
                        <span class="feature-icon">✓</span>
                        <span>Connect with campus members</span>
                    </div>

                    <div class="intro-feature">
                        <span class="feature-icon">✓</span>
                        <span>Help reunite lost belongings</span>
                    </div>

                </div>

            </div>

        </div>

        <!-- RIGHT REGISTRATION FORM -->

        <div class="register-form-area">

            <div class="register-heading">

                <h2>Create Account</h2>

                <p>Enter your details to get started with RE:CLAIM.</p>

            </div>

            <!-- Error Message -->

            <?php if (!empty($errors)) { ?>

                <div class="error-message">

                    <?php foreach ($errors as $error) { ?>

                        <p>
                            <?php echo htmlspecialchars($error); ?>
                        </p>

                    <?php } ?>

                </div>

            <?php } ?>

            <!-- Success Message -->

            <?php if (!empty($success)) { ?>

                <div class="success-message">

                    <?php echo htmlspecialchars($success); ?>

                    <br>

                    <a href="login.php">Sign in to your account →</a>

                </div>

            <?php } ?>

            <!-- Registration Form -->

            <form method="POST" action="" class="register-form">

                <div class="form-group full-width">

                    <label for="name">Full Name</label>

                    <div class="input-wrapper">

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?php echo htmlspecialchars($name); ?>"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            required
                        >

                    </div>

                </div>

                <div class="form-group full-width">

                    <label for="email">Email Address</label>

                    <div class="input-wrapper">

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            required
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label for="student_id">Student ID</label>

                    <div class="input-wrapper">

                        <input
                            type="text"
                            id="student_id"
                            name="student_id"
                            value="<?php echo htmlspecialchars($student_id); ?>"
                            placeholder="Enter student ID"
                            required
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label for="department">Department</label>

                    <div class="input-wrapper">

                        <input
                            type="text"
                            id="department"
                            name="department"
                            value="<?php echo htmlspecialchars($department); ?>"
                            placeholder="Enter department"
                            required
                        >

                    </div>

                </div>

                <div class="form-group">

                    <label for="password">Password</label>

                    <div class="input-wrapper password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Create password"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-password-toggle="password"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <svg class="eye-icon" viewBox="0 0 24 24"
                                 width="21" height="21"
                                 fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                    </div>

                </div>

                <div class="form-group">

                    <label for="confirm_password">Confirm Password</label>

                    <div class="input-wrapper password-wrapper">

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Confirm password"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            data-password-toggle="confirm_password"
                            aria-label="Show confirm password"
                            aria-pressed="false"
                        >
                            <svg class="eye-icon" viewBox="0 0 24 24"
                                 width="21" height="21"
                                 fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                    </div>

                </div>

                <button type="submit" class="register-submit">
                    Create My Account →
                </button>

            </form>

            <p class="login-link">

                Already have an account?

                <a href="login.php">Sign In</a>

            </p>

        </div>

    </div>

</div>

<?php include "includes/footer.php"; ?>
