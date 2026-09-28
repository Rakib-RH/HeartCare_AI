<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$db = getDB();
$admin_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Update Profile & Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email)) {
        $error = "Name and email are required.";
    } else {
        // Fetch current admin info
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Check if password change requested
        if (!empty($new_pass)) {
            if (empty($current_pass)) {
                $error = "Please enter your current password to set a new password.";
            } elseif (!password_verify($current_pass, $res['password'])) {
                $error = "Current password does not match our records.";
            } elseif ($new_pass !== $confirm_pass) {
                $error = "New password and confirmation password do not match.";
            } elseif (strlen($new_pass) < 6) {
                $error = "New password must be at least 6 characters long.";
            } else {
                $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                $update = $db->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                $update->bind_param("sssi", $name, $email, $hashed, $admin_id);
                $update->execute();
                $update->close();
                $_SESSION['user_name'] = $name;
                $success = "Profile and password updated successfully!";
            }
        } else {
            $update = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $update->bind_param("ssi", $name, $email, $admin_id);
            $update->execute();
            $update->close();
            $_SESSION['user_name'] = $name;
            $success = "Profile details updated successfully!";
        }
    }
}

// Fetch Admin Info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// System info
$upload_dir = __DIR__ . '/../uploads/';
$upload_writable = is_writable($upload_dir) ? 'Writable' : 'Not Writable';
$upload_count = is_dir($upload_dir) ? count(glob($upload_dir . "*.*")) : 0;
$mysql_version = $db->server_info;

require_once __DIR__ . '/../partials/header.php';
?>

<div class="row my-3">
    <!-- Header -->
    <div class="col-12 mb-4">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm border">
            <div>
                <h2 class="fw-bold text-primary mb-1"><i class="fa-solid fa-gear me-2"></i>System Settings & Admin Profile</h2>
                <p class="text-muted mb-0">Manage administrator account credentials and monitor platform environment health.</p>
            </div>
        </div>
    </div>

    <!-- Admin Account Settings -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-user-gear me-2 text-primary"></i>Administrator Profile</h5>
            <form action="settings.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Admin Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($admin_user['name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Admin Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_user['email'] ?? ''); ?>" required>
                </div>

                <hr class="my-4">
                <h6 class="fw-bold text-secondary mb-3"><i class="fa-solid fa-key me-2"></i>Change Administrator Password</h6>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="••••••••">
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" name="update_settings" class="btn btn-primary fw-bold px-4 py-2 rounded-pill">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Update Admin Profile
                </button>
            </form>
        </div>
    </div>

    <!-- System Health & Overview -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-server me-2 text-primary"></i>Server & Platform Health</h5>
            
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <div>
                        <strong class="d-block text-dark">PHP Version</strong>
                        <small class="text-muted">Active runtime</small>
                    </div>
                    <span class="badge bg-primary fs-6"><?php echo phpversion(); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <div>
                        <strong class="d-block text-dark">Database Engine</strong>
                        <small class="text-muted">MySQL / MariaDB</small>
                    </div>
                    <span class="badge bg-success fs-6"><?php echo htmlspecialchars($mysql_version); ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <div>
                        <strong class="d-block text-dark">Upload Directory</strong>
                        <small class="text-muted">Heart sound files store</small>
                    </div>
                    <span class="badge bg-info text-white fs-6"><?php echo $upload_writable; ?> (<?php echo $upload_count; ?> files)</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
                    <div>
                        <strong class="d-block text-dark">Application URL</strong>
                        <small class="text-muted">Configured site domain</small>
                    </div>
                    <code class="text-primary"><?php echo SITE_URL; ?></code>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
