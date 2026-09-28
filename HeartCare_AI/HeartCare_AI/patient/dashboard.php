<?php
require_once __DIR__ . '/../config/config.php';
require_login('patient');

$db = getDB();
$patient_id = $_SESSION['user_id'];
$patient_name = $_SESSION['user_name'] ?? 'Patient';

// প্যান্ডিং অ্যাপয়েন্টমেন্ট ডিলিট/ক্যানসেল হ্যান্ডলার
if (isset($_GET['cancel_appt_id'])) {
    $appt_id = (int)$_GET['cancel_appt_id'];
    
    $stmt = $db->prepare("DELETE FROM appointments WHERE id = ? AND patient_id = ? AND (status = 'pending' OR status = '' OR status IS NULL)");
    if ($stmt) {
        $stmt->bind_param("ii", $appt_id, $patient_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Appointment cancelled successfully!";
        } else {
            $_SESSION['error_msg'] = "Failed to cancel appointment.";
        }
        $stmt->close();
    }
    header("Location: dashboard.php");
    exit;
}

// স্ট্যাটিস্টিকস কাউন্ট (prepared statements)
$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM recordings WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$uploaded_sounds = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$total_appts = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as cnt FROM appointments WHERE patient_id = ? AND (status = 'pending' OR status = '' OR status IS NULL)");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$pending_appts = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$stmt->close();

