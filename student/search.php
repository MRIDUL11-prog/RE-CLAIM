
<?php

require_once "../includes/auth.php";
checkStudent();

require_once "../config/database.php";

// Get search filters
$search = trim($_GET["search"] ?? "");
$category_id = $_GET["category_id"] ?? "";
$item_type = $_GET["item_type"] ?? "";
$location = trim($_GET["location"] ?? "");

// Fetch categories
$category_sql = "SELECT * FROM categories ORDER BY category_name";
$category_result = mysqli_query($conn, $category_sql);

// Base SQL query
$sql = "SELECT items.*, categories.category_name
        FROM items
        INNER JOIN categories
        ON items.category_id = categories.category_id
        WHERE items.status = 'approved'";

$conditions = [];
$params = [];
$types = "";

// Search by item name
if ($search !== "") {
    $conditions[] = "items.item_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Filter by category
if ($category_id !== "" && ctype_digit($category_id)) {
    $conditions[] = "items.category_id = ?";
    $params[] = (int)$category_id;
    $types .= "i";
}

// Filter by item type
if ($item_type === "lost" || $item_type === "found") {
    $conditions[] = "items.item_type = ?";
    $params[] = $item_type;
    $types .= "s";
}

// Filter by location
if ($location !== "") {
    $conditions[] = "items.location LIKE ?";
    $params[] = "%" . $location . "%";
    $types .= "s";
}

// Add conditions
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY items.created_at DESC";

// Prepare statement
$stmt = mysqli_prepare($conn, $sql);

// Bind parameters
if (!empty($params)) {
    $bind_values = [$types];

    foreach ($params as $key => $value) {
        $bind_values[] = &$params[$key];
    }

    call_user_func_array(
        "mysqli_stmt_bind_param",
        array_merge([$stmt], $bind_values)
    );
}

// Execute query
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/navbar.php"; ?>


<style>

/* =====================================
   SEARCH PAGE THEME
===================================== */

.search-page {
    --search-panel: #ffffff;
    --search-text: #17324a;
    --search-muted: #526b80;
    --search-border: #dce8f0;
    --search-soft: #eff8fb;
    --search-shadow: rgba(35, 75, 110, .10);

    max-width: 1180px;
    margin: 35px auto 55px;
    padding: 20px;
    color: var(--search-text);
}


/* =====================================
   HERO SECTION
===================================== */

.search-hero {
    position: relative;
    overflow: hidden;
    isolation: isolate;

    padding: 34px 36px;
    margin-bottom: 26px;

    border-radius: 25px;

    background: linear-gradient(
        120deg,
        #8cebd8,
        #a8d9ff,
        #c9b7ff
    );

    border: 1px solid rgba(54, 117, 165, .42);

    box-shadow: 0 16px 38px rgba(48, 117, 158, .20);

    animation: searchHeroIn .7s ease both;

    transition: transform .3s ease, box-shadow .3s ease;
}

.search-hero:hover {
    transform: translateY(-3px);
    box-shadow: 0 21px 42px rgba(48, 117, 158, .19);
}

.search-hero::before,
.search-hero::after {
    content: "";
    position: absolute;
    z-index: -1;
    border-radius: 50%;
    pointer-events: none;
}

.search-hero::before {
    width: 260px;
    height: 260px;
    top: -145px;
    right: -45px;
    background: rgba(255,255,255,.55);
    box-shadow: 0 0 55px rgba(255,255,255,.45);
}

.search-hero::after {
    width: 180px;
    height: 180px;
    bottom: -125px;
    left: 38%;
    background: rgba(51,190,180,.13);
}

.search-hero-content {
    position: relative;
    z-index: 1;
}

.search-hero-label {
    display: inline-block;
    padding: 7px 13px;
    margin-bottom: 13px;

    border-radius: 30px;

    background: rgba(255,255,255,.86);
    border: 1px solid rgba(8,123,131,.22);

    color: #075b63;

    font-size: 11px;
    font-weight: 900;
    letter-spacing: 2px;
}

.search-hero h2 {
    color: #173b56 !important;
    font-size: clamp(28px, 4vw, 39px);
    font-weight: 900;
    letter-spacing: -.8px;
    line-height: 1.2;
    margin: 0 0 11px;
}

.search-hero p {
    max-width: 700px;
    color: #3d5d72 !important;
    font-size: 15px;
    font-weight: 600;
    line-height: 1.75;
    margin: 0;
}

@keyframes searchHeroIn {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}


/* =====================================
   SEARCH FORM
===================================== */

.search-form {
    position: relative;
    z-index: 5;

    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));

    gap: 13px;
    padding: 25px;

    /* Dropdown open হলে শুধু নিচের অংশে extra space হবে */
    margin-bottom: calc(32px + var(--dropdown-space, 0px));

    border-radius: 20px;

    background: var(--search-panel);
    border: 1px solid var(--search-border);

    box-shadow: 0 12px 30px var(--search-shadow);

    transition: margin-bottom .2s ease, box-shadow .3s ease;
}

