<?php
require_once __DIR__ . '/../config/config.php';
require_login('doctor');

$db = getDB();
$doctor_id = $_SESSION['user_id'];
$doctor_name = $_SESSION['user_name'] ?? 'Doctor';

// ১. অ্যাপয়েন্টমেন্ট একসেপ্ট হ্যান্ডলার
if (isset($_GET['accept_appt_id'])) {
    $appt_id = (int)$_GET['accept_appt_id'];
    
    $stmt = $db->prepare("UPDATE appointments SET status = 'accepted' WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $appt_id, $doctor_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Appointment accepted successfully!";
    } else {
        $_SESSION['error_msg'] = "Database Update Error: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: dashboard.php");
    exit;
}

// ২. অ্যাপয়েন্টমেন্ট ক্যানসেল/রিজেক্ট হ্যান্ডলার
if (isset($_GET['cancel_appt_id'])) {
    $appt_id = (int)$_GET['cancel_appt_id'];
    
    $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param("ii", $appt_id, $doctor_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Appointment cancelled!";
    } else {
        $_SESSION['error_msg'] = "Database Update Error: " . $stmt->error;
    }
    $stmt->close();
    
    header("Location: dashboard.php");
    exit;
}

// ৩. পেশেন্ট ডায়াগনোসিস / প্রেসক্রিপশন আপডেট হ্যান্ডলার
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_advice'])) {
    $rec_id = (int)($_POST['recording_id'] ?? 0);
    $advice = trim($_POST['doctor_advice'] ?? '');

    if ($rec_id > 0 && !empty($advice)) {
        $stmt = $db->prepare("UPDATE recordings SET doctor_advice = ?, reviewed_by = ?, status = 'reviewed' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sii", $advice, $doctor_id, $rec_id);
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Advice saved successfully!";
            } else {
                $_SESSION['error_msg'] = "Failed to save advice.";
            }
            $stmt->close();
        }
    } else {
        $_SESSION['error_msg'] = "Please provide valid advice text.";
    }
    header("Location: dashboard.php");
    exit;
}

// স্ট্যাটিস্টিকস কাউন্ট (prepared statements)
$stmt = $db->prepare("SELECT COUNT(DISTINCT patient_id) as cnt FROM appointments WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$total_patients = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = ? AND (status = 'pending' OR status = '' OR status IS NULL)");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$pending_appts = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = ? AND status IN ('accepted', 'confirmed')");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$accepted_appts = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

// ডাক্তার দ্বারা রিভিউ করা রোগীর রেকর্ড কাউন্ট
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM recordings WHERE reviewed_by = ? OR (status = 'reviewed' AND doctor_advice IS NOT NULL AND doctor_advice != '')");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$reviewed_patients = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