// পেশেন্টের আপলোড করা হার্ট সাউন্ড ও ডাক্তারের পরামর্শ
$stmt = $db->prepare("SELECT * FROM recordings WHERE patient_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$recordings = $stmt->get_result();

// পেশেন্টের অ্যাপয়েন্টমেন্ট ডাটা
$stmt2 = $db->prepare("SELECT a.*, u.name as doctor_name, u.email as doctor_email 
                        FROM appointments a 
                        LEFT JOIN users u ON a.doctor_id = u.id 
                        WHERE a.patient_id = ? 
                        ORDER BY a.id DESC");
$stmt2->bind_param("i", $patient_id);
$stmt2->execute();
$appointments = $stmt2->get_result();

require_once __DIR__ . '/../partials/header.php';
?>

<!-- Patient Dashboard Banner -->
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
                <h2 class="fw-bold mb-1"><i class="fa-solid fa-user-check me-2"></i>Welcome, <?php echo htmlspecialchars($patient_name); ?>!</h2>
                <p class="mb-0 text-white-50">Monitor your cardiac health records, sound analysis, and doctor consultations.</p>
            </div>
            <div>
                <a href="upload.php" class="btn btn-light text-primary fw-bold me-2"><i class="fa-solid fa-upload me-1"></i>Upload Heart Sound</a>
                <a href="book.php" class="btn btn-outline-light fw-bold"><i class="fa-solid fa-calendar-plus me-1"></i>Book Doctor</a>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-warning border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-bold d-block mb-1">Uploaded Heart Sounds</span>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $uploaded_sounds; ?></h3>
                </div>
                <div class="bg-warning-subtle text-warning rounded-circle p-3">
                    <i class="fa-solid fa-file-audio fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-primary border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-bold d-block mb-1">Total Appointments</span>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $total_appts; ?></h3>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3">
                    <i class="fa-solid fa-calendar-check fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-3 bg-white p-3 border-start border-info border-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted fw-bold d-block mb-1">Pending Appointments</span>
                    <h3 class="fw-bold text-info mb-0"><?php echo $pending_appts; ?></h3>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3">
                    <i class="fa-solid fa-hourglass-half fa-2x"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Heart Sound Uploads & Doctor Advice -->
    <div class="col-md-7 mb-4">
        <div class="card shadow-sm border-0 rounded-3 h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-heart-pulse me-2"></i>Recent Heart Sound Uploads</h5>
                <a href="upload.php" class="btn btn-sm btn-outline-primary fw-semibold">Upload New</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Audio Reference</th>
                                <th>AI Result</th>
                                <th>Confidence</th>
                                <th>Date</th>
                                <th class="text-center">Doctor Advice</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recordings && $recordings->num_rows > 0): ?>
                                <?php while ($rec = $recordings->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                                $audio_ref = $rec['original_name'] ?? $rec['file_path'] ?? $rec['filename'] ?? 'Audio Recording';
                                            ?>
                                            <span class="fw-semibold text-dark text-truncate d-inline-block" style="max-width: 140px;" title="<?php echo htmlspecialchars($audio_ref); ?>">
                                                <?php echo htmlspecialchars(basename($audio_ref)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                                <?php echo htmlspecialchars($rec['ai_result'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><strong class="text-success"><?php echo htmlspecialchars($rec['ai_confidence'] ?? '0'); ?>%</strong></td>
                                        <td><small class="text-muted"><?php echo !empty($rec['created_at']) ? date('d M Y', strtotime($rec['created_at'])) : 'N/A'; ?></small></td>
                                        <td class="text-center">
                                            <?php if (!empty($rec['doctor_advice'])): ?>
                                                <button type="button" class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#adviceModal<?php echo $rec['id']; ?>">
                                                    <i class="fa-solid fa-file-medical me-1"></i> View Advice
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border py-2 px-2"><i class="fa-solid fa-clock me-1"></i>Awaiting Doctor</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Doctor Advice View Modal -->
                                    <div class="modal fade" id="adviceModal<?php echo $rec['id']; ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 shadow-lg">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-md me-2"></i>Doctor's Prescription & Advice</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block fw-bold">AI Diagnosis Outcome</small>
                                                        <span class="badge bg-primary text-white fs-6 px-3 py-1 mt-1"><?php echo htmlspecialchars($rec['ai_result'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($rec['ai_confidence'] ?? '0'); ?>%)</span>
                                                    </div>
                                                    <div class="p-3 bg-light rounded border border-success border-2">
                                                        <label class="fw-bold text-success mb-2 d-block"><i class="fa-solid fa-notes-medical me-1"></i>Doctor's Feedback:</label>
                                                        <p class="mb-0 text-dark" style="white-space: pre-wrap;"><?php echo htmlspecialchars($rec['doctor_advice'] ?? ''); ?></p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No heart sound recordings uploaded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Appointments Section -->
    <div class="col-md-5 mb-4">
        <div class="card shadow-sm border-0 rounded-3 h-100">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-user-doctor me-2"></i>My Appointments</h5>
                <a href="book.php" class="btn btn-sm btn-outline-primary fw-semibold">Book New</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($appointments && $appointments->num_rows > 0): ?>
                                <?php while ($app = $appointments->fetch_assoc()): ?>
                                    <?php 
                                        $doc_name = trim($app['doctor_name'] ?? 'General Specialist');
                                        if (!preg_match('/^Dr\./i', $doc_name)) {
                                            $doc_name = 'Dr. ' . $doc_name;
                                        }
                                        
                                        $raw_status = strtolower(trim($app['status'] ?? 'pending'));
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="text-dark d-block"><?php echo htmlspecialchars($doc_name); ?></strong>
                                            <small class="text-muted">Cardiologist</small>
                                        </td>
                                        <td>
                                            <div><i class="fa-regular fa-calendar me-1 text-primary"></i><?php echo !empty($app['appointment_date']) ? date('d M Y', strtotime($app['appointment_date'])) : 'N/A'; ?></div>
                                            <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?php echo !empty($app['appointment_time']) ? date('h:i A', strtotime($app['appointment_time'])) : ''; ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                                if (in_array($raw_status, ['accepted', 'confirmed'])) {
                                                    echo '<span class="badge bg-success px-2 py-1"><i class="fa-solid fa-check me-1"></i>Accepted</span>';
                                                } elseif (in_array($raw_status, ['cancelled', 'rejected'])) {
                                                    echo '<span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-xmark me-1"></i>Cancelled</span>';
                                                } else {
                                                    echo '<span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Pending</span>';
                                                }
                                            ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <?php if (in_array($raw_status, ['pending', '', null])): ?>
                                                <a href="dashboard.php?cancel_appt_id=<?php echo $app['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" 
                                                   onclick="return confirm('Are you sure you want to cancel this pending appointment?');"
                                                   title="Cancel Appointment">
                                                    <i class="fa-solid fa-trash-can"></i> Cancel
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted fs-7">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No appointments booked yet.</td>
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