.search-form:hover {
    box-shadow: 0 16px 36px var(--search-shadow);
}

.search-form::before {
    content: "FIND WHAT YOU'RE LOOKING FOR";
    grid-column: 1 / -1;

    color: var(--search-muted);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1.8px;
    margin-bottom: 2px;
}

.search-form input,
.search-form select {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;

    padding: 13px 14px;

    border: 1px solid var(--search-border);
    border-radius: 11px;

    font-size: 14px;
    font-family: inherit;

    background: var(--search-soft);
    color: var(--search-text);

    outline: none;

    transition: border-color .2s ease, box-shadow .2s ease;
}

.search-form input::placeholder {
    color: #8295a5;
}

.search-form input:focus {
    border-color: #12a99f;
    box-shadow: 0 0 0 4px rgba(18,169,159,.13);
}


/* =====================================
   CUSTOM DROPDOWN
===================================== */

.custom-select-wrap {
    position: relative;
    min-width: 0;
    align-self: start;
    z-index: 1;
}

.custom-select-wrap.is-open {
    z-index: 20;
}

.native-select-hidden {
    display: none !important;
}

.custom-select-trigger {
    width: 100%;
    min-height: 46px;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;

    padding: 13px 14px;

    border: 1px solid var(--search-border);
    border-radius: 11px;

    background: var(--search-soft);
    color: var(--search-text);

    font-family: inherit;
    font-size: 14px;
    font-weight: 800;

    cursor: pointer;

    transition: border-color .2s ease, box-shadow .2s ease;
}

.custom-select-trigger:hover,
.custom-select-wrap.is-open .custom-select-trigger {
    border-color: #12a99f;
    box-shadow: 0 0 0 4px rgba(18,169,159,.12);
}

.custom-select-arrow {
    width: 9px;
    height: 9px;
    flex: 0 0 9px;

    border-right: 2px solid currentColor;
    border-bottom: 2px solid currentColor;

    transform: rotate(45deg) translateY(-2px);

    transition: transform .2s ease;
}

.custom-select-wrap.is-open .custom-select-arrow {
    transform: rotate(225deg) translate(-2px,-1px);
}


/*
 Dropdown নিজে overlay হিসেবে খুলবে।
 তাই উপরের search controls নড়বে না।
 নিচের Matching reports সরানোর জন্য
 JavaScript form-এর margin-bottom বাড়াবে।
*/

.custom-select-menu {
    position: absolute;
    top: calc(100% + 9px);
    left: 0;

    width: 100%;
    box-sizing: border-box;

    display: none;

    max-height: 245px;
    overflow-y: auto;
    overscroll-behavior: contain;

    padding: 7px;
    margin: 0;

    border: 1px solid var(--search-border);
    border-radius: 14px;

    background: var(--search-panel);

    box-shadow: 0 10px 24px rgba(0,0,0,.14);

    animation: dropdownAppear .2s ease;
}

