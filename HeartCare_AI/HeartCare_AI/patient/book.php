<?php
require_once __DIR__ . '/../config/config.php';
require_login('patient');

$db = getDB();
$patient_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = (int)($_POST['doctor_id'] ?? 0);
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if ($doctor_id > 0 && !empty($appointment_date) && !empty($appointment_time)) {
        $stmt = $db->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param("iisss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Appointment requested successfully!";
                echo "<script>window.location.href='dashboard.php';</script>";
                exit;
            } else {
                $error = "Failed to book appointment: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database prepare error: " . $db->error;
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

$selected_doctor_id = (int)($_GET['doctor_id'] ?? 0);
$doctors = $db->query("SELECT id, name, specialty FROM users WHERE role = 'doctor' ORDER BY name ASC");

require_once __DIR__ . '/../partials/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-lg border-0 rounded-4 p-4">
            <div class="text-center mb-4">
                <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex p-3 mb-2">
                    <i class="fa-solid fa-calendar-plus fa-2x"></i>
                </div>
                <h3 class="fw-bold text-primary mb-1">Book Doctor Appointment</h3>
                <p class="text-muted small">Schedule a consultation session with a specialist heart physician.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="book.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Select Doctor <span class="text-danger">*</span></label>
                    <select name="doctor_id" class="form-select form-select-lg fs-6" required>
                        <option value="" disabled <?php echo empty($selected_doctor_id) ? 'selected' : ''; ?>>-- Choose a Doctor --</option>
                        <?php if ($doctors && $doctors->num_rows > 0): ?>
                            <?php while ($doc = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doc['id']; ?>" <?php echo $selected_doctor_id === (int)$doc['id'] ? 'selected' : ''; ?>>
                                    Dr. <?php echo htmlspecialchars($doc['name']); ?> (<?php echo htmlspecialchars($doc['specialty'] ?? 'Cardiologist'); ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No doctors available</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Appointment Date <span class="text-danger">*</span></label>
                        <input type="date" name="appointment_date" class="form-control form-control-lg fs-6" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Appointment Time <span class="text-danger">*</span></label>
                        <input type="time" name="appointment_time" class="form-control form-control-lg fs-6" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Reason / Health Issues</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Describe your symptoms or reason for visit..."></textarea>
                </div>

                <button type="submit" name="book_appointment" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">
                    <i class="fa-solid fa-circle-check me-2"></i>Confirm Appointment Request
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>