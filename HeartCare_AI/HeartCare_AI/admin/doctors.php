<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$db = getDB();
$error = '';
$success = '';

// 1. ADD DOCTOR HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_doctor') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $specialty = sanitize($_POST['specialty'] ?? 'Cardiologist');
    $phone = sanitize($_POST['phone'] ?? '');
    $available_days = sanitize($_POST['available_days'] ?? 'Monday - Friday');
    $available_hours = sanitize($_POST['available_hours'] ?? '09:00 - 17:00');

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields (Name, Email, Password).";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "A user or doctor with this email already exists.";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $role = 'doctor';
            $insert_stmt = $db->prepare("INSERT INTO users (name, email, password, role, specialty, phone, available_days, available_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("ssssssss", $name, $email, $hashed, $role, $specialty, $phone, $available_days, $available_hours);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success_msg'] = "Dr. $name has been successfully added to the doctor list!";
                header("Location: doctors.php");
                exit();
            } else {
                $error = "Failed to add doctor: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}

// 2. EDIT DOCTOR HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_doctor') {
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $specialty = sanitize($_POST['specialty'] ?? 'Cardiologist');
    $phone = sanitize($_POST['phone'] ?? '');
    $available_days = sanitize($_POST['available_days'] ?? 'Monday - Friday');
    $available_hours = sanitize($_POST['available_hours'] ?? '09:00 - 17:00');
    $new_password = $_POST['new_password'] ?? '';

    if ($doctor_id > 0 && !empty($name) && !empty($email)) {
        // Check email uniqueness
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $doctor_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "This email address is already in use by another user.";
        } else {
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters.";
                } else {
                    $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                    $update_stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password = ?, specialty = ?, phone = ?, available_days = ?, available_hours = ? WHERE id = ? AND role = 'doctor'");
                    $update_stmt->bind_param("sssssssi", $name, $email, $hashed, $specialty, $phone, $available_days, $available_hours, $doctor_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    $_SESSION['success_msg'] = "Doctor information and password updated successfully!";
                    header("Location: doctors.php");
                    exit();
                }
            } else {
                $update_stmt = $db->prepare("UPDATE users SET name = ?, email = ?, specialty = ?, phone = ?, available_days = ?, available_hours = ? WHERE id = ? AND role = 'doctor'");
                $update_stmt->bind_param("ssssssi", $name, $email, $specialty, $phone, $available_days, $available_hours, $doctor_id);
                $update_stmt->execute();
                $update_stmt->close();
                $_SESSION['success_msg'] = "Doctor details updated successfully!";
                header("Location: doctors.php");
                exit();
            }
        }
        $stmt->close();
    } else {
        $error = "Please provide valid doctor details.";
    }
}

// 3. DELETE DOCTOR HANDLER
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($del_id > 0) {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'doctor'");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Doctor has been removed from the platform.";
        } else {
            $_SESSION['error_msg'] = "Failed to remove doctor: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: doctors.php");
    exit();
}

// Fetch all doctors with appointment counts
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = u.id) as total_appointments,
          (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = u.id AND a.status IN ('pending', '')) as pending_appointments
          FROM users u 
          WHERE u.role = 'doctor' 
          ORDER BY u.id DESC";
$doctors = $db->query($query);
$total_doctors_count = $doctors ? $doctors->num_rows : 0;

require_once __DIR__ . '/../partials/header.php';
?>

