<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$db = getDB();
$error = '';
$current_admin_id = $_SESSION['user_id'];

// 1. ADD USER HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'patient');
    $phone = sanitize($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "A user with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $insert_stmt = $db->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssss", $name, $email, $hashed, $role, $phone);
            if ($insert_stmt->execute()) {
                $_SESSION['success_msg'] = "User $name has been created successfully as " . ucfirst($role) . "!";
                header("Location: users.php");
                exit();
            } else {
                $error = "Failed to create user: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}

// 2. EDIT USER HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $role = sanitize($_POST['role'] ?? 'patient');
    $phone = sanitize($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if ($user_id > 0 && !empty($name) && !empty($email)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email address is already in use by another account.";
        } else {
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters.";
                } else {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $update_stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, role = ?, phone = ? WHERE id = ?");
                    $update_stmt->bind_param("sssssi", $name, $email, $hashed, $role, $phone, $user_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    $_SESSION['success_msg'] = "User profile and password updated successfully!";
                    header("Location: users.php");
                    exit();
                }
            } else {
                $update_stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, phone = ? WHERE id = ?");
                $update_stmt->bind_param("ssssi", $name, $email, $role, $phone, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
                $_SESSION['success_msg'] = "User profile updated successfully!";
                header("Location: users.php");
                exit();
            }
        }
        $stmt->close();
    } else {
        $error = "Invalid user information provided.";
    }
}

// 3. DELETE USER HANDLER
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($del_id === $current_admin_id) {
        $_SESSION['error_msg'] = "You cannot delete your own logged-in admin account.";
    } elseif ($del_id > 0) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "User account has been permanently removed.";
        } else {
            $_SESSION['error_msg'] = "Failed to delete user: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: users.php");
    exit();
}

// Filter and search
$role_filter = sanitize($_GET['role'] ?? '');
$search_query = sanitize($_GET['search'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

if (!empty($role_filter) && in_array($role_filter, ['admin', 'doctor', 'patient'])) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($search_query)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%" . $search_query . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sql .= " ORDER BY id DESC";
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();

require_once __DIR__ . '/../partials/header.php';
?>

<div class="row my-3">
    <!-- Header -->
    <div class="col-12 mb-4">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
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
                <h2 class="fw-bold text-primary mb-1"><i class="fa-solid fa-users me-2"></i>Manage System Users</h2>
                <p class="text-muted mb-0">Control all user accounts, assign roles (Admin, Doctor, Patient), and maintain credentials.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-primary btn-lg fw-bold shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus me-2"></i>Create New User
                </button>
            </div>
        </div>
    </div>

    <!-- Filters and Search Bar -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <form action="users.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="role" class="form-select">
                        <option value="">All Roles (Admins, Doctors, Patients)</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                        <option value="doctor" <?php echo $role_filter === 'doctor' ? 'selected' : ''; ?>>Doctors / Cardiologists</option>
                        <option value="patient" <?php echo $role_filter === 'patient' ? 'selected' : ''; ?>>Patients</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100"><i class="fa-solid fa-filter me-1"></i>Filter</button>
                    <?php if (!empty($role_filter) || !empty($search_query)): ?>
                        <a href="users.php" class="btn btn-outline-secondary"><i class="fa-solid fa-xmark"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-address-book me-2 text-primary"></i>User Accounts List</h5>
                <span class="badge bg-secondary rounded-pill px-3 py-2"><?php echo $users ? $users->num_rows : 0; ?> Accounts</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Registered</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($users && $users->num_rows > 0): ?>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-3 <?php echo $user['role'] === 'admin' ? 'bg-danger-subtle text-danger' : ($user['role'] === 'doctor' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success'); ?>" style="width: 40px; height: 40px; font-size: 18px;">
                                                    <i class="fa-solid <?php echo $user['role'] === 'admin' ? 'fa-user-shield' : ($user['role'] === 'doctor' ? 'fa-user-md' : 'fa-user'); ?>"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block"><?php echo htmlspecialchars($user['name']); ?></strong>
                                                    <small class="text-muted">ID: #<?php echo $user['id']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><i class="fa-regular fa-envelope text-muted me-1"></i><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1 rounded-pill"><i class="fa-solid fa-shield me-1"></i>Admin</span>
                                            <?php elseif ($user['role'] === 'doctor'): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-1 rounded-pill"><i class="fa-solid fa-stethoscope me-1"></i>Doctor</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success px-3 py-1 rounded-pill"><i class="fa-solid fa-user me-1"></i>Patient</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<span class="text-muted">N/A</span>'; ?></td>
                                        <td><small class="text-muted"><?php echo date('d M Y', strtotime($user['created_at'])); ?></small></td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>" title="Edit User">
                                                <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                            </button>
                                            <?php if ($user['id'] != $current_admin_id): ?>
                                                <a href="users.php?delete_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger fw-semibold" onclick="return confirm('Are you sure you want to delete account: <?php echo addslashes($user['name']); ?>?');" title="Delete User">
                                                    <i class="fa-solid fa-trash-can me-1"></i>Delete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- EDIT USER MODAL -->
                                    <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                <form action="users.php" method="POST">
                                                    <input type="hidden" name="action" value="edit_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                                                    <div class="modal-header bg-primary text-white p-4">
                                                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Edit User Account</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Phone Number</label>
                                                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold">Account Role <span class="text-danger">*</span></label>
                                                            <select name="role" class="form-select">
                                                                <option value="patient" <?php echo $user['role'] === 'patient' ? 'selected' : ''; ?>>Patient</option>
                                                                <option value="doctor" <?php echo $user['role'] === 'doctor' ? 'selected' : ''; ?>>Doctor</option>
                                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                        <div class="p-3 bg-light rounded-3 border">
                                                            <label class="form-label fw-bold text-secondary mb-1">Change Password (Optional)</label>
                                                            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep existing">
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer bg-light p-3">
                                                        <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fa-solid fa-floppy-disk me-1"></i>Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users fa-3x text-secondary mb-3 d-block"></i>
                                        <h5>No Users Found</h5>
                                        <p>No user accounts matched your search criteria.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CREATE USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="add_user">

                <div class="modal-header bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Create New Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="patient">Patient</option>
                            <option value="doctor">Doctor</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="017XXXXXXXX">
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fa-solid fa-user-plus me-1"></i>Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
