
<?php
$page_title = "Help & Support | RE:CLAIM";
$page_body_class = "support-page-body";
$page_stylesheet = "support.css";
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<main class="support-main help-page">

    <!-- HERO SECTION -->
    <section class="support-hero" aria-labelledby="help-title">

        <div class="hero-content">

            <p class="support-eyebrow">
                RE:CLAIM SUPPORT CENTER
            </p>

            <h1 id="help-title">
                We're here to help you
                <span>find what's lost.</span>
            </h1>

            <p>
                Have a question about a lost item, a found report,
                or a claim request? You're in the right place.
                Explore our help guide or get in touch with our
                support team.
            </p>

            <div class="hero-actions">
                <a href="#faq" class="support-btn primary-btn">
                    Explore FAQs <span aria-hidden="true">↓</span>
                </a>

                <a href="#contact" class="support-btn secondary-btn">
                    Contact Support <span aria-hidden="true">↗</span>
                </a>
            </div>

        </div>

        <div class="hero-decoration" aria-hidden="true">
            <div class="hero-icon-circle">
                <span>?</span>
            </div>
            <div class="hero-small-card">
                <span class="hero-check">✓</span>
                <span>We're here for you</span>
            </div>
        </div>

    </section>


    <!-- HOW IT WORKS -->
    <section class="help-section" aria-labelledby="getting-started-title">

        <div class="section-intro">

            <p class="support-eyebrow">SIMPLE AND EASY</p>

            <h2 id="getting-started-title">
                How RE:CLAIM works
            </h2>

            <p>
                Getting your lost belongings back doesn't have to
                be complicated. Just follow these three simple steps.
            </p>

        </div>


        <div class="help-grid">

            <article class="help-card">

                <span class="help-card-number">01</span>

                <div class="help-card-icon" aria-hidden="true">
                    <span>＋</span>
                </div>

                <h3>Report an item</h3>

                <p>
                    Lost something or found an item on campus?
                    Sign in and submit a report with the item name,
                    description, category and location.
                </p>

                <a href="student/report_lost.php">
                    Create a report <span aria-hidden="true">→</span>
                </a>

            </article>


            <article class="help-card">

                <span class="help-card-number">02</span>

                <div class="help-card-icon" aria-hidden="true">
                    <span>⌕</span>
                </div>

                <h3>Search for items</h3>

                <p>
                    Explore the available listings and use the
                    search options to find items by name, category
                    or location.
                </p>

                <a href="student/search.php">
                    Browse items <span aria-hidden="true">→</span>
                </a>

            </article>


            <article class="help-card">

                <span class="help-card-number">03</span>

                <div class="help-card-icon" aria-hidden="true">
                    <span>✓</span>
                </div>

                <h3>Submit a claim</h3>

                <p>
                    Recognised your belongings? Submit a claim
                    with accurate identification details and
                    follow its approval status.
                </p>

                <a href="student/my_reports.php">
                    Track your reports <span aria-hidden="true">→</span>
                </a>

            </article>

        </div>

    </section>


    <!-- FAQ SECTION -->
    <section class="help-section faq-section" id="faq"
             aria-labelledby="faq-title">

        <div class="section-intro">

            <p class="support-eyebrow">GOT QUESTIONS?</p>

            <h2 id="faq-title">
                Frequently asked questions
            </h2>

            <p>
                Here are some helpful answers to common questions
                about using RE:CLAIM.
            </p>

        </div>


        <div class="faq-list">

            <details class="faq-item">

                <summary>
                    How do I report a lost item?
                </summary>

                <p>
                    First, sign in to your RE:CLAIM account.
                    Open the report section and select the
                    appropriate report type. Enter the item name,
                    category, description and location, then
                    submit your report.
                </p>

            </details>


            <details class="faq-item">

                <summary>
                    How can I report an item I found?
                </summary>

                <p>
                    Sign in and choose the Found Item option.
                    Provide an accurate description of the item
                    and where you found it. This helps its owner
                    identify and recover their belongings.
                </p>

            </details>


            <details class="faq-item">

                <summary>
                    Why is my report not visible in the listings?
                </summary>

                <p>
                    Reports may require administrator review
                    before they appear in public listings.
                    Please visit My Reports after signing in
                    to check the current status of your report.
                </p>

            </details>


            <details class="faq-item">

                <summary>
                    How do I claim an item?
                </summary>

                <p>
                    Find the item in the available listings and
                    open its details. Select the claim option
                    and provide specific information that can
                    help the administrator verify your ownership.
                </p>

            </details>


            <details class="faq-item">

                <summary>
                    How will I know if my claim is approved?
                </summary>

                <p>
                    You can check your claim status from your
                    My Reports section. If your claim is approved,
                    you will also receive an email notification
                    at your registered email address, provided
                    email notifications are enabled.
                </p>

            </details>


            <details class="faq-item">

                <summary>
                    Can I edit or remove my submitted report?
                </summary>

                <p>
                    Visit My Reports to review your submitted
                    reports and check which actions are available.
                    If you cannot make the required changes,
                    contact the campus support team.
                </p>

            </details>


            <details class="faq-item">

                <summary>
                    Is my personal information safe?
                </summary>

                <p>
                    Please avoid sharing passwords, OTPs or
                    sensitive personal information in item
                    descriptions and claim messages. Only provide
                    the information necessary to verify ownership.
                </p>

            </details>

        </div>

    </section>


    <!-- CONTACT SUPPORT SECTION -->
    <section class="contact-panel" id="contact"
             aria-labelledby="contact-title">

        <div class="contact-copy">

            <p class="support-eyebrow">WE'RE JUST A MESSAGE AWAY</p>

            <h2 id="contact-title">
                Still need help?
                <span>Let's talk.</span>
            </h2>

            <p>
                Couldn't find the answer you were looking for?
                Don't worry. Reach out to our support team
                and we'll do our best to assist you.
            </p>

            <div class="contact-note">
                <span aria-hidden="true">✦</span>
                Your campus support team is here to help.
            </div>

        </div>


        <div class="contact-list">

            <!-- EMAIL CONTACT -->
            <a class="contact-card"
               href="mailto:support@yourdomain.com">

                <span class="contact-icon" aria-hidden="true">
                    ✉
                </span>

                <span class="contact-details">
                    <small>Email Support</small>
                    <strong>reclaim71@gmail.com</strong>
                    <small>Send us your questions by email</small>
                </span>

                <span class="contact-arrow" aria-hidden="true">
                    ↗
                </span>

            </a>


            <!-- PHONE CONTACT -->
            <a class="contact-card"
               href="tel:+919876543210">

                <span class="contact-icon" aria-hidden="true">
                    ☎
                </span>

                <span class="contact-details">
                    <small>Phone Support</small>
                    <strong>+91 9875399315</strong>
                    <small>Call us for assistance</small>
                </span>

                <span class="contact-arrow" aria-hidden="true">
                    ↗
                </span>

            </a>


            <!-- FEEDBACK CONTACT -->
            <a class="contact-card"
               href="feedback.php">

                <span class="contact-icon" aria-hidden="true">
                    ◇
                </span>

                <span class="contact-details">
                    <small>Share Your Feedback</small>
                    <strong>Help us improve RE:CLAIM</strong>
                    <small>We value your suggestions</small>
                </span>

                <span class="contact-arrow" aria-hidden="true">
                    ↗
                </span>

            </a>

        </div>

    </section>


    <!-- BOTTOM MESSAGE -->
    <section class="support-bottom">

        <div class="bottom-icon" aria-hidden="true">
            ♥
        </div>

        <h2>Every lost item deserves a second chance.</h2>

        <p>
            Together, let's make our campus a better place
            to find what's lost and return what's found.
        </p>

        <a href="index.php">
            Back to Home <span aria-hidden="true">→</span>
        </a>

    </section>


    <p class="support-secondary-link">
        Have an idea to make RE:CLAIM better?
        <a href="feedback.php">
            Share your feedback <span aria-hidden="true">→</span>
        </a>
    </p>

</main>

<?php include "includes/footer.php"; ?>
