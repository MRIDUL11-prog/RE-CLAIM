
<?php

require_once "../includes/auth.php";
checkStudent();

require_once "../config/database.php";

?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>

<style>

/* ==============================
   MAIN DASHBOARD
============================== */

.student-dashboard {
    max-width: 1250px;
    width: 92%;
    margin: 25px auto 50px;
    padding: 0;
}


/* ==============================
   WELCOME HERO SECTION
============================== */

.dashboard-hero {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
    min-height: 260px;
    padding: 38px 50px;
    margin-bottom: 38px;
    border-radius: 24px;
    background: linear-gradient(120deg, #102a43, #176b87, #36a6a0);
    color: #ffffff;
    box-shadow: 0 12px 35px rgba(16, 42, 67, 0.18);
}

.dashboard-hero::before,
.dashboard-hero::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.07);
}

.dashboard-hero::before {
    width: 280px;
    height: 280px;
    right: 80px;
    top: -150px;
}

.dashboard-hero::after {
    width: 200px;
    height: 200px;
    right: -60px;
    bottom: -110px;
}

.dashboard-hero-content {
    position: relative;
    z-index: 2;
    max-width: 650px;
}


.dashboard-eyebrow {
    margin: 0 0 12px;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: 2.5px;
    color: #7DFFE5 !important;
    -webkit-text-fill-color: #7DFFE5 !important;
    text-shadow: 0 0 12px rgba(125, 255, 229, 0.35);
    opacity: 1 !important;
}


.dashboard-hero h1 {
    margin: 0 0 14px;
    color: #ffffff !important;
    font-size: clamp(27px, 4vw, 40px);
    line-height: 1.2;
    font-weight: 800;
}

/* FIX 1: Welcome message visibility in Light Mode */

.dashboard-hero .welcome-text {
    max-width: 550px;
    margin: 0 0 24px;
    color: #f3ffff !important;
    font-size: 16px;
    line-height: 1.7;
    opacity: 1 !important;
}

.dashboard-primary-action {
    display: inline-block;
    padding: 13px 25px;
    border-radius: 10px;
    background: #ffffff;
    color: #14566a;
    text-decoration: none;
    font-size: 15px;
    font-weight: 700;
    transition: 0.3s ease;
}

.dashboard-primary-action:hover {
    background: #d9fff7;
    color: #102a43;
    transform: translateY(-3px);
}


/* ==============================
   HERO ILLUSTRATION
============================== */

.dashboard-hero-art {
    position: relative;
    z-index: 2;
    flex: 0 0 220px;
    width: 220px;
    height: 190px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-circle {
    width: 175px;
    height: 175px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255,255,255,0.35);
    border-radius: 50%;
    background: rgba(255,255,255,0.13);
    font-size: 82px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.12);
    animation: gentleFloat 4s ease-in-out infinite;
}

.hero-small-badge {
    position: absolute;
    right: 0;
    bottom: 15px;
    padding: 11px 15px;
    border-radius: 12px;
    background: #ffffff;
    color: #176b87;
    font-size: 13px;
    font-weight: 800;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

@keyframes gentleFloat {
    0%, 100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-10px);
    }
}


/* ==============================
   EXPLORE YOUR DASHBOARD
============================== */

.dashboard-section-heading {
    margin: 0 0 25px;
}

.dashboard-section-heading h2 {
    margin: 0 0 8px;
    color: #173b50;
    font-size: 30px;
    font-weight: 800;
}

.dashboard-section-heading p {
    margin: 0;
    color: #718096;
    font-size: 16px;
}


/* FIX 2: Explore heading visibility in Dark Mode */

body.dark-mode .dashboard-section-heading h2,
html.dark .dashboard-section-heading h2,
[data-theme="dark"] .dashboard-section-heading h2 {
    color: #f1f7ff !important;
}

body.dark-mode .dashboard-section-heading p,
html.dark .dashboard-section-heading p,
[data-theme="dark"] .dashboard-section-heading p {
    color: #c5d3e0 !important;
}


/* Dashboard Cards */

.dashboard-menu {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 28px;
}

.dashboard-card {
    padding: 32px;
    min-height: 240px;
    border: 1px solid #e5edf2;
    border-radius: 20px;
    background: #ffffff;
    box-shadow: 0 8px 25px rgba(23, 59, 80, 0.07);
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 15px 35px rgba(23, 59, 80, 0.13);
}


/* Card Icons */

.dashboard-card-icon {
    width: 55px;
    height: 55px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
    border-radius: 15px;
    background: #e8f7f5;
    font-size: 28px;
}


/* Card Text */

.dashboard-card-label {
    display: block;
    margin-bottom: 12px;
    color: #168b91;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1.3px;
}

.dashboard-card h3 {
    margin: 0 0 12px;
    color: #173b50;
    font-size: 23px;
    font-weight: 800;
}

