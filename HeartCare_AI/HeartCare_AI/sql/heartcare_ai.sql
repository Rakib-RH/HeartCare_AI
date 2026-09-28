CREATE DATABASE IF NOT EXISTS `heartcare_ai` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `heartcare_ai`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('admin', 'doctor', 'patient') NOT NULL DEFAULT 'patient',
  `specialty` VARCHAR(100) DEFAULT 'Cardiologist',
  `available_days` VARCHAR(100) DEFAULT 'Monday,Tuesday,Wednesday,Thursday,Friday',
  `available_hours` VARCHAR(100) DEFAULT '09:00 - 17:00',
  `phone` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `recordings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL DEFAULT '',
  `file_path` VARCHAR(255) NOT NULL DEFAULT '',
  `original_name` VARCHAR(255) NOT NULL DEFAULT '',
  `ai_result` VARCHAR(100) DEFAULT 'pending_clinician_review',
  `ai_confidence` DECIMAL(5,2) DEFAULT 0.00,
  `status` ENUM('pending', 'reviewed') DEFAULT 'pending',
  `clinical_notes` TEXT DEFAULT NULL,
  `doctor_advice` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'confirmed', 'accepted', 'cancelled', 'completed') DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

INSERT INTO `users` (`id`, `name`, `email`, `password`, `password_hash`, `role`, `specialty`, `available_days`, `available_hours`, `phone`) VALUES
(1, 'System Admin', 'admin@heartcare.local', '$2y$10$KfxLOmiyvOv7eg3Dv5Sg3OQeWxV7tMX0oiz2/FAVft0TLYE3xVPpe', '$2y$10$KfxLOmiyvOv7eg3Dv5Sg3OQeWxV7tMX0oiz2/FAVft0TLYE3xVPpe', 'admin', NULL, NULL, NULL, '01700000000'),
(2, 'Dr. Sarah Khan', 'doctor@heartcare.local', '$2y$10$tXEnpLK2nbyqvlwqYDO9G.h9CbDfvnLdb3EGc3vraZFggaqymJA3y', '$2y$10$tXEnpLK2nbyqvlwqYDO9G.h9CbDfvnLdb3EGc3vraZFggaqymJA3y', 'doctor', 'Cardiologist', 'Monday,Wednesday,Friday', '09:00 - 15:00', '01800000000'),
(3, 'John Doe', 'patient@heartcare.local', '$2y$10$iMp4L8KaltLdY.a1gtKgheUL8e1VDcpk3IdtL7rieNcKf5duCbaMi', '$2y$10$iMp4L8KaltLdY.a1gtKgheUL8e1VDcpk3IdtL7rieNcKf5duCbaMi', 'patient', NULL, NULL, NULL, '01900000000')
ON DUPLICATE KEY UPDATE `password`=VALUES(`password`), `password_hash`=VALUES(`password_hash`);
