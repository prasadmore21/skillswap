<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /certificates.php');
    exit;
}

$db = getDB();
$userId = currentUserId();
$skillId = (int) ($_POST['skill_id'] ?? 0);

// Confirm this user actually teaches the claimed skill (never trust the client).
$stmt = $db->prepare("SELECT 1 FROM user_skills WHERE user_id = ? AND skill_id = ? AND type = 'teach'");
$stmt->execute([$userId, $skillId]);
if (!$stmt->fetch()) {
    setFlash('error', 'You can only upload a certificate for a skill you teach.');
    header('Location: /certificates.php');
    exit;
}

if (empty($_FILES['certificate_file']['name'])) {
    setFlash('error', 'Please choose a file to upload.');
    header('Location: /certificates.php');
    exit;
}

$file = $_FILES['certificate_file'];
$allowedTypes = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($file['error'] !== UPLOAD_ERR_OK) {
    setFlash('error', 'There was a problem uploading your file. Please try again.');
    header('Location: /certificates.php');
    exit;
}
if ($file['size'] > $maxSize) {
    setFlash('error', 'File must be under 5MB.');
    header('Location: /certificates.php');
    exit;
}

// Check the actual file content, not the filename or claimed content-type.
$detectedType = mime_content_type($file['tmp_name']);
if (!isset($allowedTypes[$detectedType])) {
    setFlash('error', 'Only PDF, JPG, or PNG files are accepted.');
    header('Location: /certificates.php');
    exit;
}

$ext = $allowedTypes[$detectedType];
$newFilename = 'cert_' . $userId . '_' . $skillId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$destination = __DIR__ . '/uploads/certificates/' . $newFilename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    setFlash('error', 'Could not save the uploaded file. Please try again.');
    header('Location: /certificates.php');
    exit;
}

$stmt = $db->prepare(
    "INSERT INTO certificates (user_id, skill_id, file_path, status) VALUES (?, ?, ?, 'pending')"
);
$stmt->execute([$userId, $skillId, $newFilename]);

setFlash('success', 'Certificate submitted for review. An admin will verify it soon.');
header('Location: /certificates.php');
exit;
