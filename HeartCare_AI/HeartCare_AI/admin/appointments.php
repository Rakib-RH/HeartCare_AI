<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$db = getDB();

// 1. UPDATE STATUS HANDLER
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = (int)$_GET['id'];
    $new_status = sanitize($_GET['action']);

    if (in_array($new_status, ['accepted', 'confirmed', 'completed', 'cancelled'])) {
        $stmt = $db->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $appt_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Appointment #$appt_id marked as " . ucfirst($new_status) . "!";
        } else {
            $_SESSION['error_msg'] = "Failed to update appointment: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: appointments.php");
    exit();
}

// 2. DELETE APPOINTMENT HANDLER
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($del_id > 0) {
        $stmt = $db->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Appointment record removed successfully.";
        } else {
            $_SESSION['error_msg'] = "Failed to remove appointment: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: appointments.php");
    exit();
}

// Filter by status
$status_filter = sanitize($_GET['status'] ?? '');
$sql = "SELECT a.*, p.name as patient_name, p.email as patient_email, p.phone as patient_phone,
               d.name as doctor_name, d.specialty as doctor_specialty 
        FROM appointments a 
        LEFT JOIN users p ON a.patient_id = p.id 
        LEFT JOIN users d ON a.doctor_id = d.id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    if ($status_filter === 'pending') {
        $sql .= " AND (a.status = 'pending' OR a.status = '' OR a.status IS NULL)";
    } else {
        $sql .= " AND a.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
}

$sql .= " ORDER BY a.id DESC";
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();

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

        <div class="d-flex flex-wrap justify-content-between align-items-center bg-white p-4 rounded-4 shadow-sm border">
            <div>
                <h2 class="fw-bold text-primary mb-1"><i class="fa-solid fa-calendar-check me-2"></i>Manage Appointments</h2>
                <p class="text-muted mb-0">Monitor, confirm, complete, or cancel consultations between patients and doctors.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="appointments.php" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?> fw-bold rounded-pill">All</a>
                <a href="appointments.php?status=pending" class="btn <?php echo $status_filter === 'pending' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark'; ?> fw-bold rounded-pill">Pending</a>
                <a href="appointments.php?status=accepted" class="btn <?php echo $status_filter === 'accepted' ? 'btn-success' : 'btn-outline-success'; ?> fw-bold rounded-pill">Accepted</a>
                <a href="appointments.php?status=completed" class="btn <?php echo $status_filter === 'completed' ? 'btn-info' : 'btn-outline-info'; ?> fw-bold rounded-pill">Completed</a>
                <a href="appointments.php?status=cancelled" class="btn <?php echo $status_filter === 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?> fw-bold rounded-pill">Cancelled</a>
            </div>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>All Scheduled Consultations</h5>
                <span class="badge bg-secondary rounded-pill px-3 py-2"><?php echo $appointments ? $appointments->num_rows : 0; ?> Records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Patient</th>
                                <th>Assigned Doctor</th>
                                <th>Date & Time</th>
                                <th>Reason / Notes</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments && $appointments->num_rows > 0): ?>
                                <?php while ($app = $appointments->fetch_assoc()): ?>
                                    <?php 
                                        $status = strtolower(trim($app['status'] ?? 'pending')); 
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <strong class="text-dark d-block"><?php echo htmlspecialchars($app['patient_name'] ?? 'Patient'); ?></strong>
                                            <small class="text-muted"><?php echo htmlspecialchars($app['patient_email'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <strong class="text-primary d-block">Dr. <?php echo htmlspecialchars($app['doctor_name'] ?? 'Physician'); ?></strong>
                                            <small class="text-muted"><?php echo htmlspecialchars($app['doctor_specialty'] ?? 'Cardiologist'); ?></small>
                                        </td>
                                        <td>
                                            <div><i class="fa-regular fa-calendar text-primary me-1"></i><?php echo date('d M Y', strtotime($app['appointment_date'])); ?></div>
                                            <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block text-muted" style="max-width: 180px;" title="<?php echo htmlspecialchars($app['reason'] ?? 'None'); ?>">
                                                <?php echo !empty($app['reason']) ? htmlspecialchars($app['reason']) : '<span class="text-muted fst-italic">No symptoms stated</span>'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (in_array($status, ['accepted', 'confirmed'])): ?>
                                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i>Accepted</span>
                                            <?php elseif ($status === 'completed'): ?>
                                                <span class="badge bg-info px-3 py-2 rounded-pill"><i class="fa-solid fa-circle-check me-1"></i>Completed</span>
                                            <?php elseif ($status === 'cancelled'): ?>
                                                <span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-xmark me-1"></i>Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fa-solid fa-clock me-1"></i>Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($status !== 'accepted' && $status !== 'confirmed'): ?>
                                                    <a href="appointments.php?action=accepted&id=<?php echo $app['id']; ?>" class="btn btn-outline-success" title="Accept"><i class="fa-solid fa-check"></i></a>
                                                <?php endif; ?>
                                                <?php if ($status !== 'completed'): ?>
                                                    <a href="appointments.php?action=completed&id=<?php echo $app['id']; ?>" class="btn btn-outline-info" title="Mark Completed"><i class="fa-solid fa-flag-checkered"></i></a>
                                                <?php endif; ?>
                                                <?php if ($status !== 'cancelled'): ?>
                                                    <a href="appointments.php?action=cancelled&id=<?php echo $app['id']; ?>" class="btn btn-outline-warning text-dark" title="Cancel"><i class="fa-solid fa-ban"></i></a>
                                                <?php endif; ?>
                                                <a href="appointments.php?delete_id=<?php echo $app['id']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this appointment record?');" title="Delete"><i class="fa-solid fa-trash-can"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-calendar-xmark fa-3x text-secondary mb-3 d-block"></i>
                                        <h5>No Appointments Found</h5>
                                        <p>No appointment records match the selected filter.</p>
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

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
