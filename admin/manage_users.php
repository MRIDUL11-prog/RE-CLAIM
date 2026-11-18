<?php
require_once __DIR__ . "/../includes/session.php";

require_once "../config/database.php";

// Check admin access
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

$update_msg = "";
$update_type = "success";

// Handle User Account Editing / Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_user"])) {
    $edit_user_id = intval($_POST["user_id"] ?? 0);
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $student_id = trim($_POST["student_id"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $role = in_array($_POST["role"] ?? "", ["student", "admin"]) ? $_POST["role"] : "student";

    if ($edit_user_id > 0 && !empty($name) && !empty($email)) {
        $upd_sql = "UPDATE users SET name = ?, email = ?, student_id = ?, department = ?, role = ? WHERE user_id = ?";
        $upd_stmt = mysqli_prepare($conn, $upd_sql);
        if ($upd_stmt) {
            mysqli_stmt_bind_param($upd_stmt, "sssssi", $name, $email, $student_id, $department, $role, $edit_user_id);
            if (mysqli_stmt_execute($upd_stmt)) {
                $update_msg = "Account for " . htmlspecialchars($name) . " successfully updated.";
                $update_type = "success";
            } else {
                $update_msg = "Database error while updating account.";
                $update_type = "error";
            }
            mysqli_stmt_close($upd_stmt);
        }
    } else {
        $update_msg = "Name and email cannot be empty.";
        $update_type = "error";
    }
}

// Current action & search handling
$current_action = $_GET["action"] ?? "view";
$edit_target_id = intval($_GET["edit_id"] ?? 0);
$search = "";

$sql = "SELECT user_id, name, email, student_id,
               department, role, created_at
        FROM users";

if ($_SERVER["REQUEST_METHOD"] === "GET" && !empty($_GET["search"])) {
    $search = trim($_GET["search"]);

    $sql .= " WHERE name LIKE ?
              OR email LIKE ?
              OR student_id LIKE ?
              OR department LIKE ?";

    $sql .= " ORDER BY user_id DESC";

    $stmt = mysqli_prepare($conn, $sql);
    $search_value = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $search_value,
        $search_value,
        $search_value,
        $search_value
    );

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $sql .= " ORDER BY user_id DESC";
    $result = mysqli_query($conn, $sql);
}

$script_directory = str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"]));
$base_url = rtrim(
    preg_replace("~/(student|admin)$~", "", $script_directory),
    "/"
);
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/admin_sidebar.php"; ?>

<style>
/* Unified Accounts Glassmorphism Navbar */
.accounts-navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    background: rgba(18, 18, 36, 0.78);
    border: 1px solid rgba(201, 232, 255, 0.18);
    border-top: 1px solid rgba(201, 232, 255, 0.28);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-radius: 14px;
    padding: 10px 14px;
    margin-bottom: 24px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45), inset 0 1px 1px rgba(201, 232, 255, 0.15);
}

.accounts-nav-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.accounts-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 10px;
    background: rgba(184, 186, 255, 0.08);
    border: 1px solid rgba(201, 232, 255, 0.14);
    color: #C9E8FF;
    font-family: 'Space Grotesk', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.accounts-nav-link:hover {
    color: #ffffff;
    background: rgba(102, 102, 255, 0.18);
    border-color: rgba(184, 186, 255, 0.35);
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(102, 102, 255, 0.25);
}

.accounts-nav-link.is-active {
    color: #ffffff !important;
    background: linear-gradient(135deg, rgba(102, 102, 255, 0.35), rgba(184, 186, 255, 0.18)) !important;
    border-color: #6666FF !important;
    box-shadow: 0 0 18px rgba(102, 102, 255, 0.35), inset 0 1px 1px rgba(201, 232, 255, 0.3);
}

.accounts-nav-reset {
    color: #B8BAFF;
}

.accounts-nav-reset:hover {
    background: rgba(184, 186, 255, 0.18);
    border-color: #B8BAFF;
    color: #ffffff;
}

.accounts-nav-search-form {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 260px;
    max-width: 440px;
}

.accounts-nav-search-input {
    flex: 1;
    background: rgba(12, 12, 22, 0.75) !important;
    border: 1px solid rgba(201, 232, 255, 0.18) !important;
    border-radius: 10px !important;
    padding: 9px 14px !important;
    color: #ffffff !important;
    font-size: 0.88rem !important;
    font-family: 'DM Sans', sans-serif !important;
    transition: all 0.2s ease !important;
}

