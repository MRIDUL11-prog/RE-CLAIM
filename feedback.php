
<?php

require_once __DIR__ . "/includes/session.php";

require_once "config/database.php";



// CSRF Token

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

}



$errors = [];

$rating = "";

$feedback_type = "";

$email = "";

$message = "";



$show_success = !empty($_SESSION["feedback_success"]);

unset($_SESSION["feedback_success"]);



$feedback_types = [

    "Website experience",

    "Finding an item",

    "Reporting an item",

    "Claiming an item",

    "Other"

];



if ($_SERVER["REQUEST_METHOD"] === "POST") {



    $submitted_token = $_POST["csrf_token"] ?? "";

    $rating_input = $_POST["rating"] ?? "";

    $feedback_type_input = $_POST["feedback_type"] ?? "";

    $email_input = $_POST["email"] ?? "";

    $message_input = $_POST["message"] ?? "";



    $rating_value = filter_var(

        is_string($rating_input) ? $rating_input : null,

        FILTER_VALIDATE_INT,

        ["options" => ["min_range" => 1, "max_range" => 5]]

    );



    $feedback_type = is_string($feedback_type_input)

        ? trim($feedback_type_input)

        : "";



    $email = is_string($email_input) ? trim($email_input) : "";

    $message = is_string($message_input) ? trim($message_input) : "";



    // CSRF Validation

    if (

        !is_string($submitted_token) ||

        !hash_equals($_SESSION["csrf_token"], $submitted_token)

    ) {

        $errors[] = "Your session has expired. Please refresh the page and try again.";

    }



    // Rating Validation

    if ($rating_value === false) {

        $errors[] = "Please select a rating between 1 and 5 stars.";

    } else {

        $rating = (string)$rating_value;

    }



    // Category Validation

    if (!in_array($feedback_type, $feedback_types, true)) {

        $errors[] = "Please select a feedback category.";

    }



    // Mandatory Email Validation

    if ($email === "") {

        $errors[] = "Email address is required to submit your feedback.";

    } elseif (

        !filter_var($email, FILTER_VALIDATE_EMAIL) ||

        strlen($email) > 254

    ) {

        $errors[] = "Please enter a valid email address.";

    }



    // Message Validation

    if ($message === "" || strlen($message) > 5000) {

        $errors[] = "Your feedback is required and must be 5,000 characters or fewer.";

    }



    if (empty($errors)) {



        $user_id = isset($_SESSION["user_id"])

            ? (int)$_SESSION["user_id"]

            : null;



        $statement = mysqli_prepare(

            $conn,

            "INSERT INTO feedback (user_id, rating, feedback_type, email, message)

             VALUES (?, ?, ?, ?, ?)"

        );



        if (!$statement) {



            error_log("Could not prepare feedback submission: " . mysqli_error($conn));



            $errors[] = "We couldn't save your feedback right now. Please try again later.";



        } else {



            mysqli_stmt_bind_param(

                $statement,

                "iisss",

                $user_id,

                $rating_value,

                $feedback_type,

                $email,

                $message

            );



            if (mysqli_stmt_execute($statement)) {



                mysqli_stmt_close($statement);



                /*
                 * =====================================
                 * SEND ATTRACTIVE CONFIRMATION EMAIL
                 * =====================================
                 */



                try {



                    require_once __DIR__ . "/vendor/autoload.php";



                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);



                    // Load private SMTP configuration

                    require_once __DIR__ . "/config/mail.php";



                    // SMTP Configuration

                    $mail->isSMTP();

                    $mail->Host = getenv("SMTP_HOST") ?: "smtp.gmail.com";

                    $mail->SMTPAuth = true;

                    $mail->Username = getenv("SMTP_USERNAME");

                    $mail->Password = getenv("SMTP_PASSWORD");

                    $mail->SMTPSecure =

                        PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

                    $mail->Port = (int)(getenv("SMTP_PORT") ?: 587);

                    $mail->CharSet = "UTF-8";



                    // Sender Information

                    $mail->setFrom(

                        getenv("SMTP_FROM_EMAIL"),

                        getenv("SMTP_FROM_NAME") ?: "RE:CLAIM Team"

                    );



                    // Receiver Information

                    $mail->addAddress($email);



                    // HTML Email

                    $mail->isHTML(true);



                    $mail->Subject =

                        "Thank You for Your Feedback! | RE:CLAIM";



                    // Securely escape user-provided content

                    $safeMessage = nl2br(

                        htmlspecialchars(

                            $message,

                            ENT_QUOTES,

                            "UTF-8"

                        )

                    );



                    $safeCategory = htmlspecialchars(

                        $feedback_type,

                        ENT_QUOTES,

                        "UTF-8"

                    );



                    $ratingStars =

                        str_repeat("★", $rating_value) .

                        str_repeat("☆", 5 - $rating_value);



                    // Attractive HTML Email Template

                    $mail->Body = <<<HTML

<!DOCTYPE html>

<html lang="en">



<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Thank You for Your Feedback</title>

</head>



<body style="

    margin:0;

    padding:0;

    background-color:#f1f5f9;

    font-family:Arial,Helvetica,sans-serif;

    color:#1e293b;

">



<table role="presentation"

       width="100%"

       cellpadding="0"

       cellspacing="0"

       border="0"

       style="background-color:#f1f5f9;padding:35px 12px;">



<tr>

<td align="center">



<table role="presentation"

       width="100%"

       cellpadding="0"

       cellspacing="0"

       border="0"

       style="

           max-width:620px;

           background-color:#ffffff;

           border-radius:16px;

           overflow:hidden;

       ">



<!-- BRAND HEADER -->



<tr>

<td align="center"

    style="

        background-color:#172554;

        padding:35px 20px;

    ">



    <div style="

        font-size:31px;

        font-weight:800;

        letter-spacing:2px;

        color:#ffffff;

    ">

        RE:<span style="color:#60a5fa;">CLAIM</span>

    </div>



    <p style="

        margin:10px 0 0;

        color:#cbd5e1;

        font-size:12px;

        letter-spacing:2px;

    ">

        CAMPUS LOST &amp; FOUND PLATFORM

    </p>



    <div style="

        width:45px;

        height:3px;

        background-color:#60a5fa;

        margin:20px auto 0;

        border-radius:5px;

    "></div>



</td>

</tr>





<!-- MAIN CONTENT -->



<tr>

<td style="padding:38px 30px 25px;">



    <div style="text-align:center;margin-bottom:28px;">



        <div style="

            font-size:48px;

            line-height:1.4;

        ">

            🎉

        </div>



        <h1 style="

            margin:12px 0;

            color:#172554;

            font-size:27px;

            line-height:1.35;

        ">

            Thank You for Your Feedback!

        </h1>



        <p style="

            margin:0;

            color:#64748b;

            font-size:15px;

            line-height:1.9;

        ">

            Your voice matters to us. Thank you for taking

            a moment to share your thoughts with RE:CLAIM.

            Your feedback helps us create a better experience

            for our entire campus community.

        </p>



    </div>





    <!-- SUCCESS STATUS -->



    <table role="presentation"

           width="100%"

           cellpadding="0"

           cellspacing="0"

           border="0"

           style="

               background-color:#eff6ff;

               border:1px solid #bfdbfe;

               border-radius:10px;

           ">



        <tr>

            <td align="center" style="padding:20px;">



                <p style="

                    margin:0;

                    color:#1d4ed8;

                    font-size:14px;

                    font-weight:bold;

                    letter-spacing:0.5px;

                ">

                    &#10003; FEEDBACK SUCCESSFULLY RECEIVED

                </p>



                <p style="

                    margin:8px 0 0;

                    color:#475569;

                    font-size:13px;

                    line-height:1.6;

                ">

                    Your feedback has been recorded successfully.

                </p>



            </td>

        </tr>



    </table>





    <!-- FEEDBACK SUMMARY -->



    <h2 style="

        margin:32px 0 15px;

        color:#172554;

        font-size:20px;

    ">

        Your Feedback Summary

    </h2>



    <table role="presentation"

           width="100%"

           cellpadding="0"

           cellspacing="0"

           border="0"

           style="

               border:1px solid #e2e8f0;

               border-radius:10px;

           ">



        <!-- RATING -->



        <tr>

            <td style="

                padding:18px;

                border-bottom:1px solid #e2e8f0;

            ">



                <p style="

                    margin:0 0 8px;

                    color:#64748b;

                    font-size:12px;

                    font-weight:bold;

                    letter-spacing:1px;

                ">

                    YOUR RATING

                </p>



                <p style="

                    margin:0;

                    color:#f59e0b;

                    font-size:27px;

                    letter-spacing:3px;

                ">

                    {$ratingStars}

                </p>



                <p style="

                    margin:6px 0 0;

                    color:#475569;

                    font-size:13px;

                ">

                    {$rating_value} out of 5 stars

                </p>



            </td>

        </tr>





        <!-- CATEGORY -->



        <tr>

            <td style="

                padding:18px;

                border-bottom:1px solid #e2e8f0;

            ">



                <p style="

                    margin:0 0 8px;

                    color:#64748b;

                    font-size:12px;

                    font-weight:bold;

                    letter-spacing:1px;

                ">

                    FEEDBACK CATEGORY

                </p>



                <p style="

                    margin:0;

                    color:#172554;

                    font-size:15px;

                    font-weight:bold;

                ">

                    {$safeCategory}

                </p>



            </td>

        </tr>





        <!-- MESSAGE -->



        <tr>

            <td style="padding:18px;">



                <p style="

                    margin:0 0 12px;

                    color:#64748b;

                    font-size:12px;

                    font-weight:bold;

                    letter-spacing:1px;

                ">

                    YOUR MESSAGE

                </p>



                <div style="

                    background-color:#f8fafc;

                    border-left:4px solid #3b82f6;

                    border-radius:6px;

                    padding:16px;

                    color:#334155;

                    font-size:14px;

                    line-height:1.8;

                    overflow-wrap:anywhere;

                ">

                    {$safeMessage}

                </div>



            </td>

        </tr>



    </table>





    <!-- APPRECIATION MESSAGE -->



    <div style="

        margin-top:30px;

        padding:23px 18px;

        background-color:#f8fafc;

        border-radius:10px;

        text-align:center;

    ">



        <div style="font-size:25px;">

            💙

        </div>



        <h2 style="

            margin:10px 0;

            color:#172554;

            font-size:19px;

        ">

            Together, We Make a Difference!

        </h2>



        <p style="

            margin:0;

            color:#64748b;

            font-size:14px;

            line-height:1.8;

        ">

            Every idea, suggestion, and experience you share

            brings us closer to building a more helpful,

            reliable, and user-friendly campus community.

            We truly appreciate your support.

        </p>



    </div>





    <!-- CLOSING -->



    <p style="

        margin:30px 0 6px;

        color:#475569;

        font-size:14px;

        line-height:1.7;

    ">

        With gratitude,

    </p>



    <p style="

        margin:0;

        color:#172554;

        font-size:17px;

        font-weight:bold;

    ">

        The RE:CLAIM Team

    </p>



</td>

</tr>





<!-- FOOTER -->



<tr>

<td align="center"

    style="

        background-color:#f8fafc;

        padding:25px 20px;

        border-top:1px solid #e2e8f0;

    ">



    <p style="

        margin:0 0 8px;

        color:#475569;

        font-size:13px;

        font-weight:bold;

    ">

        RE:CLAIM — Campus Lost &amp; Found

    </p>



    <p style="

        margin:0;

        color:#94a3b8;

        font-size:12px;

        line-height:1.7;

    ">

        Helping lost belongings find their way home.

    </p>



    <p style="

        margin:14px 0 0;

        color:#94a3b8;

        font-size:11px;

        line-height:1.7;

    ">

        This is an automated confirmation email.

        Please do not reply directly to this message.

    </p>



    <p style="

        margin:12px 0 0;

        color:#cbd5e1;

        font-size:11px;

    ">

        &copy; RE:CLAIM. All rights reserved.

    </p>



</td>

</tr>



</table>



</td>

</tr>



</table>



</body>

</html>

HTML;



                    // Plain Text Fallback

                    $mail->AltBody =

                        "Thank you for your feedback!\n\n" .

                        "Your feedback has been successfully received by RE:CLAIM.\n\n" .

                        "Rating: {$rating_value}/5\n" .

                        "Category: {$feedback_type}\n\n" .

                        "Your Message:\n{$message}\n\n" .

                        "Every suggestion helps us improve our campus community.\n\n" .

                        "With gratitude,\nThe RE:CLAIM Team";



                    // Send Email

                    $mail->send();



                } catch (Throwable $e) {



                    error_log(

                        "Feedback email error: " . $e->getMessage()

                    );



                }



                // Success Redirect

                $_SESSION["feedback_success"] = true;



                header("Location: feedback.php", true, 303);

                exit();



            } else {



                error_log(

                    "Could not save feedback: " . mysqli_stmt_error($statement)

                );



                mysqli_stmt_close($statement);



                $errors[] = "We couldn't save your feedback right now. Please try again later.";

            }

        }

    }

}



