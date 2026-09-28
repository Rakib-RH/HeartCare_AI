<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header("Location: " . SITE_URL . "/" . $_SESSION['user_role'] . "/dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $phone    = sanitize($_POST['phone'] ?? '');
    $role     = sanitize($_POST['role'] ?? 'patient');

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = "দয়া করে সকল আবশ্যিক তথ্য পূরণ করুন।";
    } elseif ($password !== $confirm) {
        $error = "পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলছে না।";
    } elseif (strlen($password) < 6) {
        $error = "পাসওয়ার্ড অন্তত ৬ অক্ষরের হতে হবে।";
    } else {
        $db = getDB();

        try {
            // ইমেইল চেক
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "এই ইমেইল দিয়ে ইতোমধ্যে একটি অ্যাকাউন্ট খোলা রয়েছে।";
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // phone কলাম আছে কিনা ফালব্যাক চেক
                $checkPhoneCol = $db->query("SHOW COLUMNS FROM `users` LIKE 'phone'");
                
                if ($checkPhoneCol && $checkPhoneCol->num_rows > 0) {
                    $insert_stmt = $db->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
                    $insert_stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $phone);
                } else {
                    $insert_stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                    $insert_stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
                }

                if ($insert_stmt->execute()) {
                    $success = "নিবন্ধন সফল হয়েছে! এখন আপনি লগইন করতে পারেন।";
                } else {
                    $error = "অ্যাকাউন্ট তৈরি করতে সমস্যা হয়েছে: " . $insert_stmt->error;
                }
                $insert_stmt->close();
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow border-0 p-4">
            <h3 class="text-center mb-4 text-primary"><i class="fa-solid fa-user-plus me-2"></i>Create Account</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <a href="login.php" class="alert-link">এখানে ক্লিক করে লগইন করুন।</a>
                </div>
            <?php else: ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address *</label>
                    <input type="email" name="email" class="form-control" required placeholder="john@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="017XXXXXXXX" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Password *</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Register As</label>
                    <select name="role" class="form-select">
                        <option value="patient">Patient</option>
                        <option value="doctor">Doctor</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold mt-2 py-2">Register</button>
            </form>

            <div class="text-center mt-3">
                <small>Already have an account? <a href="login.php">Login here</a></small>
            </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>