.custom-select-wrap.is-open .custom-select-menu {
    display: block;
}

@keyframes dropdownAppear {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.custom-select-option {
    width: 100%;
    display: block;

    padding: 12px 13px;
    margin: 2px 0;

    border: 0;
    border-radius: 10px;

    background: transparent;
    color: var(--search-text);

    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    text-align: left;

    cursor: pointer;

    transition: background .18s ease, color .18s ease;
}

.custom-select-option:hover,
.custom-select-option:focus-visible {
    outline: none;
    background: var(--search-soft);
}

.custom-select-option.is-selected {
    background: linear-gradient(110deg,#078f91,#5366e8);
    color: #ffffff;
}


/* =====================================
   BUTTONS
===================================== */

.search-form button[type="submit"],
.reset-btn {
    display: flex;
    align-items: center;
    justify-content: center;

    min-height: 46px;
    box-sizing: border-box;

    border: none;
    border-radius: 11px;

    padding: 12px 15px;

    font-size: 14px;
    font-weight: 800;

    cursor: pointer;
    text-align: center;
    text-decoration: none;

    transition: transform .2s ease, box-shadow .2s ease;
}

.search-form button[type="submit"] {
    background: linear-gradient(110deg,#078f91,#5366e8);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(55,115,180,.20);
}

.search-form button[type="submit"]:hover,
.reset-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 11px 22px rgba(55,115,180,.25);
}

.reset-btn {
    background: #53657a;
    color: #ffffff;
}


/* =====================================
   RESULTS HEADING
===================================== */

.search-results-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 12px;
    margin: 0 0 19px;
}

.search-results-heading h3 {
    color: var(--search-text);
    font-size: 22px;
    font-weight: 900;
    margin: 0;
}

.search-results-heading span {
    color: var(--search-muted);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1px;
}


/* =====================================
   ITEM CARDS
===================================== */

.search-results {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(240px,1fr));
    gap: 22px;
}

.item-card {
    position: relative;
    overflow: hidden;

    background: var(--search-panel);
    border: 1px solid var(--search-border);
    border-radius: 18px;

    padding: 18px;

    box-shadow: 0 8px 22px var(--search-shadow);

    transition: transform .28s ease, box-shadow .28s ease;
}

.item-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;

    background: linear-gradient(90deg,#12b8a6,#5366e8,#a66be8);
}

.item-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 17px 34px rgba(35,100,135,.16);
}

.item-card img {
    display: block;
    width: 100%;
    height: 190px;

    object-fit: cover;

    border-radius: 12px;
    background: var(--search-soft);
}

