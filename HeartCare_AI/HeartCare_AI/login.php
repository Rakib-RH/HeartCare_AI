<?php
require_once __DIR__ . '/config/config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_password_hash = $row['password'];
            if (empty($user_password_hash) && isset($row['password_hash'])) {
                $user_password_hash = $row['password_hash'];
            }
            if (password_verify($password, $user_password_hash)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['user_role'] = $row['role'];
                if ($row['role'] === 'admin') header("Location: admin/dashboard.php");
                elseif ($row['role'] === 'doctor') header("Location: doctor/dashboard.php");
                else header("Location: patient/dashboard.php");
                exit();
            } else { $error = "Invalid email or password."; }
        } else { $error = "User not found."; }
    } else { $error = "Please fill in all fields."; }
}
require_once __DIR__ . '/partials/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow border-0 p-4">
            <h3 class="text-center mb-4 text-primary"><i class="fa-solid fa-lock me-2"></i>Login</h3>
            <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <form method="POST">
                <div class="mb-3"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" required placeholder="admin@heartcare.local"></div>
                <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required placeholder="••••••••"></div>
                <button type="submit" class="btn btn-primary w-100 fw-bold">Login</button>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">Default password for demo accounts: <code>password</code></small>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