.accounts-nav-search-input:focus {
    outline: none !important;
    border-color: #6666FF !important;
    box-shadow: 0 0 14px rgba(102, 102, 255, 0.35) !important;
}

.accounts-nav-search-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 16px;
    border-radius: 10px;
    background: linear-gradient(135deg, #6666FF 0%, #5050df 100%);
    border: 1px solid rgba(201, 232, 255, 0.4);
    color: #ffffff;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.88rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.accounts-nav-search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(102, 102, 255, 0.4);
    filter: brightness(1.1);
}

.accounts-nav-search-btn.is-active {
    box-shadow: 0 0 16px rgba(102, 102, 255, 0.5);
    border-color: #B8BAFF;
}

.edit-cell-input {
    background: rgba(12, 12, 22, 0.85);
    border: 1px solid rgba(184, 186, 255, 0.3);
    border-radius: 6px;
    padding: 6px 10px;
    color: #ffffff;
    font-size: 0.88rem;
    width: 100%;
}
.edit-cell-input:focus {
    outline: none;
    border-color: #6666FF;
    box-shadow: 0 0 10px rgba(102, 102, 255, 0.35);
}

.edit-cell-select {
    background: #0C0C16;
    border: 1px solid rgba(184, 186, 255, 0.3);
    border-radius: 6px;
    padding: 6px 10px;
    color: #ffffff;
    font-size: 0.88rem;
}

.btn-save-inline {
    background: linear-gradient(135deg, #6666FF, #5050df);
    border: 1px solid rgba(201, 232, 255, 0.4);
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-save-inline:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 10px rgba(102, 102, 255, 0.4);
}

.btn-cancel-inline {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(201, 232, 255, 0.2);
    color: #C9E8FF;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.82rem;
    text-decoration: none;
    display: inline-block;
}

.btn-edit-action {
    background: rgba(102, 102, 255, 0.16);
    border: 1px solid rgba(184, 186, 255, 0.25);
    color: #C9E8FF;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
}

.btn-edit-action:hover {
    background: #6666FF;
    color: #ffffff;
    box-shadow: 0 0 12px rgba(102, 102, 255, 0.4);
}

.account-role-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-family: 'Space Mono', monospace;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
}

.account-role-badge.admin {
    background: rgba(102, 102, 255, 0.22);
    border: 1px solid rgba(102, 102, 255, 0.45);
    color: #B8BAFF;
}

.account-role-badge.student {
    background: rgba(185, 240, 215, 0.18);
    border: 1px solid rgba(185, 240, 215, 0.35);
    color: #B9F0D7;
}

.alert-message-glass {
    padding: 12px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 0.92rem;
    font-weight: 600;
}
.alert-message-glass.success {
    background: rgba(185, 240, 215, 0.15);
    border: 1px solid rgba(185, 240, 215, 0.35);
    color: #B9F0D7;
}
.alert-message-glass.error {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #fca5a5;
}

/* =====================================================================
   LIGHT MODE: Lavender & Periwinkle Accounts Navbar & Tables
   ===================================================================== */
html[data-theme="light"] .accounts-navbar {
    background: rgba(255, 255, 255, 0.85) !important;
    border: 1px solid rgba(184, 186, 255, 0.45) !important;
    border-top: 1px solid rgba(255, 255, 255, 0.95) !important;
    box-shadow: 0 10px 30px rgba(79, 59, 140, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.85) !important;
}

html[data-theme="light"] .accounts-nav-link {
    background: rgba(184, 186, 255, 0.22) !important;
    border: 1px solid rgba(184, 186, 255, 0.45) !important;
    color: #0C0C16 !important;
}

html[data-theme="light"] .accounts-nav-link:hover {
    background: rgba(102, 102, 255, 0.18) !important;
    border-color: #6666FF !important;
    color: #0C0C16 !important;
    box-shadow: 0 4px 14px rgba(102, 102, 255, 0.22) !important;
}

html[data-theme="light"] .accounts-nav-link.is-active {
    background: linear-gradient(135deg, rgba(102, 102, 255, 0.28), rgba(184, 186, 255, 0.42)) !important;
    border-color: #6666FF !important;
    color: #0C0C16 !important;
    box-shadow: 0 0 18px rgba(102, 102, 255, 0.3), inset 0 1px 1px rgba(255, 255, 255, 0.9) !important;
}

