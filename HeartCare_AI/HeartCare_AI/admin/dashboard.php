<?php
require_once __DIR__ . '/../config/config.php';
require_login('admin');

$db = getDB();

// Aggregated Statistics
$tot_users = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0;
$tot_doctors = $db->query("SELECT COUNT(*) as c FROM users WHERE role='doctor'")->fetch_assoc()['c'] ?? 0;
$tot_patients = $db->query("SELECT COUNT(*) as c FROM users WHERE role='patient'")->fetch_assoc()['c'] ?? 0;
$tot_recordings = $db->query("SELECT COUNT(*) as c FROM recordings")->fetch_assoc()['c'] ?? 0;
$tot_appts = $db->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'] ?? 0;
$pending_appts = $db->query("SELECT COUNT(*) as c FROM appointments WHERE status IN ('pending', '')")->fetch_assoc()['c'] ?? 0;
$reviewed_recordings = $db->query("SELECT COUNT(*) as c FROM recordings WHERE status='reviewed' OR (doctor_advice IS NOT NULL AND doctor_advice != '')")->fetch_assoc()['c'] ?? 0;

// Recent Appointments
$recent_appts = $db->query("SELECT a.*, p.name as patient_name, d.name as doctor_name 
                             FROM appointments a 
                             LEFT JOIN users p ON a.patient_id = p.id 
                             LEFT JOIN users d ON a.doctor_id = d.id 
                             ORDER BY a.id DESC LIMIT 5");

// Recent Recordings
$recent_recs = $db->query("SELECT r.*, u.name as patient_name 
                           FROM recordings r 
                           LEFT JOIN users u ON r.patient_id = u.id 
                           ORDER BY r.id DESC LIMIT 5");

require_once __DIR__ . '/../partials/header.php';
?>

<!-- Welcome Banner -->
<div class="row my-3">
    <div class="col-12 mb-4">
        <div class="p-4 bg-primary text-white rounded-4 shadow-sm d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold mb-1"><i class="fa-solid fa-gauge-high me-2"></i>Admin Control Center</h2>
                <p class="mb-0 text-white-50">Manage doctors, patients, appointments, heart sound recordings, and platform settings.</p>
            </div>
            <div class="mt-3 mt-md-0 d-flex gap-2">
                <a href="doctors.php" class="btn btn-light text-primary fw-bold shadow-sm rounded-pill">
                    <i class="fa-solid fa-user-doctor me-1"></i>Manage Doctors
                </a>
                <a href="users.php" class="btn btn-outline-light fw-bold rounded-pill">
                    <i class="fa-solid fa-users me-1"></i>Manage Users
                </a>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-primary border-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted fw-semibold d-block mb-1">Total Users</span>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $tot_users; ?></h3>
                    <small class="text-muted"><?php echo $tot_patients; ?> Patients &bull; <?php echo $tot_doctors; ?> Doctors</small>
                </div>
                <div class="bg-primary-subtle text-primary rounded-circle p-3">
                    <i class="fa-solid fa-users-gear fa-2x"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <a href="users.php" class="text-decoration-none fw-semibold small text-primary">Manage all users &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-success border-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted fw-semibold d-block mb-1">Specialist Doctors</span>
                    <h3 class="fw-bold text-success mb-0"><?php echo $tot_doctors; ?></h3>
                    <small class="text-muted">Active cardiologists</small>
                </div>
                <div class="bg-success-subtle text-success rounded-circle p-3">
                    <i class="fa-solid fa-user-doctor fa-2x"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <a href="doctors.php" class="text-decoration-none fw-semibold small text-success">Add & Manage doctors &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-warning border-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted fw-semibold d-block mb-1">Appointments</span>
                    <h3 class="fw-bold text-dark mb-0"><?php echo $tot_appts; ?></h3>
                    <small class="text-warning fw-semibold"><?php echo $pending_appts; ?> Pending requests</small>
                </div>
                <div class="bg-warning-subtle text-warning rounded-circle p-3">
                    <i class="fa-solid fa-calendar-check fa-2x"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <a href="appointments.php" class="text-decoration-none fw-semibold small text-warning">View appointments &rarr;</a>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 border-start border-info border-4 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted fw-semibold d-block mb-1">Heart Recordings</span>
                    <h3 class="fw-bold text-info mb-0"><?php echo $tot_recordings; ?></h3>
                    <small class="text-muted"><?php echo $reviewed_recordings; ?> Reviewed</small>
                </div>
                <div class="bg-info-subtle text-info rounded-circle p-3">
                    <i class="fa-solid fa-heart-pulse fa-2x"></i>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <a href="recordings.php" class="text-decoration-none fw-semibold small text-info">View recordings &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Recent Appointments Section -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white h-100">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-calendar-days me-2 text-primary"></i>Recent Appointment Requests</h5>
                <a href="appointments.php" class="btn btn-sm btn-outline-primary fw-semibold">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Schedule</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_appts && $recent_appts->num_rows > 0): ?>
                                <?php while ($app = $recent_appts->fetch_assoc()): ?>
                                    <?php $status = strtolower(trim($app['status'] ?? 'pending')); ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($app['patient_name'] ?? 'Patient'); ?></strong></td>
                                        <td>Dr. <?php echo htmlspecialchars($app['doctor_name'] ?? 'Doctor'); ?></td>
                                        <td>
                                            <small class="d-block"><?php echo date('d M Y', strtotime($app['appointment_date'])); ?></small>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($app['appointment_time'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if (in_array($status, ['accepted', 'confirmed', 'completed'])): ?>
                                                <span class="badge bg-success px-2 py-1"><?php echo ucfirst($status); ?></span>
                                            <?php elseif ($status === 'cancelled'): ?>
                                                <span class="badge bg-danger px-2 py-1">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-2 py-1">Pending</span>
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

    <!-- Recent Heart Sound Recordings -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white h-100">
            <div class="card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-file-audio me-2 text-primary"></i>Latest AI Sound Tests</h5>
                <a href="recordings.php" class="btn btn-sm btn-outline-primary fw-semibold">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>AI Diagnosis</th>
                                <th>Confidence</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_recs && $recent_recs->num_rows > 0): ?>
                                <?php while ($rec = $recent_recs->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rec['patient_name'] ?? 'Patient'); ?></strong>
                                            <small class="text-muted d-block"><?php echo date('d M Y', strtotime($rec['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1">
                                                <?php echo htmlspecialchars($rec['ai_result'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="text-success"><?php echo htmlspecialchars($rec['ai_confidence'] ?? '0'); ?>%</strong>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No heart recordings uploaded yet.</td>
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
