
<?php

require_once __DIR__ . "/../includes/session.php";
require_once "../config/database.php";
require_once "../includes/mailer.php";

// Check admin access
if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: ../login.php");
    exit();
}


// =============================================
// APPROVE OR REJECT CLAIM
// =============================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $claim_id = intval($_POST["claim_id"] ?? 0);
    $status = $_POST["status"] ?? "";

    if (
        $claim_id > 0 &&
        in_array($status, ["approved", "rejected"], true)
    ) {

        mysqli_begin_transaction($conn);

        try {

            // Get claim and student details
            $claim_sql = "
                SELECT
                    claims.claim_id,
                    claims.item_id,
                    claims.claim_status,
                    users.name AS student_name,
                    users.email AS student_email,
                    items.item_name
                FROM claims
                INNER JOIN users
                    ON claims.claimant_id = users.user_id
                INNER JOIN items
                    ON claims.item_id = items.item_id
                WHERE claims.claim_id = ?
                FOR UPDATE
            ";

            $claim_stmt = mysqli_prepare($conn, $claim_sql);

            if (!$claim_stmt) {
                throw new Exception("Claim query preparation failed.");
            }

            mysqli_stmt_bind_param(
                $claim_stmt,
                "i",
                $claim_id
            );

            if (!mysqli_stmt_execute($claim_stmt)) {
                throw new Exception("Claim query execution failed.");
            }

            $claim_result = mysqli_stmt_get_result($claim_stmt);

            $claim = mysqli_fetch_assoc($claim_result);

            mysqli_stmt_close($claim_stmt);

            if (
                $claim &&
                $claim["claim_status"] === "pending"
            ) {

                // Update claim status
                $update_sql = "
                    UPDATE claims
                    SET claim_status = ?
                    WHERE claim_id = ?
                    AND claim_status = 'pending'
                ";

                $update_stmt = mysqli_prepare($conn, $update_sql);

                if (!$update_stmt) {
                    throw new Exception("Claim update preparation failed.");
                }

                mysqli_stmt_bind_param(
                    $update_stmt,
                    "si",
                    $status,
                    $claim_id
                );

                if (!mysqli_stmt_execute($update_stmt)) {
                    throw new Exception("Claim update failed.");
                }

                $updated = mysqli_stmt_affected_rows($update_stmt);

                mysqli_stmt_close($update_stmt);

                if ($updated === 1 && $status === "approved") {

                    // Mark item as claimed
                    $item_sql = "
                        UPDATE items
                        SET status = 'claimed'
                        WHERE item_id = ?
                    ";

                    $item_stmt = mysqli_prepare($conn, $item_sql);

                    if (!$item_stmt) {
                        throw new Exception("Item update preparation failed.");
                    }

                    mysqli_stmt_bind_param(
                        $item_stmt,
                        "i",
                        $claim["item_id"]
                    );

                    if (!mysqli_stmt_execute($item_stmt)) {
                        throw new Exception("Item update failed.");
                    }

                    mysqli_stmt_close($item_stmt);


                    // Reject other pending claims
                    $other_sql = "
                        UPDATE claims
                        SET claim_status = 'rejected'
                        WHERE item_id = ?
                        AND claim_id != ?
                        AND claim_status = 'pending'
                    ";

                    $other_stmt = mysqli_prepare($conn, $other_sql);

                    if (!$other_stmt) {
                        throw new Exception("Other claims query preparation failed.");
                    }

                    mysqli_stmt_bind_param(
                        $other_stmt,
                        "ii",
                        $claim["item_id"],
                        $claim_id
                    );

                    if (!mysqli_stmt_execute($other_stmt)) {
                        throw new Exception("Other claims update failed.");
                    }

                    mysqli_stmt_close($other_stmt);
                }

            }

            // Commit database changes first
            mysqli_commit($conn);


            // =============================================
            // SEND APPROVAL EMAIL
            // =============================================

            if (
                $claim &&
                $claim["claim_status"] === "pending" &&
                $status === "approved"
            ) {

                $email_sent = catchit_send_claim_approval_email(
                    $claim["student_email"],
                    $claim["student_name"],
                    $claim["item_name"],
                    $claim["claim_id"]
                );

                if ($email_sent) {

                    error_log(
                        "Re:Claim: Approval email sent for claim ID " .
                        $claim_id
                    );

                } else {

                    error_log(
                        "Re:Claim: Approval email failed for claim ID " .
                        $claim_id
                    );
                }
            }

        } catch (Throwable $e) {

            mysqli_rollback($conn);

            error_log(
                "Re:Claim claim approval error: " .
                $e->getMessage()
            );
        }

    }

    header("Location: manage_claims.php");
    exit();
}


// =============================================
// FETCH ALL CLAIMS
// =============================================

$sql = "
    SELECT
        claims.*,
        users.name AS student_name,
        items.item_name
    FROM claims
    LEFT JOIN users
        ON claims.claimant_id = users.user_id
    LEFT JOIN items
        ON claims.item_id = items.item_id
    ORDER BY claims.created_at DESC
";

$result = mysqli_query($conn, $sql);

?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/admin_sidebar.php"; ?>


<div class="container">

    <h2>Review claim requests</h2>

    <table border="1" cellpadding="10" cellspacing="0" width="100%">

        <tr>
            <th>Claim ID</th>
            <th>Item Name</th>
            <th>Student Name</th>
            <th>Claim Message</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($result && mysqli_num_rows($result) > 0) { ?>

            <?php while ($claim = mysqli_fetch_assoc($result)) { ?>

                <tr>

                    <td>
                        <?php echo (int) $claim["claim_id"]; ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $claim["item_name"] ?? "Unknown"
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $claim["student_name"] ?? "Unknown"
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $claim["message"] ?? ""
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $claim["created_at"] ?? ""
                        );
                        ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $claim["claim_status"]
                        );
                        ?>
                    </td>

                    <td>

                        <?php if ($claim["claim_status"] === "pending") { ?>

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="claim_id"
                                    value="<?php echo (int) $claim["claim_id"]; ?>"
                                >

                                <button
                                    type="submit"
                                    name="status"
                                    value="approved"
                                >
                                    Approve
                                </button>

                                <button
                                    type="submit"
                                    name="status"
                                    value="rejected"
                                >
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
                <td colspan="7">
                    No claim requests found.
                </td>
            </tr>

        <?php } ?>

    </table>

</div>


<?php include "../includes/footer.php"; ?>
