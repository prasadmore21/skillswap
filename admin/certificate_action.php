<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/certificates.php');
    exit;
}

$db = getDB();
$certificateId = (int) ($_POST['certificate_id'] ?? 0);
$action = $_POST['action'] ?? '';
$remarks = trim($_POST['remarks'] ?? '');

if (!in_array($action, ['approve', 'reject'], true)) {
    setFlash('error', 'Invalid action.');
    header('Location: /admin/certificates.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM certificates WHERE certificate_id = ?');
$stmt->execute([$certificateId]);
$certificate = $stmt->fetch();

if (!$certificate || $certificate['status'] !== 'pending') {
    setFlash('error', 'That certificate has already been reviewed.');
    header('Location: /admin/certificates.php');
    exit;
}

$db->beginTransaction();
try {
    if ($action === 'approve') {
        $stmt = $db->prepare(
            "UPDATE certificates SET status = 'approved', admin_remarks = ?, reviewed_at = NOW() WHERE certificate_id = ?"
        );
        $stmt->execute([$remarks ?: null, $certificateId]);

        // At least one approved certificate is enough to earn the verified badge.
        $stmt = $db->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
        $stmt->execute([$certificate['user_id']]);

        $stmt = $db->prepare(
            "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)"
        );
        $stmt->execute([$certificate['user_id'], 'Your certificate was approved — you are now a verified teacher!', '/certificates.php']);

    } else {
        if ($remarks === '') {
            $remarks = 'No reason provided.';
        }
        $stmt = $db->prepare(
            "UPDATE certificates SET status = 'rejected', admin_remarks = ?, reviewed_at = NOW() WHERE certificate_id = ?"
        );
        $stmt->execute([$remarks, $certificateId]);

        $stmt = $db->prepare(
            "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)"
        );
        $stmt->execute([$certificate['user_id'], 'Your certificate was rejected: ' . $remarks, '/certificates.php']);
    }

    $db->commit();
    setFlash('success', 'Certificate ' . ($action === 'approve' ? 'approved' : 'rejected') . '.');
} catch (Exception $e) {
    $db->rollBack();
    setFlash('error', 'Something went wrong. Please try again.');
}

header('Location: /admin/certificates.php');
exit;