.dashboard-card p {
    margin: 0 0 22px;
    color: #718096;
    font-size: 15px;
    line-height: 1.7;
}

.dashboard-card a {
    display: inline-block;
    color: #087e8b;
    font-size: 15px;
    font-weight: 750;
    text-decoration: none;
}

.dashboard-card a:hover {
    color: #102a43;
    text-decoration: underline;
}


/* ==============================
   BOTTOM MESSAGE
============================== */

.dashboard-bottom-note {
    margin-top: 28px;
    padding: 20px 25px;
    border-radius: 15px;
    background: #eaf8f6;
    color: #176b70;
    text-align: center;
    font-size: 15px;
    font-weight: 650;
}


/* ==============================
   RESPONSIVE DESIGN
============================== */

@media (max-width: 700px) {

    .student-dashboard {
        width: 94%;
        margin-top: 18px;
    }

    .dashboard-hero {
        min-height: auto;
        padding: 30px 24px;
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .dashboard-hero-art {
        align-self: center;
        height: 150px;
    }

    .hero-circle {
        width: 130px;
        height: 130px;
        font-size: 62px;
    }

    .dashboard-menu {
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .dashboard-card {
        padding: 25px;
    }

    .dashboard-section-heading h2 {
        font-size: 25px;
    }
}


/* FIX: Welcome Section Text Visibility */

main.student-dashboard .dashboard-hero .dashboard-hero-content h1 {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    opacity: 1 !important;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

main.student-dashboard .dashboard-hero .dashboard-hero-content .welcome-text {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
    opacity: 1 !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    font-weight: 500;
}


</style>


<main class="student-dashboard">


    <!-- WELCOME HERO SECTION -->

    <section class="dashboard-hero">

        <div class="dashboard-hero-content">

            <p class="dashboard-eyebrow">
                WELCOME TO RE:CLAIM
            </p>

            <h1>
                Welcome back,
                <?php echo htmlspecialchars($_SESSION["name"]); ?>!
            </h1>

            <p class="welcome-text">
                Lost something on campus? Or found an item that belongs
                to someone else? Re:Claim helps you report, search and
                reconnect lost belongings with their owners.
            </p>

            <a href="report_lost.php" class="dashboard-primary-action">
                + Create a Report
            </a>

        </div>


        <div class="dashboard-hero-art">

            <div class="hero-circle">
                🔎
            </div>

            <div class="hero-small-badge">
                Find • Report • Reconnect
            </div>

        </div>

    </section>



    <!-- EXPLORE YOUR DASHBOARD -->

    <div class="dashboard-section-heading">

        <h2>Explore Your Dashboard</h2>

        <p>
            Choose an option below to get started with Re:Claim.
        </p>

    </div>



    <!-- DASHBOARD CARDS -->

    <div class="dashboard-menu">


        <!-- LOST ITEM CARD -->

        <div class="dashboard-card">

            <div class="dashboard-card-icon">
                📦
            </div>

            <span class="dashboard-card-label">
                01 / LOST PROPERTY
            </span>

            <h3>
                Report a Lost Item
            </h3>

            <p>
                Share the details of an item you have misplaced
                on campus and let others help you find it.
            </p>

            <a href="report_lost.php">
                Submit a lost-item report →
            </a>

        </div>



        <!-- FOUND ITEM CARD -->

        <div class="dashboard-card">

            <div class="dashboard-card-icon">
                🔍
            </div>

            <span class="dashboard-card-label">
                02 / FOUND PROPERTY
            </span>

            <h3>
                Report a Found Item
            </h3>

            <p>
                Found something on campus? Report it here
                and help return it to its rightful owner.
            </p>

            <a href="report_found.php">
                Submit a found-item report →
            </a>

        </div>



        <!-- BROWSE ITEMS CARD -->

        <div class="dashboard-card">

            <div class="dashboard-card-icon">
                🗂️
            </div>

            <span class="dashboard-card-label">
                03 / ITEM DIRECTORY
            </span>

            <h3>
                Browse Item Reports
            </h3>

            <p>
                Explore current lost and found listings
                and search for a possible match.
            </p>

            <a href="search.php">
                Browse listings →
            </a>

        </div>



        <!-- MY REPORTS CARD -->

        <div class="dashboard-card">

            <div class="dashboard-card-icon">
                📋
            </div>

            <span class="dashboard-card-label">
                04 / YOUR ACTIVITY
            </span>

            <h3>
                Manage Your Reports
            </h3>

            <p>
                View the reports you have submitted
                and keep track of your activity.
            </p>

            <a href="my_reports.php">
                View your reports →
            </a>

        </div>


    </div>



    <!-- BOTTOM MESSAGE -->

    <div class="dashboard-bottom-note">

        💚 Together, We Reconnect What Matters.

    </div>


</main>


<?php include "../includes/footer.php"; ?>
