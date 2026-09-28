<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/users.php');
    exit;
}

$db = getDB();
$targetUserId = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!in_array($action, ['suspend', 'activate', 'promote', 'demote'], true)) {
    setFlash('error', 'Invalid action.');
    header('Location: /admin/users.php');
    exit;
}

// Never allow an admin to act on their own account here — prevents accidental
// self-suspension or self-demotion locking everyone out of the admin panel.
if ($targetUserId === currentUserId()) {
    setFlash('error', 'You cannot perform this action on your own account.');
    header('Location: /admin/users.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$targetUserId]);
$target = $stmt->fetch();

if (!$target) {
    setFlash('error', 'User not found.');
    header('Location: /admin/users.php');
    exit;
}

switch ($action) {
    case 'suspend':
        $stmt = $db->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        setFlash('success', $target['full_name'] . ' has been suspended.');
        break;

    case 'activate':
        $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        setFlash('success', $target['full_name'] . ' has been reactivated.');
        break;

    case 'promote':
        $stmt = $db->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        setFlash('success', $target['full_name'] . ' is now an admin.');
        break;

    case 'demote':
        $stmt = $db->prepare("UPDATE users SET role = 'student' WHERE user_id = ?");
        $stmt->execute([$targetUserId]);
        setFlash('success', $target['full_name'] . ' is no longer an admin.');
        break;
}

header('Location: /admin/users.php');
exit;
