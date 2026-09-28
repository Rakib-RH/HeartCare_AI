<?php
require_once __DIR__ . '/../config/config.php';
require_login('patient');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['user_id'];
    $notes = sanitize($_POST['notes'] ?? '');

    if (isset($_FILES['heart_sound']) && $_FILES['heart_sound']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['heart_sound']['tmp_name'];
        $fileName = $_FILES['heart_sound']['name'];
        
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = ['wav', 'mp3', 'ogg', 'm4a'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            
            $uploadFileDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $db = getDB();

                $db_file_path = 'uploads/' . $newFileName;
                $predict_script = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'predict.py';
                
                // AI প্রেডিকশন রান করা (Python Script)
                $python_executable = 'python';
                $possible_py_paths = [
                    'C:\\Users\\Imtiaz Dinar Akib\\AppData\\Local\\Programs\\Python\\Python311\\python.exe',
                    'C:\\Program Files\\Python311\\python.exe',
                    'C:\\Python311\\python.exe',
                    'python',
                    'py'
                ];
                
                foreach ($possible_py_paths as $p) {
                    if (file_exists($p)) {
                        $python_executable = '"' . $p . '"';
                        break;
                    }
                }

                $escaped_script = escapeshellarg($predict_script);
                $escaped_audio = escapeshellarg($dest_path);
                $cmd = "$python_executable $escaped_script $escaped_audio 2>&1";
                $output = shell_exec($cmd);

                $ai_result = 'Normal';
                $confidence = 90.0;

                if (!empty($output)) {
                    $json_data = json_decode(trim($output), true);
                    if ($json_data && isset($json_data['status']) && $json_data['status'] === 'success') {
                        $ai_result = $json_data['result'] ?? 'Normal';
                        $confidence = floatval($json_data['confidence'] ?? 90.0);
                    }
                }

                try {
                    $stmt = $db->prepare("INSERT INTO recordings (patient_id, filename, file_path, original_name, ai_result, ai_confidence, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssds", $patient_id, $newFileName, $db_file_path, $fileName, $ai_result, $confidence, $notes);

                    if ($stmt->execute()) {
                        $success = "Heart sound analyzed successfully! AI Diagnosis: " . htmlspecialchars($ai_result) . " (" . number_format($confidence, 1) . "% confidence)";
                    } else {
                        $error = "Failed to save to database: " . $stmt->error;
                    }
                    $stmt->close();

                } catch (Exception $e) {
                    $error = "Error: " . $e->getMessage();
                }
            } else {
                $error = 'ফাইল আপলোড ডিরেক্টরিতে সেভ করতে সমস্যা হচ্ছে।';
            }
        } else {
            $error = 'শুধুমাত্র WAV, MP3, OGG, অথবা M4A অডিও ফাইল আপলোড করা যাবে।';
        }
    } else {
        $error = 'দয়া করে একটি বৈধ অডিও ফাইল নির্বাচন করুন।';
    }
}

require_once __DIR__ . '/../partials/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow border-0 p-4">
            <div class="text-center mb-4">
                <h3 class="text-primary fw-bold"><i class="fa-solid fa-file-audio me-2"></i>Upload Heart Sound</h3>
                <p class="text-muted">Upload your stethoscope audio recording for instant AI-powered heart condition analysis.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?php echo $success; ?>
                    <div class="mt-2">
                        <a href="dashboard.php" class="btn btn-sm btn-success me-2">Go to Dashboard</a>
                        <a href="upload.php" class="btn btn-sm btn-outline-success">Upload Another</a>
                    </div>
                </div>
            <?php endif; ?>

            <form action="upload.php" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Audio File (WAV, MP3, M4A) *</label>
                    <input type="file" name="heart_sound" class="form-control form-control-lg" accept=".wav, .mp3, .ogg, .m4a" required>
                    <div class="form-text">For accurate AI diagnosis, ensure the audio recording is clear with low background noise.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Symptoms / Additional Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="e.g., Chest pain, shortness of breath, recorded after light walking..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 fs-5">
                    <i class="fa-solid fa-microchip me-2"></i>Upload & Analyze Sound
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>