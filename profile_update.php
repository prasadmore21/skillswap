<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/cloudinary.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /profile.php');
    exit;
}

$db = getDB();
$userId = currentUserId();

$fullName = trim($_POST['full_name'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if ($fullName === '') {
    setFlash('error', 'Name cannot be empty.');
    header('Location: /profile.php');
    exit;
}

$updates = ['full_name = ?', 'bio = ?'];
$params = [$fullName, $bio];

// --- Handle profile picture upload, if one was provided ---
if (!empty($_FILES['profile_picture']['name'])) {
    $file = $_FILES['profile_picture'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'There was a problem uploading your picture. Please try again.');
        header('Location: /profile.php');
        exit;
    }
    if ($file['size'] > $maxSize) {
        setFlash('error', 'Profile picture must be under 2MB.');
        header('Location: /profile.php');
        exit;
    }

    // Verify the actual file content, not just the extension/claimed MIME type.
    $detectedType = mime_content_type($file['tmp_name']);
    if (!in_array($detectedType, $allowedTypes, true)) {
        setFlash('error', 'Profile picture must be a JPG, PNG, or GIF image.');
        header('Location: /profile.php');
        exit;
    }

    // Upload to Cloudinary (permanent cloud storage — survives server restarts)
    $cloudinaryUrl = uploadToCloudinary($file['tmp_name'], 'image', 'skillswap/profiles');

    if (!$cloudinaryUrl) {
        setFlash('error', 'Could not upload profile picture to cloud storage. Please try again.');
        header('Location: /profile.php');
        exit;
    }

    // Store the full Cloudinary URL instead of a local filename
    $updates[] = 'profile_picture = ?';
    $params[] = $cloudinaryUrl;
}

$params[] = $userId;
$stmt = $db->prepare('UPDATE users SET ' . implode(', ', $updates) . ' WHERE user_id = ?');
$stmt->execute($params);

setFlash('success', 'Profile updated.');
header('Location: /profile.php');
exit;
