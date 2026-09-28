<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/partials/header.php';

// সেশন চেক করার সেফ লজিক
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userRole   = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'patient';

// প্রজেক্টের মূল পাথ সেটআপ
$base_path = '/heartcare_ai';
?>

<!-- Hero Section -->
<div class="row justify-content-center my-4">
    <div class="col-lg-10">
        <div class="card shadow-lg border-0 rounded-4 overflow-hidden text-center p-5 bg-white">
            <div class="mb-3 text-primary">
                <i class="fa-solid fa-heart-pulse fa-4x"></i>
            </div>
            <h1 class="display-4 fw-bold text-primary mb-3">AI Heart Care Platform</h1>
            <p class="lead text-secondary mx-auto mb-4" style="max-width: 700px;">
                AI-powered stethoscope sound analysis & direct clinician review system for early cardiac health monitoring.
            </p>
            <div class="d-flex justify-content-center gap-3">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $base_path; ?>/<?php echo $userRole; ?>/dashboard.php" class="btn btn-primary btn-lg px-4 py-2 fw-bold rounded-pill shadow-sm">
                        <i class="fa-solid fa-gauge-high me-2"></i>Go to Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_path; ?>/login.php" class="btn btn-primary btn-lg px-4 py-2 fw-bold rounded-pill shadow-sm">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Get Started / Login
                    </a>
                    <a href="<?php echo $base_path; ?>/register.php" class="btn btn-outline-primary btn-lg px-4 py-2 fw-bold rounded-pill">
                        <i class="fa-solid fa-user-plus me-2"></i>Register Patient
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Key Features Section -->
<div class="row g-4 my-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 text-center p-4 rounded-3">
            <div class="card-body">
                <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                    <i class="fa-solid fa-file-audio"></i>
                </div>
                <h4 class="fw-bold mb-2">Upload Heart Sound</h4>
                <p class="text-muted">Easily upload stethoscope audio files for instant automated AI detection and processing.</p>
                <a href="<?php echo $base_path; ?>/patient/upload.php" class="btn btn-sm btn-outline-primary fw-bold mt-2">Upload Audio</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 text-center p-4 rounded-3">
            <div class="card-body">
                <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
                <h4 class="fw-bold mb-2">Expert Cardiologists</h4>
                <p class="text-muted">Connect with professional doctors and specialist clinicians for accurate health consultations.</p>
                <a href="<?php echo $base_path; ?>/doctors.php" class="btn btn-sm btn-outline-primary fw-bold mt-2">Find Doctors</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 text-center p-4 rounded-3">
            <div class="card-body">
                <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h4 class="fw-bold mb-2">Book Appointment</h4>
                <p class="text-muted">Schedule seamless online appointments with verified cardiologists in a few clicks.</p>
                <a href="<?php echo $base_path; ?>/patient/book.php" class="btn btn-sm btn-outline-primary fw-bold mt-2">Book Now</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>