<div class="row my-3">
    <!-- Header Section -->
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
                <h2 class="fw-bold text-primary mb-1">
                    <i class="fa-solid fa-user-doctor me-2"></i>Manage Doctors
                </h2>
                <p class="text-muted mb-0">Add new physicians, update specialties, set availability, and manage existing cardiologists.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <button type="button" class="btn btn-primary btn-lg fw-bold shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                    <i class="fa-solid fa-plus me-2"></i>Add New Doctor
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-primary border-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted fw-semibold">Total Registered Doctors</span>
                    <h3 class="fw-bold text-primary mb-0"><?php echo $total_doctors_count; ?></h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3">
                    <i class="fa-solid fa-stethoscope fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-success border-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted fw-semibold">Publicly Available</span>
                    <h3 class="fw-bold text-success mb-0"><?php echo $total_doctors_count; ?> Active</h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3">
                    <i class="fa-solid fa-circle-check fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-info border-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted fw-semibold">Public Page Link</span>
                    <div class="mt-1">
                        <a href="<?php echo SITE_URL; ?>/doctors.php" target="_blank" class="btn btn-sm btn-outline-info fw-bold">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View Doctor List
                        </a>
                    </div>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3">
                    <i class="fa-solid fa-globe fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctors Table Card -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i>Doctor Directory</h5>
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill"><?php echo $total_doctors_count; ?> Doctors Listed</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Doctor Profile</th>
                                <th>Specialty</th>
                                <th>Contact Information</th>
                                <th>Available Schedule</th>
                                <th class="text-center">Appointments</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($doctors && $doctors->num_rows > 0): ?>
                                <?php while ($doc = $doctors->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; font-size: 20px;">
                                                    <i class="fa-solid fa-user-doctor"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block">Dr. <?php echo htmlspecialchars($doc['name']); ?></strong>
                                                    <small class="text-muted">ID: #<?php echo $doc['id']; ?> &bull; Joined: <?php echo date('M Y', strtotime($doc['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info border border-info px-3 py-1 rounded-pill">
                                                <?php echo htmlspecialchars($doc['specialty'] ?? 'Cardiologist'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div><i class="fa-solid fa-envelope text-primary me-2"></i><?php echo htmlspecialchars($doc['email']); ?></div>
                                            <?php if (!empty($doc['phone'])): ?>
                                                <small class="text-muted"><i class="fa-solid fa-phone text-success me-2"></i><?php echo htmlspecialchars($doc['phone']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><i class="fa-regular fa-calendar-days text-primary me-1"></i><small class="fw-semibold"><?php echo htmlspecialchars($doc['available_days'] ?? 'Mon - Fri'); ?></small></div>
                                            <div><i class="fa-regular fa-clock text-muted me-1"></i><small class="text-muted"><?php echo htmlspecialchars($doc['available_hours'] ?? '09:00 - 17:00'); ?></small></div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary rounded-pill px-2 py-1"><?php echo (int)$doc['total_appointments']; ?> total</span>
                                            <?php if ((int)$doc['pending_appointments'] > 0): ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1"><?php echo (int)$doc['pending_appointments']; ?> pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1 fw-semibold" data-bs-toggle="modal" data-bs-target="#editDoctorModal<?php echo $doc['id']; ?>" title="Edit Doctor">
                                                <i class="fa-solid fa-pen-to-square me-1"></i>Edit
                                            </button>
                                            <a href="doctors.php?delete_id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-outline-danger fw-semibold" onclick="return confirm('Are you sure you want to remove Dr. <?php echo addslashes($doc['name']); ?> from the system?');" title="Remove Doctor">
                                                <i class="fa-solid fa-trash-can me-1"></i>Delete
                                            </a>
                                        </td>
                                    </tr>

                                    <!-- EDIT DOCTOR MODAL -->
                                    <div class="modal fade" id="editDoctorModal<?php echo $doc['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                <form action="doctors.php" method="POST">
                                                    <input type="hidden" name="action" value="edit_doctor">
                                                    <input type="hidden" name="doctor_id" value="<?php echo $doc['id']; ?>">

                                                    <div class="modal-header bg-primary text-white p-4">
                                                        <h5 class="modal-title fw-bold">
                                                            <i class="fa-solid fa-user-pen me-2"></i>Edit Doctor: Dr. <?php echo htmlspecialchars($doc['name']); ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body p-4">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Doctor Full Name <span class="text-danger">*</span></label>
                                                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($doc['name']); ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($doc['email']); ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Medical Specialty</label>
                                                                <input type="text" name="specialty" class="form-control" value="<?php echo htmlspecialchars($doc['specialty'] ?? 'Cardiologist'); ?>" placeholder="e.g. Senior Cardiologist, Heart Specialist">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Phone Number</label>
                                                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($doc['phone'] ?? ''); ?>" placeholder="01XXXXXXXXX">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Available Days</label>
                                                                <input type="text" name="available_days" class="form-control" value="<?php echo htmlspecialchars($doc['available_days'] ?? 'Monday - Friday'); ?>" placeholder="e.g. Monday, Wednesday, Friday">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold">Available Hours</label>
                                                                <input type="text" name="available_hours" class="form-control" value="<?php echo htmlspecialchars($doc['available_hours'] ?? '09:00 - 17:00'); ?>" placeholder="e.g. 09:00 AM - 04:00 PM">
                                                            </div>
                                                            <div class="col-12">
                                                                <div class="p-3 bg-light rounded-3 border">
                                                                    <label class="form-label fw-bold text-secondary mb-1">Reset Password (Optional)</label>
                                                                    <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep existing password">
                                                                    <small class="text-muted">Only fill this if you wish to set a new password for this doctor.</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer bg-light p-3">
                                                        <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary fw-bold px-4">
                                                            <i class="fa-solid fa-floppy-disk me-1"></i>Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-user-doctor fa-3x text-secondary mb-3 d-block"></i>
                                        <h5>No Doctors Found</h5>
                                        <p class="mb-3">Click the button below to add the first specialist doctor to the platform.</p>
                                        <button type="button" class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                                            <i class="fa-solid fa-plus me-1"></i>Add Doctor Now
                                        </button>
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

<!-- ADD DOCTOR MODAL -->
<div class="modal fade" id="addDoctorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="doctors.php" method="POST">
                <input type="hidden" name="action" value="add_doctor">

                <div class="modal-header bg-primary text-white p-4">
                    <div>
                        <h5 class="modal-title fw-bold mb-1"><i class="fa-solid fa-user-plus me-2"></i>Add New Doctor</h5>
                        <p class="mb-0 text-white-50 small">Create a specialist doctor account. It will immediately appear on the website and booking forms.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Doctor Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">Dr.</span>
                                <input type="text" name="name" class="form-control" placeholder="Sarah Khan" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="doctor@heartcare.local" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Specialty / Title</label>
                            <input type="text" name="specialty" class="form-control" placeholder="e.g. Cardiologist, Cardiac Surgeon" value="Cardiologist">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="018XXXXXXXX">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Consultation Days</label>
                            <input type="text" name="available_days" class="form-control" value="Monday, Wednesday, Friday" placeholder="e.g. Monday - Thursday">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Consultation Hours</label>
                            <input type="text" name="available_hours" class="form-control" value="09:00 AM - 05:00 PM" placeholder="e.g. 10:00 AM - 03:00 PM">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="fa-solid fa-user-plus me-1"></i>Add Doctor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