$page_title = "Share Your Feedback | RE:CLAIM";

$page_body_class = "support-page-body";

$page_stylesheet = "support.css";

?>



<?php include "includes/header.php"; ?>

<?php include "includes/navbar.php"; ?>



<main class="support-main feedback-page">



    <!-- HERO SECTION: DESIGN UNCHANGED -->



    <section class="support-hero feedback-hero" aria-labelledby="feedback-title">



        <p class="support-eyebrow">YOUR VOICE MAKES A DIFFERENCE</p>



        <h1 id="feedback-title">

            Every thought helps us

            <span>grow better.</span>

        </h1>



        <p>

            Your experience matters to us. Share your thoughts, tell us what

            you loved, and help us make RE:CLAIM a better campus community.

        </p>



        <div class="feedback-hero-note">

            <span aria-hidden="true">✦</span>

            Every suggestion brings us one step forward.

        </div>



    </section>





    <!-- FEEDBACK FORM CARD -->



    <section class="feedback-card" aria-labelledby="rating-title">



        <div class="feedback-card-heading">



            <div class="feedback-heading-icon" aria-hidden="true">✦</div>



            <div>

                <p class="feedback-card-kicker">WE VALUE YOUR OPINION</p>

                <h2 id="rating-title">Share your experience</h2>

                <p>A few words from you can make a big difference.</p>

            </div>



        </div>





        <!-- ERROR MESSAGES -->



        <?php if (!empty($errors)) { ?>



            <div class="support-alert" role="alert" tabindex="-1">



                <strong>We couldn't submit your feedback yet.</strong>



                <ul>

                    <?php foreach ($errors as $error) { ?>

                        <li>

                            <?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>

                        </li>

                    <?php } ?>

                </ul>



                <?php

                if (

                    in_array(

                        "We couldn't save your feedback right now. Please try again later.",

                        $errors,

                        true

                    )

                ) {

                ?>



                    <p class="support-alert-hint">

                        If this problem continues, please ask the site administrator

                        to check the feedback database setup.

                    </p>



                <?php } ?>



            </div>



        <?php } ?>





        <!-- FEEDBACK FORM -->



        <form

            class="feedback-form"

            id="feedback-form"

            method="POST"

            action="feedback.php"

        >



            <input

                type="hidden"

                name="csrf_token"

                value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"

            >





            <!-- STAR RATING -->



            <fieldset class="rating-field">



                <legend>

                    How would you rate your experience?

                    <span class="required-star">*</span>

                </legend>



                <p class="rating-description">

                    Select the number of stars that best describes your experience.

                </p>



                <div

                    class="rating-stars"

                    role="radiogroup"

                    aria-label="Rate your experience from one to five stars"

                >



                    <?php for ($star = 5; $star >= 1; $star--) { ?>



                        <input

                            type="radio"

                            name="rating"

                            id="rating-<?php echo $star; ?>"

                            value="<?php echo $star; ?>"

                            aria-label="<?php echo $star; ?> <?php echo $star === 1 ? "star" : "stars"; ?>"

                            <?php echo (string)$star === $rating ? "checked" : ""; ?>

                            <?php echo $star === 1 ? "required" : ""; ?>

                        >



                        <label

                            for="rating-<?php echo $star; ?>"

                            title="<?php echo $star; ?> <?php echo $star === 1 ? "star" : "stars"; ?>"

                        >

                            <span aria-hidden="true">★</span>

                        </label>



                    <?php } ?>



                </div>



                <div class="rating-feedback" id="rating-feedback" aria-live="polite">



                    <span class="rating-feedback-icon" aria-hidden="true">★</span>



                    <span id="rating-feedback-text">

                        <?php

                        $rating_names = [

                            1 => "Poor",

                            2 => "Fair",

                            3 => "Good",

                            4 => "Very Good",

                            5 => "Excellent"

                        ];



                        echo $rating !== ""

                            ? htmlspecialchars($rating_names[(int)$rating], ENT_QUOTES, "UTF-8")

                            : "Choose a star rating";

                        ?>

                    </span>



                </div>



                <p class="rating-hint">

                    Your honest rating helps us improve.

                </p>



            </fieldset>





            <!-- FEEDBACK CATEGORY -->



            <div class="feedback-form-group">



                <label class="support-label" for="feedback-type">

                    What would you like to talk about?

                    <span class="required-star">*</span>

                </label>



                <select

                    class="support-control"

                    id="feedback-type"

                    name="feedback_type"

                    required

                >



                    <option

                        value=""

                        disabled

                        <?php echo $feedback_type === "" ? "selected" : ""; ?>

                    >

                        Select a category

                    </option>



                    <?php foreach ($feedback_types as $type) { ?>



                        <option

                            value="<?php echo htmlspecialchars($type, ENT_QUOTES, "UTF-8"); ?>"

                            <?php echo $feedback_type === $type ? "selected" : ""; ?>

                        >

                            <?php echo htmlspecialchars($type, ENT_QUOTES, "UTF-8"); ?>

                        </option>



                    <?php } ?>



                </select>



                <div

                    class="category-guidance"

                    id="category-guidance"

                    aria-live="polite"

                >



                    <span class="category-guidance-icon" aria-hidden="true">💡</span>



                    <span id="category-guidance-text">

                        Choose a category to see what you can share with us.

                    </span>



                </div>



            </div>





            <!-- MESSAGE -->



            <div class="feedback-form-group">



                <label class="support-label" for="feedback-message">

                    Tell us more about your experience

                    <span class="required-star">*</span>

                </label>



                <textarea

                    class="support-control feedback-message"

                    id="feedback-message"

                    name="message"

                    rows="6"

                    minlength="1"

                    maxlength="5000"

                    placeholder="Share your thoughts, suggestions, or anything you think we can improve..."

                    required

                ><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></textarea>



                <div class="message-meta">



                    <p class="field-hint">

                        Please avoid sharing passwords or sensitive personal information.

                    </p>



                    <p class="character-counter" id="character-counter" aria-live="polite">

                        <span id="character-count">0</span> / 5000 characters

                    </p>



                </div>



            </div>





            <!-- EMAIL -->



            <div class="feedback-form-group">



                <label class="support-label" for="feedback-email">

                    Email address

                    <span class="required-star">*</span>

                    <span class="optional-label">REQUIRED</span>

                </label>



                <input

                    class="support-control"

                    type="email"

                    id="feedback-email"

                    name="email"

                    maxlength="254"

                    autocomplete="email"

                    placeholder="Enter your email address"

                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, "UTF-8"); ?>"

                    required

                >



                <p class="field-hint">

                    Your email is required. We will send you a confirmation

                    message after successfully receiving your feedback.

                </p>



            </div>





            <!-- SUBMIT BUTTON -->



            <button

                class="support-button feedback-submit-button"

                id="feedback-submit"

                type="submit"

            >



                <span class="submit-button-text">Submit your feedback</span>



                <span class="submit-button-arrow" aria-hidden="true">→</span>



                <span class="submit-spinner" aria-hidden="true"></span>



            </button>



            <p class="feedback-privacy-note">

                <span aria-hidden="true">🔒</span>

                Thank you for helping us build a better RE:CLAIM.

            </p>



        </form>



    </section>





    <!-- BOTTOM LINK -->



    <p class="support-secondary-link">

        Need help with something?



        <a href="help.php">

            Explore Help &amp; Support

            <span aria-hidden="true">→</span>

        </a>

    </p>



