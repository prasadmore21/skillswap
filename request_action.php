<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /exchange_requests.php');
    exit;
}

$db = getDB();
$userId = currentUserId();
$requestId = (int) ($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!in_array($action, ['accept', 'reject', 'cancel'], true)) {
    setFlash('error', 'Invalid action.');
    header('Location: /exchange_requests.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM exchange_requests WHERE request_id = ?');
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request || $request['status'] !== 'pending') {
    setFlash('error', 'That request is no longer pending.');
    header('Location: /exchange_requests.php');
    exit;
}

// Ownership checks: only the receiver can accept/reject, only the sender can cancel.
if (in_array($action, ['accept', 'reject'], true) && $request['receiver_id'] != $userId) {
    setFlash('error', 'You are not authorized to do that.');
    header('Location: /exchange_requests.php');
    exit;
}
if ($action === 'cancel' && $request['sender_id'] != $userId) {
    setFlash('error', 'You are not authorized to do that.');
    header('Location: /exchange_requests.php');
    exit;
}

$newStatus = ['accept' => 'accepted', 'reject' => 'rejected', 'cancel' => 'cancelled'][$action];
$stmt = $db->prepare('UPDATE exchange_requests SET status = ? WHERE request_id = ?');
$stmt->execute([$newStatus, $requestId]);

// Notify the other party
$notifyUserId = ($action === 'cancel') ? $request['receiver_id'] : $request['sender_id'];
$stmt = $db->prepare('SELECT full_name FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$actorName = $stmt->fetchColumn();

$messages = [
    'accept' => $actorName . ' accepted your exchange request!',
    'reject' => $actorName . ' declined your exchange request.',
    'cancel' => $actorName . ' cancelled their exchange request.',
];
$stmt = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
$stmt->execute([$notifyUserId, $messages[$action], '/exchange_requests.php']);

setFlash('success', 'Request ' . $newStatus . '.');
header('Location: /exchange_requests.php');
exit;
