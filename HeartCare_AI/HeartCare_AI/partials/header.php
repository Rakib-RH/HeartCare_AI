<?php
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HeartCare AI - Smart Cardiac Health</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?php echo SITE_URL; ?>/index.php">
        <i class="fa-solid fa-heart-pulse me-2"></i>HeartCare AI
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/doctors.php">Doctors</a></li>
        <?php if (isLoggedIn()): ?>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle active" href="#" data-bs-toggle="dropdown">Admin Menu</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/users.php">Manage Users</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/doctors.php">Manage Doctors</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/appointments.php">Appointments</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/recordings.php">Heart Recordings</a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings.php">System Settings</a></li>
                    </ul>
                </li>
            <?php elseif ($_SESSION['user_role'] === 'doctor'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/doctor/dashboard.php">Doctor Dashboard</a></li>
            <?php elseif ($_SESSION['user_role'] === 'patient'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/patient/dashboard.php">Patient Portal</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/patient/upload.php">Upload Heart Sound</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/patient/book.php">Book Appointment</a></li>
            <?php endif; ?>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-auto">
        <?php if (isLoggedIn()): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white fw-semibold" href="#" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-user-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo ucfirst($_SESSION['user_role']); ?>)
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php"><i class="fa-solid fa-right-from-bracket me-2 text-danger"></i>Logout</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/login.php"><i class="fa-solid fa-right-to-bracket me-1"></i>Login</a></li>
            <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-2 mt-1" href="<?php echo SITE_URL; ?>/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container py-4">