// অ্যাপয়েন্টমেন্ট ডাটা
$stmt = $db->prepare("SELECT a.*, u.name as patient_name, u.email as patient_email 
                        FROM appointments a 
                        LEFT JOIN users u ON a.patient_id = u.id 
                        WHERE a.doctor_id = ? 
                        ORDER BY a.id DESC");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result();

// রিসেন্ট হার্ট সাউন্ড রেকর্ডস
$recordings = $db->query("SELECT r.*, u.name as patient_name 
                         FROM recordings r 
                         LEFT JOIN users u ON r.patient_id = u.id 
                         ORDER BY r.id DESC LIMIT 10");

require_once __DIR__ . '/../partials/header.php';
?>

<!-- Doctor Dashboard Banner -->
<div class="row my-3">
    <div class="col-md-12 mb-4">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="p-4 bg-primary text-white rounded-3 shadow-sm d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="fa-solid fa-user-md me-2"></i>Welcome, <?php echo htmlspecialchars($doctor_name); ?>!</h2>
                <p class="mb-0 text-white-50">Manage your patient appointments, review heart sound recordings, and give prescriptions.</p>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-primary border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-bold d-block mb-1">Total Patients</span>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_patients; ?></h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3">
                    <i class="fa-solid fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-warning border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-bold d-block mb-1">Pending Requests</span>
                    <h3 class="fw-bold text-warning mb-0"><?php echo $pending_appts; ?></h3>
                </div>
                <div class="bg-warning-subtle text-warning rounded-circle p-3">
                    <i class="fa-solid fa-hourglass-half fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-success border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-bold d-block mb-1">Accepted Appointments</span>
                    <h3 class="fw-bold text-success mb-0"><?php echo $accepted_appts; ?></h3>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3">
                    <i class="fa-solid fa-calendar-check fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-info border-4 h-100">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-bold d-block mb-1">Patients Reviewed</span>
                    <h3 class="fw-bold text-info mb-0"><?php echo $reviewed_patients; ?></h3>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3">
                    <i class="fa-solid fa-clipboard-check fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointments Management Section -->
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-calendar-days me-2"></i>Patient Appointments</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Email</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments && $appointments->num_rows > 0): ?>
                                <?php while ($app = $appointments->fetch_assoc()): ?>
                                    <?php 
                                        $status = strtolower(trim($app['status'] ?? 'pending'));
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block"><?php echo htmlspecialchars($app['patient_name'] ?? 'Patient'); ?></strong>
                                        </td>
                                        <td><span class="text-muted"><?php echo htmlspecialchars($app['patient_email'] ?? 'N/A'); ?></span></td>
                                        <td>
                                            <div><i class="fa-regular fa-calendar me-1 text-primary"></i><?php echo !empty($app['appointment_date']) ? date('d M Y', strtotime($app['appointment_date'])) : 'N/A'; ?></div>
                                            <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?php echo !empty($app['appointment_time']) ? date('h:i A', strtotime($app['appointment_time'])) : ''; ?></small>
                                        </td>
                                        <td>
                                            <?php if (in_array($status, ['accepted', 'confirmed'])): ?>
                                                <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-check me-1"></i>Accepted</span>
                                            <?php elseif ($status === 'cancelled'): ?>
                                                <span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-xmark me-1"></i>Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if (in_array($status, ['accepted', 'confirmed'])): ?>
                                                <span class="badge bg-success px-3 py-2"><i class="fa-solid fa-check me-1"></i>Accepted</span>
                                            <?php elseif ($status === 'cancelled'): ?>
                                                <span class="badge bg-danger px-3 py-2"><i class="fa-solid fa-xmark me-1"></i>Cancelled</span>
                                            <?php else: ?>
                                                <a href="dashboard.php?accept_appt_id=<?php echo $app['id']; ?>" class="btn btn-sm btn-success fw-bold me-1">
                                                    <i class="fa-solid fa-check me-1"></i> Accept
                                                </a>
                                                <a href="dashboard.php?cancel_appt_id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?');">
                                                    <i class="fa-solid fa-xmark me-1"></i> Cancel
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No appointment requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Heart Sound Review Section -->
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-heart-pulse me-2"></i>Patient Sound Recordings & Prescriptions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Audio Reference</th>
                                <th>AI Diagnosis</th>
                                <th>Confidence</th>
                                <th>Uploaded Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recordings && $recordings->num_rows > 0): ?>
                                <?php while ($rec = $recordings->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong class="text-dark"><?php echo htmlspecialchars($rec['patient_name'] ?? 'Patient'); ?></strong></td>
                                        <td>
                                            <span class="fw-semibold text-dark text-truncate d-inline-block" style="max-width: 150px;">
                                                <?php 
                                                    $audio_ref = $rec['original_name'] ?? $rec['file_path'] ?? $rec['filename'] ?? 'Audio Recording';
                                                    echo htmlspecialchars(basename($audio_ref)); 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                                <?php echo htmlspecialchars($rec['ai_result'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><strong class="text-success"><?php echo htmlspecialchars($rec['ai_confidence'] ?? '0'); ?>%</strong></td>
                                        <td><small class="text-muted"><?php echo !empty($rec['created_at']) ? date('d M Y', strtotime($rec['created_at'])) : 'N/A'; ?></small></td>
                                        <td class="text-end pe-4">
                                            <button type="button" class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#adviceModal<?php echo $rec['id']; ?>">
                                                <i class="fa-solid fa-pen-to-square me-1"></i> <?php echo !empty($rec['doctor_advice']) ? 'Edit Advice' : 'Give Advice'; ?>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Doctor Advice Modal -->
                                    <div class="modal fade" id="adviceModal<?php echo $rec['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg">
                                                <form action="dashboard.php" method="POST">
                                                    <div class="modal-header bg-primary text-white">
                                                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-md me-2"></i>Provide Medical Advice</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <input type="hidden" name="recording_id" value="<?php echo $rec['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="fw-bold text-muted d-block">Patient Name</label>
                                                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($rec['patient_name'] ?? 'Patient'); ?></div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="fw-bold text-muted d-block">AI Diagnosis Result</label>
                                                            <span class="badge bg-primary text-white px-3 py-1 mt-1"><?php echo htmlspecialchars($rec['ai_result'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($rec['ai_confidence'] ?? '0'); ?>%)</span>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold text-primary">Doctor's Feedback / Prescription:</label>
                                                            <textarea name="doctor_advice" class="form-control" rows="4" placeholder="Write prescription or health instructions here..." required><?php echo htmlspecialchars($rec['doctor_advice'] ?? ''); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="save_advice" class="btn btn-success fw-bold"><i class="fa-solid fa-paper-plane me-1"></i> Save Advice</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No heart sound recordings available.</td>
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