</main>





<!-- SUCCESS DIALOG -->



<?php if ($show_success) { ?>



    <dialog

        class="feedback-dialog"

        id="feedback-thanks"

        aria-labelledby="feedback-thanks-title"

    >



        <div class="dialog-check" aria-hidden="true">✓</div>



        <p class="dialog-kicker">MESSAGE RECEIVED</p>



        <h2 id="feedback-thanks-title">Thank you for sharing!</h2>



        <p>

            Your feedback has been successfully received.

            Your thoughts will help us make RE:CLAIM even better.

        </p>



        <button class="support-button" type="button" data-close-feedback>

            Done

        </button>



    </dialog>



<?php } ?>





<!-- FEEDBACK FORM INTERACTIONS -->



<script>

document.addEventListener("DOMContentLoaded", function () {



    const feedbackForm = document.getElementById("feedback-form");



    if (!feedbackForm) {

        return;

    }



    // 1. Live star rating text



    const ratingInputs = feedbackForm.querySelectorAll('input[name="rating"]');

    const ratingText = document.getElementById("rating-feedback-text");



    const ratingNames = {

        "1": "Poor",

        "2": "Fair",

        "3": "Good",

        "4": "Very Good",

        "5": "Excellent"

    };



    function updateRatingText() {



        const selectedRating = feedbackForm.querySelector(

            'input[name="rating"]:checked'

        );



        if (selectedRating) {

            ratingText.textContent = ratingNames[selectedRating.value];

        } else {

            ratingText.textContent = "Choose a star rating";

        }

    }



    ratingInputs.forEach(function (input) {

        input.addEventListener("change", updateRatingText);

    });



    updateRatingText();





    // 2. Category-specific guidance



    const categorySelect = document.getElementById("feedback-type");

    const categoryGuidance = document.getElementById("category-guidance-text");



    const categoryMessages = {



        "Website experience":

            "Tell us about the website design, navigation, speed, or overall experience.",



        "Finding an item":

            "Let us know how easy or difficult it was to search for a lost item.",



        "Reporting an item":

            "Share your experience while reporting an item you found or lost.",



        "Claiming an item":

            "Tell us whether the item claiming process was clear and convenient.",



        "Other":

            "Have another idea or suggestion? We would love to hear about it."

    };



    function updateCategoryGuidance() {



        const selectedCategory = categorySelect.value;



        categoryGuidance.textContent = categoryMessages[selectedCategory]

            || "Choose a category to see what you can share with us.";

    }



    categorySelect.addEventListener("change", updateCategoryGuidance);

    updateCategoryGuidance();





    // 3. Live message character counter



    const messageField = document.getElementById("feedback-message");

    const characterCount = document.getElementById("character-count");

    const characterCounter = document.getElementById("character-counter");



    function updateCharacterCount() {



        const currentLength = messageField.value.length;



        characterCount.textContent = currentLength;



        characterCounter.classList.toggle(

            "is-near-limit",

            currentLength >= 4500

        );



        characterCounter.classList.toggle(

            "is-at-limit",

            currentLength >= 5000

        );

    }



    messageField.addEventListener("input", updateCharacterCount);

    updateCharacterCount();





    // 4. Prevent repeated submissions and show loading state



    const submitButton = document.getElementById("feedback-submit");



    feedbackForm.addEventListener("submit", function (event) {



        if (!feedbackForm.checkValidity()) {

            return;

        }



        if (submitButton.disabled) {

            event.preventDefault();

            return;

        }



        submitButton.disabled = true;

        submitButton.classList.add("is-submitting");



        const buttonText = submitButton.querySelector(".submit-button-text");



        buttonText.textContent = "Submitting...";



        submitButton.setAttribute(

            "aria-label",

            "Submitting your feedback"

        );

    });





    // 5. Automatically focus error message



    const errorAlert = document.querySelector(

        ".feedback-page .support-alert"

    );



    if (errorAlert) {

        errorAlert.focus();

    }



});

</script>





<!-- EXTERNAL FEEDBACK JAVASCRIPT -->



<script src="<?php echo $base_url; ?>/js/feedback.js?v=<?php echo filemtime(__DIR__ . '/js/feedback.js'); ?>" defer></script>



<?php include "includes/footer.php"; ?>
