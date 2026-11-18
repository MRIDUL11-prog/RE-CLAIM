
<?php
require_once "config/database.php";
?>

<?php include "includes/header.php"; ?>
<?php include "includes/navbar.php"; ?>

<!-- Hero Section -->
<section class="hero">

    <div class="hero-copy">

        <p class="hero-kicker">
            CAMPUS LOST &amp; FOUND · ACROSS EVERY UNIVERSE
        </p>

        <h1>
            Every lost thing has a way <span>back.</span>
        </h1>

        <p>
            Explore a campus-wide universe of lost and found belongings.
            Share a report, follow a clue, and help an item find its way home.
        </p>

        <div class="hero-buttons">

            <a href="login.php" class="btn lost-btn">
                Report a lost item
            </a>

            <a href="login.php" class="btn found-btn">
                Report a found item
            </a>

        </div>

    </div>


    <div class="multiverse-stage"
         aria-label="RE:CLAIM helps students search for missing belongings, report found items, and reconnect belongings with their owners.">

        <div class="multiverse-rings" aria-hidden="true">

            <span class="multiverse-core">
                <img
                    src="images/brand/reclaim-icon.png"
                    alt=""
                    decoding="async"
                >
            </span>

        </div>


        <div class="item-orbit item-orbit-search">

            <span class="orbit-icon" aria-hidden="true">⌕</span>

            <span class="orbit-copy">
                <strong>Find an item</strong>
                <small>SEARCH LISTINGS</small>
            </span>

        </div>


        <div class="item-orbit item-orbit-report">

            <span class="orbit-icon" aria-hidden="true">＋</span>

            <span class="orbit-copy">
                <strong>Report a find</strong>
                <small>HELP SOMEONE</small>
            </span>

        </div>


        <div class="item-orbit item-orbit-reconnect">

            <span class="orbit-icon" aria-hidden="true">↗</span>

            <span class="orbit-copy">
                <strong>Reconnect</strong>
                <small>BACK TO ITS OWNER</small>
            </span>

        </div>


        <div class="item-orbit item-orbit-belongings">

            <span class="orbit-icon" aria-hidden="true">◇</span>

            <span class="orbit-copy">
                <strong>Campus items</strong>
                <small>LOST &amp; FOUND</small>
            </span>

        </div>

    </div>

</section>


<!-- Search Section -->
<section class="search-section">

    <h2>Search campus listings</h2>

    <form action="student/search.php" method="GET">

        <input
            type="text"
            name="search"
            placeholder="Search by item name..."
        >

        <button type="submit">
            Search listings
        </button>

    </form>

</section>


<!-- Lost Items Section -->
<section class="items-section">

    <h2>Recently reported lost</h2>

    <div class="item-container">

        <?php

        $sql = "SELECT * FROM items
                WHERE item_type = 'lost'
                AND status = 'approved'
                ORDER BY created_at DESC
                LIMIT 4";

        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {

            while ($item = mysqli_fetch_assoc($result)) {

        ?>

            <div class="item-card">

                <?php if (!empty($item['image'])) { ?>

                    <img
                        src="uploads/items/<?php
                        echo rawurlencode(basename($item['image']));
                        ?>"
                        alt="Lost item"
                        width="640"
                        height="400"
                        class="image-skeleton"
                        data-loading-skeleton
                        loading="lazy"
                        decoding="async"
                    >

                <?php } else { ?>

                    <div class="no-image">No Image Available</div>

                <?php } ?>


                <h3>
                    <?php
                    echo htmlspecialchars($item['item_name']);
                    ?>
                </h3>


                <p>
                    Location:
                    <?php
                    echo htmlspecialchars($item['location']);
                    ?>
                </p>


                <p>
                    Date:
                    <?php
                    echo htmlspecialchars($item['item_date']);
                    ?>
                </p>


                <a href="student/item_details.php?id=<?php
                    echo (int)$item['item_id'];
                ?>">
                    View Details
                </a>

            </div>

        <?php

            }

        } else {

            echo "<p>No lost-item reports are available right now.</p>";

        }

        ?>

    </div>

</section>


<!-- Found Items Section -->
<section class="items-section">

    <h2>Recently reported found</h2>

    <div class="item-container">

        <?php

        $sql = "SELECT * FROM items
                WHERE item_type = 'found'
                AND status = 'approved'
                ORDER BY created_at DESC
                LIMIT 4";

        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {

            while ($item = mysqli_fetch_assoc($result)) {

        ?>

            <div class="item-card">

                <?php if (!empty($item['image'])) { ?>

                    <img
                        src="uploads/items/<?php
                        echo rawurlencode(basename($item['image']));
                        ?>"
                        alt="Found item"
                        width="640"
                        height="400"
                        class="image-skeleton"
                        data-loading-skeleton
                        loading="lazy"
                        decoding="async"
                    >

                <?php } else { ?>

                    <div class="no-image">No Image Available</div>

                <?php } ?>


                <h3>
                    <?php
                    echo htmlspecialchars($item['item_name']);
                    ?>
                </h3>


                <p>
                    Location:
                    <?php
                    echo htmlspecialchars($item['location']);
                    ?>
                </p>


                <p>
                    Date:
                    <?php
                    echo htmlspecialchars($item['item_date']);
                    ?>
                </p>


                <a href="student/item_details.php?id=<?php
                    echo (int)$item['item_id'];
                ?>">
                    View Details
                </a>

            </div>

        <?php

            }

        } else {

            echo "<p>No found-item reports are available right now.</p>";

        }

        ?>

    </div>

</section>


<?php include "includes/footer.php"; ?>

<html>
    <Body>
        <style>
            /* =========================================
   HOMEPAGE LIGHT MODE - TEXT & EMOJI FIX
   ========================================= */

/* Recently Reported Lost / Found headings */
html[data-theme="light"] .items-section h2 {
    color: #211B49 !important;
    text-shadow: none !important;
}

/* Campus Items emoji visibility */
html[data-theme="light"] .item-orbit-belongings .orbit-icon {
    background: linear-gradient(145deg, #E0DFFF, #A9A5FF) !important;
    border: 1px solid rgba(70, 55, 160, 0.45) !important;
    color: #211B49 !important;
    text-shadow: 0 2px 5px rgba(25, 20, 70, 0.35) !important;
    box-shadow: 0 0 14px rgba(102, 102, 255, 0.35) !important;
}
/* Campus Items Emoji - Dark Mode */
html[data-theme="dark"] .item-orbit-belongings .orbit-icon {
    background: linear-gradient(145deg, #E0DFFF, #A9A5FF) !important;
    border: 1px solid rgba(70, 55, 160, 0.45) !important;
    color: #211B49 !important;
    text-shadow: 0 2px 5px rgba(25, 20, 70, 0.35) !important;
    box-shadow: 0 0 14px rgba(102, 102, 255, 0.35) !important;
}
</style>
</body>
</html>