html[data-theme="light"] .accounts-nav-reset {
    background: rgba(201, 232, 255, 0.25) !important;
    border: 1px solid rgba(184, 186, 255, 0.45) !important;
    color: #2F2A56 !important;
}

html[data-theme="light"] .accounts-nav-reset:hover {
    background: rgba(184, 186, 255, 0.35) !important;
    border-color: #6666FF !important;
    color: #0C0C16 !important;
}

html[data-theme="light"] .accounts-nav-search-input {
    background: rgba(255, 255, 255, 0.92) !important;
    border: 1px solid rgba(184, 186, 255, 0.5) !important;
    color: #0C0C16 !important;
}

html[data-theme="light"] .accounts-nav-search-input::placeholder {
    color: #555273 !important;
}

html[data-theme="light"] .accounts-nav-search-input:focus {
    border-color: #6666FF !important;
    box-shadow: 0 0 14px rgba(102, 102, 255, 0.3) !important;
}

html[data-theme="light"] .accounts-nav-search-btn {
    background: linear-gradient(135deg, #6666FF 0%, #5050df 100%) !important;
    border: 1px solid rgba(201, 232, 255, 0.6) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(102, 102, 255, 0.3) !important;
}

html[data-theme="light"] .container {
    background: rgba(255, 255, 255, 0.85) !important;
    border: 1px solid rgba(184, 186, 255, 0.45) !important;
    border-top: 1px solid rgba(255, 255, 255, 0.95) !important;
    box-shadow: 0 16px 36px rgba(79, 59, 140, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.85) !important;
}

html[data-theme="light"] .container h2 {
    color: #0C0C16 !important;
    text-shadow: none !important;
}

html[data-theme="light"] .container p {
    color: #37335C !important;
}

html[data-theme="light"] .container table th {
    background: rgba(240, 237, 252, 0.85) !important;
    color: #4F4974 !important;
    border-bottom: 2px solid rgba(184, 186, 255, 0.42) !important;
}

html[data-theme="light"] .container table td {
    background: rgba(255, 255, 255, 0.82) !important;
    color: #0C0C16 !important;
    border-top: 1px solid rgba(184, 186, 255, 0.28) !important;
    border-bottom: 1px solid rgba(184, 186, 255, 0.28) !important;
}

html[data-theme="light"] .container table tr td:first-child {
    border-left: 1px solid rgba(184, 186, 255, 0.28) !important;
}

html[data-theme="light"] .container table tr td:last-child {
    border-right: 1px solid rgba(184, 186, 255, 0.28) !important;
}

html[data-theme="light"] .container table tr:hover td {
    background: rgba(235, 233, 255, 0.95) !important;
}

html[data-theme="light"] .edit-cell-input {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 1px solid rgba(184, 186, 255, 0.6) !important;
    color: #0C0C16 !important;
}

html[data-theme="light"] .edit-cell-input:focus {
    border-color: #6666FF !important;
    box-shadow: 0 0 10px rgba(102, 102, 255, 0.35) !important;
}

html[data-theme="light"] .edit-cell-select {
    background: rgba(255, 255, 255, 0.95) !important;
    border: 1px solid rgba(184, 186, 255, 0.6) !important;
    color: #0C0C16 !important;
}

html[data-theme="light"] .btn-cancel-inline {
    background: rgba(184, 186, 255, 0.25) !important;
    border: 1px solid rgba(184, 186, 255, 0.5) !important;
    color: #0C0C16 !important;
}

html[data-theme="light"] .btn-edit-action {
    background: rgba(102, 102, 255, 0.12) !important;
    border-color: rgba(184, 186, 255, 0.45) !important;
    color: #0C0C16 !important;
}

html[data-theme="light"] .btn-edit-action:hover {
    background: #6666FF !important;
    color: #ffffff !important;
}

html[data-theme="light"] .account-role-badge.admin {
    background: rgba(102, 102, 255, 0.18) !important;
    border: 1px solid #6666FF !important;
    color: #23236e !important;
}

html[data-theme="light"] .account-role-badge.student {
    background: rgba(185, 240, 215, 0.45) !important;
    border: 1px solid #7cd6b0 !important;
    color: #0E4E30 !important;
}

html[data-theme="light"] .alert-message-glass.success {
    background: rgba(185, 240, 215, 0.35) !important;
    border: 1px solid #7cd6b0 !important;
    color: #0E4E30 !important;
}

html[data-theme="light"] .alert-message-glass.error {
    background: rgba(254, 226, 226, 0.9) !important;
    border: 1px solid rgba(239, 68, 68, 0.5) !important;
    color: #991b1b !important;
}
</style>

<div class="container">

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 14px;">
        <div>
            <h2>Manage Accounts</h2>
            <p style="color: #C9E8FF; opacity: 0.85; margin-bottom: 0;">View, search, edit, and moderate the registered RE:CLAIM community directory.</p>
        </div>
        <a href="dashboard.php" class="back-link admin-back-btn" style="margin-top: 0;">
            &larr; Return to Dashboard
        </a>
    </div>

    <?php if (!empty($update_msg)) { ?>
        <div class="alert-message-glass <?php echo $update_type; ?>">
            <?php echo $update_msg; ?>
        </div>
    <?php } ?>

    <!-- Accounts navigation and search -->
    <nav class="accounts-navbar">
        <div class="accounts-nav-left">
            <a href="manage_users.php" class="accounts-nav-link accounts-nav-reset">
                <span>↺</span> Reset
            </a>
        </div>

        <!-- 2. Search Control -->
        <form method="GET" action="manage_users.php" class="accounts-nav-search-form">
            <?php if ($current_action === 'edit') { ?>
                <input type="hidden" name="action" value="edit">
            <?php } ?>
            <input
                type="text"
                name="search"
                class="accounts-nav-search-input"
                placeholder="Search by name, email, or student ID..."
                value="<?php echo htmlspecialchars($search); ?>"
            >
            <button type="submit" class="accounts-nav-search-btn <?php echo !empty($search) ? 'is-active' : ''; ?>">
                <span>🔍</span> Search
            </button>
        </form>
    </nav>

    <!-- Users Table -->
    <div style="overflow-x: auto;">
        <table border="0" cellpadding="10" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Student ID</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th>Registration Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                    <?php while ($user = mysqli_fetch_assoc($result)) { 
                        $is_editing_this_row = false;
                    ?>
                        <tr>
                            <?php if ($is_editing_this_row) { ?>
                                <form method="POST" action="manage_users.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $user["user_id"]; ?>">
                                    <input type="hidden" name="update_user" value="1">

                                    <td>#<?php echo $user["user_id"]; ?></td>
                                    <td>
                                        <input type="text" name="name" class="edit-cell-input" value="<?php echo htmlspecialchars($user["name"]); ?>" required>
                                    </td>
                                    <td>
                                        <input type="email" name="email" class="edit-cell-input" value="<?php echo htmlspecialchars($user["email"]); ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="student_id" class="edit-cell-input" value="<?php echo htmlspecialchars($user["student_id"]); ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="department" class="edit-cell-input" value="<?php echo htmlspecialchars($user["department"]); ?>">
                                    </td>
                                    <td>
                                        <select name="role" class="edit-cell-select">
                                            <option value="student" <?php echo ($user["role"] === "student") ? "selected" : ""; ?>>Student</option>
                                            <option value="admin" <?php echo ($user["role"] === "admin") ? "selected" : ""; ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td><?php echo htmlspecialchars(date("M d, Y", strtotime($user["created_at"]))); ?></td>
                                    <td style="white-space: nowrap;">
                                        <button type="submit" class="btn-save-inline">Save</button>
                                        <a href="manage_users.php?action=edit<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="btn-cancel-inline">Cancel</a>
                                    </td>
                                </form>
                            <?php } else { ?>
                                <td>#<?php echo $user["user_id"]; ?></td>
                                <td><strong><?php echo htmlspecialchars($user["name"]); ?></strong></td>
                                <td><?php echo htmlspecialchars($user["email"]); ?></td>
                                <td><?php echo htmlspecialchars($user["student_id"] ?? "N/A"); ?></td>
                                <td><?php echo htmlspecialchars($user["department"] ?? "General"); ?></td>
                                <td>
                                    <span class="account-role-badge <?php echo htmlspecialchars($user["role"]); ?>">
                                        <?php echo htmlspecialchars(ucfirst($user["role"])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(date("M d, Y", strtotime($user["created_at"]))); ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--catchit-text-muted, #B8BAFF); padding: 24px;">No user accounts found matching your query.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</div>

<?php include "../includes/footer.php"; ?>

<?php
if (isset($stmt)) {
    mysqli_stmt_close($stmt);
}
?>
