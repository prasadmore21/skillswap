<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();
$notificationId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM notifications WHERE notification_id = ? AND user_id = ?');
$stmt->execute([$notificationId, $userId]);
$notification = $stmt->fetch();

if (!$notification) {
    header('Location: /notifications.php');
    exit;
}

if (!$notification['is_read']) {
    $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ?');
    $stmt->execute([$notificationId]);
}

header('Location: ' . ($notification['link'] ?: '/notifications.php'));
exit;
