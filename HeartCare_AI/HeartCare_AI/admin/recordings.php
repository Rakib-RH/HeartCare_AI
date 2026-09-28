<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$db = getDB();

// Delete recording handler
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($del_id > 0) {
        $stmt = $db->prepare("DELETE FROM recordings WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Heart recording record deleted successfully.";
        } else {
            $_SESSION['error_msg'] = "Failed to delete recording: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: recordings.php");
    exit();
}

// Fetch all recordings with patient & doctor info
$query = "SELECT r.*, p.name as patient_name, p.email as patient_email, d.name as doctor_name 
          FROM recordings r 
          LEFT JOIN users p ON r.patient_id = p.id 
          LEFT JOIN users d ON r.reviewed_by = d.id 
          ORDER BY r.id DESC";
$recordings = $db->query($query);
$tot_count = $recordings ? $recordings->num_rows : 0;

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
                <h2 class="fw-bold text-primary mb-1"><i class="fa-solid fa-file-waveform me-2"></i>Heart Sound Recordings & AI Analysis</h2>
                <p class="text-muted mb-0">Review all uploaded stethoscope audio records, AI classification metrics, and doctor prescriptions.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill"><?php echo $tot_count; ?> Total Uploads</span>
            </div>
        </div>
    </div>

    <!-- Recordings Table -->
    <div class="col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-ul me-2 text-primary"></i>All Recorded Patient Audios</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Patient</th>
                                <th>Audio Reference</th>
                                <th>AI Diagnosis Result</th>
                                <th>Confidence</th>
                                <th>Clinical Review / Advice</th>
                                <th>Uploaded Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recordings && $recordings->num_rows > 0): ?>
                                <?php while ($rec = $recordings->fetch_assoc()): ?>
                                    <?php
                                        $file_display = $rec['original_name'] ?? $rec['filename'] ?? 'Audio File';
                                    ?>
                                    <tr>
                                        <td class="ps-4">
                                            <strong class="text-dark d-block"><?php echo htmlspecialchars($rec['patient_name'] ?? 'Patient'); ?></strong>
                                            <small class="text-muted"><?php echo htmlspecialchars($rec['patient_email'] ?? ''); ?></small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fa-solid fa-file-audio text-primary fa-lg me-2"></i>
                                                <span class="text-dark fw-semibold text-truncate" style="max-width: 140px;" title="<?php echo htmlspecialchars($file_display); ?>">
                                                    <?php echo htmlspecialchars(basename($file_display)); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-3 py-2 rounded-pill">
                                                <i class="fa-solid fa-heart-pulse me-1"></i><?php echo htmlspecialchars($rec['ai_result'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?php echo htmlspecialchars($rec['ai_confidence'] ?? '0'); ?>%</strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($rec['doctor_advice'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success fw-semibold" data-bs-toggle="modal" data-bs-target="#adviceModal<?php echo $rec['id']; ?>">
                                                    <i class="fa-solid fa-notes-medical me-1"></i>View Advice
                                                </button>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border py-2 px-2"><i class="fa-solid fa-clock me-1"></i>Awaiting Review</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo date('d M Y, h:i A', strtotime($rec['created_at'])); ?></small></td>
                                        <td class="text-end pe-4">
                                            <a href="recordings.php?delete_id=<?php echo $rec['id']; ?>" class="btn btn-sm btn-outline-danger fw-semibold" onclick="return confirm('Are you sure you want to delete this sound recording?');" title="Delete Recording">
                                                <i class="fa-solid fa-trash-can me-1"></i>Delete
                                            </a>
                                        </td>
                                    </tr>

                                    <!-- VIEW ADVICE MODAL -->
                                    <?php if (!empty($rec['doctor_advice'])): ?>
                                        <div class="modal fade" id="adviceModal<?php echo $rec['id']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                                    <div class="modal-header bg-success text-white p-4">
                                                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-doctor me-2"></i>Doctor Advice & Feedback</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <label class="text-muted fw-bold d-block small">PATIENT NAME</label>
                                                            <div class="fw-bold fs-6 text-dark"><?php echo htmlspecialchars($rec['patient_name'] ?? 'Patient'); ?></div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="text-muted fw-bold d-block small">AI DIAGNOSIS</label>
                                                            <span class="badge bg-primary text-white px-3 py-1"><?php echo htmlspecialchars($rec['ai_result']); ?> (<?php echo htmlspecialchars($rec['ai_confidence']); ?>%)</span>
                                                        </div>
                                                        <?php if (!empty($rec['doctor_name'])): ?>
                                                            <div class="mb-3">
                                                                <label class="text-muted fw-bold d-block small">REVIEWED BY</label>
                                                                <div class="text-primary fw-semibold">Dr. <?php echo htmlspecialchars($rec['doctor_name']); ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="p-3 bg-light rounded-3 border">
                                                            <label class="text-success fw-bold d-block mb-1"><i class="fa-solid fa-prescription me-1"></i>Doctor's Prescription / Advice:</label>
                                                            <p class="mb-0 text-dark" style="white-space: pre-wrap;"><?php echo htmlspecialchars($rec['doctor_advice']); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light p-3">
                                                        <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-file-audio fa-3x text-secondary mb-3 d-block"></i>
                                        <h5>No Recordings Uploaded Yet</h5>
                                        <p>Patient heart sound recordings will appear here once submitted.</p>
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
