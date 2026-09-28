<?php
require_once __DIR__ . '/config/config.php';

$db = getDB();

// Fetch all doctors from database
$query = "SELECT id, name, email, phone, specialty, available_days, available_hours, created_at FROM users WHERE role = 'doctor' ORDER BY name ASC";
$result = $db->query($query);

require_once __DIR__ . '/partials/header.php';
?>

<div class="container py-4">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-primary"><i class="fa-solid fa-user-doctor me-2"></i>Our Specialist Doctors</h2>
        <p class="text-muted">Consult with top cardiologists and heart specialists for expert care and online appointments.</p>
    </div>

    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($doctor = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                        <div class="card-body p-4 text-center d-flex flex-column justify-content-between">
                            <div>
                                <div class="mb-3">
                                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; font-size: 32px;">
                                        <i class="fa-solid fa-user-md"></i>
                                    </div>
                                </div>
                                <h4 class="card-title fw-bold text-dark mb-1">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h4>
                                <span class="badge bg-primary-subtle text-primary mb-3 px-3 py-2 rounded-pill">
                                    <?php echo htmlspecialchars($doctor['specialty'] ?? 'Cardiologist / Heart Specialist'); ?>
                                </span>
                                
                                <div class="text-start bg-light p-3 rounded-3 text-muted fs-6 mb-4">
                                    <p class="mb-2">
                                        <i class="fa-solid fa-envelope text-primary me-2"></i> <?php echo htmlspecialchars($doctor['email']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="fa-solid fa-phone text-success me-2"></i> 
                                        <?php echo !empty($doctor['phone']) ? htmlspecialchars($doctor['phone']) : 'Available via Portal'; ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="fa-regular fa-calendar-days text-primary me-2"></i> 
                                        <strong>Days:</strong> <?php echo htmlspecialchars($doctor['available_days'] ?? 'Mon - Fri'); ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fa-regular fa-clock text-primary me-2"></i> 
                                        <strong>Hours:</strong> <?php echo htmlspecialchars($doctor['available_hours'] ?? '09:00 AM - 05:00 PM'); ?>
                                    </p>
                                </div>
                            </div>

                            <a href="patient/book.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm">
                                <i class="fa-solid fa-calendar-check me-2"></i>Book Appointment
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-5 rounded-4 shadow-sm" role="alert">
                    <i class="fa-solid fa-user-doctor fa-3x text-primary mb-3 d-block"></i>
                    <h4>No Specialist Doctors Listed Yet</h4>
                    <p class="text-muted">The system administrator will be adding doctors shortly. Please check back later.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>