.no-image {
    height: 190px;

    background: linear-gradient(135deg,#e7f8f7,#edf0ff);
    color: #526b80;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;
    font-weight: 700;
}

.item-card h3 {
    margin: 16px 0 12px;
    color: var(--search-text);

    font-size: 19px;
    font-weight: 900;
    line-height: 1.35;

    overflow-wrap: anywhere;
}

.item-card p {
    color: var(--search-muted);
    font-size: 14px;
    line-height: 1.6;
    margin: 9px 0;
    overflow-wrap: anywhere;
}


/* =====================================
   LOST / FOUND BADGES
===================================== */

.item-type {
    display: inline-block;

    padding: 6px 12px;
    border-radius: 20px;

    font-size: 12px;
    font-weight: 900;

    margin: 10px 0 3px;

    letter-spacing: .4px;
    text-transform: uppercase;
}

.item-type.lost {
    background: #fee2e2;
    color: #b91c1c;
}

.item-type.found {
    background: #dcfce7;
    color: #15803d;
}


/* =====================================
   VIEW DETAILS
===================================== */

.item-card > a {
    display: block;
    text-align: center;

    background: linear-gradient(110deg,#078f91,#5366e8);
    color: #ffffff;

    padding: 12px;
    border-radius: 10px;

    text-decoration: none;
    margin-top: 14px;

    font-size: 14px;
    font-weight: 800;

    box-shadow: 0 7px 16px rgba(55,115,180,.18);

    transition: transform .2s ease, box-shadow .2s ease;
}

.item-card > a:hover {
    transform: translateY(-2px);
    box-shadow: 0 11px 21px rgba(55,115,180,.25);
}


/* =====================================
   NO RESULTS
===================================== */

.no-results {
    grid-column: 1 / -1;

    color: var(--search-muted);
    background: var(--search-panel);

    border: 1px dashed var(--search-border);
    padding: 28px;

    border-radius: 15px;
    text-align: center;
    font-weight: 700;
}


/* =====================================
   DARK MODE
===================================== */

body.dark-mode .search-page,
html.dark .search-page,
[data-theme="dark"] .search-page,
.dark-mode .search-page {
    --search-panel: #172b3d;
    --search-text: #f1f5f9;
    --search-muted: #cbd5e1;
    --search-border: #344b60;
    --search-soft: #20384d;
    --search-shadow: rgba(0,0,0,.22);

    color: #f1f5f9;
}

body.dark-mode .search-hero,
html.dark .search-hero,
[data-theme="dark"] .search-hero,
.dark-mode .search-hero {
    background: linear-gradient(120deg,#123e4c,#1d355d,#392c60);
    border-color: #385c79;
    box-shadow: 0 16px 38px rgba(0,0,0,.25);
}

body.dark-mode .search-hero::before,
html.dark .search-hero::before,
[data-theme="dark"] .search-hero::before,
.dark-mode .search-hero::before {
    background: rgba(255,255,255,.06);
    box-shadow: 0 0 55px rgba(95,220,220,.08);
}

body.dark-mode .search-hero-label,
html.dark .search-hero-label,
[data-theme="dark"] .search-hero-label,
.dark-mode .search-hero-label {
    background: rgba(255,255,255,.10);
    color: #8fffe8;
    border: 1px solid rgba(143,255,232,.18);
}

body.dark-mode .search-hero h2,
html.dark .search-hero h2,
[data-theme="dark"] .search-hero h2,
.dark-mode .search-hero h2 {
    color: #f0fffd !important;
}

body.dark-mode .search-hero p,
html.dark .search-hero p,
[data-theme="dark"] .search-hero p,
.dark-mode .search-hero p {
    color: #d2e8f0 !important;
}

body.dark-mode .search-form,
html.dark .search-form,
[data-theme="dark"] .search-form,
.dark-mode .search-form {
    background: #172b3d;
    border-color: #344b60;
}

body.dark-mode .search-form::before,
html.dark .search-form::before,
[data-theme="dark"] .search-form::before,
.dark-mode .search-form::before {
    color: #cbd5e1;
}

body.dark-mode .search-form input,
html.dark .search-form input,
[data-theme="dark"] .search-form input,
.dark-mode .search-form input {
    background: #0e1d2c;
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff;
    border-color: #476176;
    font-weight: 600;
}

body.dark-mode .search-form input::placeholder,
html.dark .search-form input::placeholder,
[data-theme="dark"] .search-form input::placeholder,
.dark-mode .search-form input::placeholder {
    color: #c6d8e8 !important;
    opacity: 1;
}

body.dark-mode .custom-select-trigger,
html.dark .custom-select-trigger,
[data-theme="dark"] .custom-select-trigger,
.dark-mode .custom-select-trigger {
    background: #0e1d2c;
    color: #ffffff;
    border-color: #476176;
}

body.dark-mode .custom-select-menu,
html.dark .custom-select-menu,
[data-theme="dark"] .custom-select-menu,
.dark-mode .custom-select-menu {
    background: #102638;
    border-color: #476176;
}

body.dark-mode .custom-select-option,
html.dark .custom-select-option,
[data-theme="dark"] .custom-select-option,
.dark-mode .custom-select-option {
    color: #ffffff;
}

body.dark-mode .custom-select-option:hover,
html.dark .custom-select-option:hover,
[data-theme="dark"] .custom-select-option:hover,
.dark-mode .custom-select-option:hover {
    background: #29445b;
    color: #ffffff;
}

body.dark-mode .custom-select-option.is-selected,
html.dark .custom-select-option.is-selected,
[data-theme="dark"] .custom-select-option.is-selected,
.dark-mode .custom-select-option.is-selected {
    background: linear-gradient(110deg,#078f91,#5366e8);
    color: #ffffff;
}

body.dark-mode .search-results-heading h3,
html.dark .search-results-heading h3,
[data-theme="dark"] .search-results-heading h3,
.dark-mode .search-results-heading h3 {
    color: #f1f5f9;
}

body.dark-mode .search-results-heading span,
html.dark .search-results-heading span,
[data-theme="dark"] .search-results-heading span,
.dark-mode .search-results-heading span {
    color: #cbd5e1;
}

body.dark-mode .item-card,
html.dark .item-card,
[data-theme="dark"] .item-card,
.dark-mode .item-card {
    background: #172b3d;
    border-color: #344b60;
    box-shadow: 0 8px 22px rgba(0,0,0,.22);
}

body.dark-mode .item-card h3,
html.dark .item-card h3,
[data-theme="dark"] .item-card h3,
.dark-mode .item-card h3 {
    color: #f8fafc !important;
}

body.dark-mode .item-card p,
html.dark .item-card p,
[data-theme="dark"] .item-card p,
.dark-mode .item-card p {
    color: #cbd5e1 !important;
}

body.dark-mode .no-image,
html.dark .no-image,
[data-theme="dark"] .no-image,
.dark-mode .no-image {
    background: linear-gradient(135deg,#1b3448,#26365b);
    color: #d8e7f2;
}

body.dark-mode .item-type.lost,
html.dark .item-type.lost,
[data-theme="dark"] .item-type.lost,
.dark-mode .item-type.lost {
    background: #512b35;
    color: #fecdd3;
}

body.dark-mode .item-type.found,
html.dark .item-type.found,
[data-theme="dark"] .item-type.found,
.dark-mode .item-type.found {
    background: #164b3a;
    color: #a9f5ce;
}

body.dark-mode .no-results,
html.dark .no-results,
[data-theme="dark"] .no-results,
.dark-mode .no-results {
    background: #172b3d;
    border-color: #476176;
    color: #cbd5e1;
}


/* =====================================
   RESPONSIVE
===================================== */

@media (max-width: 650px) {

    .search-page {
        margin: 22px auto 35px;
        padding: 13px;
    }

    .search-hero {
        padding: 25px 21px;
        border-radius: 19px;
    }

    .search-hero h2 {
        font-size: 29px;
    }

    .search-hero p {
        font-size: 14px;
    }

    .search-form {
        grid-template-columns: 1fr;
        padding: 19px;
        border-radius: 16px;
    }

    .search-results {
        grid-template-columns: 1fr;
        gap: 17px;
    }

    .search-results-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .custom-select-menu {
        max-height: 210px;
    }

}

@media (prefers-reduced-motion: reduce) {

    .search-hero,
    .item-card,
    .search-form button,
    .reset-btn,
    .item-card > a {
        animation: none;
        transition: none;
    }

    .search-hero:hover,
    .item-card:hover {
        transform: none;
    }

}

</style>


<div class="search-page">

    <!-- HERO SECTION -->

    <section class="search-hero">

        <div class="search-hero-content">

            <span class="search-hero-label">
                RE:CLAIM • CAMPUS COMMUNITY
            </span>

            <h2>Discover. Connect. Reunite.</h2>

            <p>
                Browse item reports shared by your campus community.
                Search by item name, category, or location to help a lost
                belonging find its way home.
            </p>

        </div>

    </section>


    <!-- SEARCH FORM -->

    <form method="GET" action="" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Search item name..."
            value="<?php echo htmlspecialchars($search); ?>"
        >


        <!-- CATEGORY DROPDOWN -->

        <div class="custom-select-wrap">

            <select
                name="category_id"
                class="native-select-hidden"
                aria-hidden="true"
                tabindex="-1"
            >

                <option value="">All Categories</option>

                <?php while ($cat = mysqli_fetch_assoc($category_result)) { ?>

                    <option
                        value="<?php echo htmlspecialchars($cat['category_id']); ?>"
                        <?php echo ($category_id == $cat['category_id']) ? "selected" : ""; ?>
                    >
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>

                <?php } ?>

            </select>

            <button
                type="button"
                class="custom-select-trigger"
                aria-expanded="false"
            >

                <span class="custom-select-value">
                    All Categories
                </span>

                <span class="custom-select-arrow" aria-hidden="true"></span>

            </button>

            <div class="custom-select-menu" role="listbox"></div>

        </div>


        <!-- REPORT TYPE DROPDOWN -->

        <div class="custom-select-wrap">

            <select
                name="item_type"
                class="native-select-hidden"
                aria-hidden="true"
                tabindex="-1"
            >

                <option value="" <?php echo ($item_type === "") ? "selected" : ""; ?>>
                    All report types
                </option>

                <option value="lost" <?php echo ($item_type === "lost") ? "selected" : ""; ?>>
                    Reported lost
                </option>

                <option value="found" <?php echo ($item_type === "found") ? "selected" : ""; ?>>
                    Reported found
                </option>

            </select>

            <button
                type="button"
                class="custom-select-trigger"
                aria-expanded="false"
            >

                <span class="custom-select-value">
                    All report types
                </span>

                <span class="custom-select-arrow" aria-hidden="true"></span>

            </button>

            <div class="custom-select-menu" role="listbox"></div>

        </div>


        <!-- LOCATION SEARCH -->

        <input
            type="text"
            name="location"
            placeholder="Search location..."
            value="<?php echo htmlspecialchars($location); ?>"
        >


        <!-- APPLY FILTERS -->

        <button type="submit">
            Apply filters
        </button>


        <!-- CLEAR FILTERS -->

        <a href="search.php" class="reset-btn">
            Clear filters
        </a>

    </form>


    <!-- RESULTS HEADING -->

    <div class="search-results-heading">

        <h3>Matching reports</h3>

        <span>EXPLORE CAMPUS LISTINGS</span>

    </div>


    <!-- ITEM RESULTS -->

    <div class="search-results">

        <?php if (mysqli_num_rows($result) > 0) { ?>

            <?php while ($item = mysqli_fetch_assoc($result)) { ?>

                <div class="item-card">

                    <?php if (!empty($item["image"])) { ?>

                        <img
                            src="../uploads/items/<?php echo rawurlencode(basename($item["image"])); ?>"
                            alt="Item image"
                            loading="lazy"
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
                        Category:
                        <?php echo htmlspecialchars($item["category_name"]); ?>
                    </p>


                    <p>
                        Location:
                        <?php echo htmlspecialchars($item["location"]); ?>
                    </p>


                    <p>
                        Date:
                        <?php echo htmlspecialchars($item["item_date"]); ?>
                    </p>


                    <span class="item-type <?php echo htmlspecialchars($item["item_type"]); ?>">
                        <?php echo ucfirst($item["item_type"]); ?>
                    </span>


                    <a href="item_details.php?id=<?php echo (int)$item["item_id"]; ?>">
                        View Details
                    </a>

                </div>

            <?php } ?>

        <?php } else { ?>

            <p class="no-results">
                No matching items found.
            </p>

        <?php } ?>

    </div>

</div>


<!-- =====================================
     CUSTOM DROPDOWN JAVASCRIPT
===================================== -->

<script>

document.addEventListener("DOMContentLoaded", function () {

    const dropdowns = document.querySelectorAll(
        ".custom-select-wrap"
    );

    const searchForm = document.querySelector(".search-form");

    if (!searchForm) return;


    /*
     * Calculate the extra space required below
     * the search form when a dropdown is open.
     *
     * Only the form's bottom margin changes.
     * Other search controls remain in their positions.
     */

    function updateDropdownSpace() {

        let requiredSpace = 0;

        dropdowns.forEach(function (wrap) {

            if (wrap.classList.contains("is-open")) {

                const menu = wrap.querySelector(
                    ".custom-select-menu"
                );

                if (menu) {

                    const menuHeight = Math.min(
                        menu.scrollHeight,
                        245
                    );

                    requiredSpace = Math.max(
                        requiredSpace,
                        menuHeight + 9
                    );

                }

            }

        });

        searchForm.style.setProperty(
            "--dropdown-space",
            requiredSpace + "px"
        );

    }


    // Close other dropdowns

    function closeAll(except) {

        dropdowns.forEach(function (wrap) {

            if (wrap !== except) {

                wrap.classList.remove("is-open");

                const trigger = wrap.querySelector(
                    ".custom-select-trigger"
                );

                if (trigger) {

                    trigger.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                }

            }

        });

        updateDropdownSpace();

    }


    dropdowns.forEach(function (wrap) {

        const select = wrap.querySelector("select");

        const trigger = wrap.querySelector(
            ".custom-select-trigger"
        );

        const valueLabel = wrap.querySelector(
            ".custom-select-value"
        );

        const menu = wrap.querySelector(
            ".custom-select-menu"
        );


        if (!select || !trigger || !valueLabel || !menu) {
            return;
        }


        // Update selected option

        function updateSelection() {

            const selectedOption =
                select.options[select.selectedIndex];

            valueLabel.textContent = selectedOption
                ? selectedOption.textContent.trim()
                : "";


            menu.querySelectorAll(
                ".custom-select-option"
            ).forEach(function (button) {

                const selected =
                    button.dataset.value === select.value;

                button.classList.toggle(
                    "is-selected",
                    selected
                );

                button.setAttribute(
                    "aria-selected",
                    selected ? "true" : "false"
                );

            });

        }


        // Create dropdown options

        Array.from(select.options).forEach(function (option) {

            const optionButton = document.createElement("button");

            optionButton.type = "button";

            optionButton.className = "custom-select-option";

            optionButton.textContent =
                option.textContent.trim();

            optionButton.dataset.value = option.value;

            optionButton.setAttribute("role", "option");


            optionButton.addEventListener("click", function () {

                select.value = option.value;

                select.dispatchEvent(
                    new Event("change", { bubbles: true })
                );

                updateSelection();

                wrap.classList.remove("is-open");

                trigger.setAttribute(
                    "aria-expanded",
                    "false"
                );

                updateDropdownSpace();

                trigger.focus();

            });


            menu.appendChild(optionButton);

        });


        // Open and close dropdown

        trigger.addEventListener("click", function () {

            const willOpen =
                !wrap.classList.contains("is-open");

            closeAll(wrap);

            wrap.classList.toggle(
                "is-open",
                willOpen
            );

            trigger.setAttribute(
                "aria-expanded",
                willOpen ? "true" : "false"
            );

            updateDropdownSpace();

        });


        select.addEventListener("change", updateSelection);

        updateSelection();

    });


    // Close dropdown when clicking outside

    document.addEventListener("click", function (event) {

        if (!event.target.closest(".custom-select-wrap")) {
            closeAll(null);
        }

    });


    // Close dropdown using Escape key

    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape") {
            closeAll(null);
        }

    });

});

</script>


<?php

mysqli_stmt_close($stmt);

include "../includes/footer.php";

?>
