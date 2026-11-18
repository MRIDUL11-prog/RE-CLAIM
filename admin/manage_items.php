
<?php
require_once __DIR__ . "/../includes/session.php";

require_once "../config/database.php";

// Check admin login
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

// Handle approve or reject
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $item_id = intval($_POST["item_id"]);
    $status = $_POST["status"];

    if (in_array($status, ["approved", "rejected"])) {

        $sql = "UPDATE items SET status = ? WHERE item_id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "si", $status, $item_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: manage_items.php");
    exit();
}

// Fetch all items
$sql = "SELECT items.*, users.name
        FROM items
        LEFT JOIN users ON items.user_id = users.user_id
        ORDER BY items.created_at DESC";

$result = mysqli_query($conn, $sql);
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/admin_sidebar.php"; ?>

<div class="container">
    <h2>Review item reports</h2>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">
        <tr>
            <th>ID</th>
            <th>Item Name</th>
            <th>Type</th>
            <th>Image</th>
            <th>Description</th>
            <th>Location</th>
            <th>Reported By</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($result && mysqli_num_rows($result) > 0) { ?>

            <?php while ($item = mysqli_fetch_assoc($result)) { ?>

                <tr>
                    <td><?php echo $item["item_id"]; ?></td>

                    <td><?php echo htmlspecialchars($item["item_name"]); ?></td>

                    <td><?php echo htmlspecialchars($item["item_type"]); ?></td>

                    <td>
                        <?php if (!empty($item["image"])) { ?>
                            <img
                                src="../uploads/items/<?php
                                echo rawurlencode(basename($item["image"]));
                                ?>"
                                alt="Image of <?php echo htmlspecialchars($item["item_name"]); ?>"
                                class="admin-item-image image-skeleton"
                                data-loading-skeleton
                                loading="lazy"
                                decoding="async"
                            >
                        <?php } else { ?>
                            <span>No image</span>
                        <?php } ?>
                    </td>

                    <td><?php echo htmlspecialchars($item["description"]); ?></td>

                    <td><?php echo htmlspecialchars($item["location"]); ?></td>

                    <td><?php echo htmlspecialchars($item["name"] ?? "Unknown"); ?></td>

                    <td><?php echo htmlspecialchars($item["status"]); ?></td>

                    <td>
                        <?php if ($item["status"] == "pending") { ?>

                            <form method="POST">
                                <input type="hidden" name="item_id"
                                       value="<?php echo $item["item_id"]; ?>">

                                <button type="submit" name="status"
                                        value="approved">
                                    Approve
                                </button>

                                <button type="submit" name="status"
                                        value="rejected">
                                    Reject
                                </button>
                            </form>

                        <?php } else { ?>

                            No action needed

                        <?php } ?>
                    </td>
                </tr>

            <?php } ?>

        <?php } else { ?>

            <tr>
                <td colspan="9">No items found.</td>
            </tr>

        <?php } ?>

    </table>
</div>

<?php include "../includes/footer.php"; ?>
