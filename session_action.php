<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /sessions.php');
    exit;
}

$db = getDB();
$userId = currentUserId();
$sessionId = (int) ($_POST['session_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!in_array($action, ['complete', 'cancel'], true)) {
    setFlash('error', 'Invalid action.');
    header('Location: /sessions.php');
    exit;
}

$stmt = $db->prepare(
    "SELECT ss.*, r.sender_id, r.receiver_id FROM sessions_schedule ss
     JOIN exchange_requests r ON r.request_id = ss.request_id
     WHERE ss.session_id = ?"
);
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

// Only the two people involved in the underlying request can act on this session.
if (!$session || ($session['sender_id'] != $userId && $session['receiver_id'] != $userId)) {
    setFlash('error', 'That session could not be found.');
    header('Location: /sessions.php');
    exit;
}
if ($session['status'] !== 'scheduled') {
    setFlash('error', 'That session has already been updated.');
    header('Location: /sessions.php');
    exit;
}

$newStatus = $action === 'complete' ? 'completed' : 'cancelled';
$stmt = $db->prepare('UPDATE sessions_schedule SET status = ? WHERE session_id = ?');
$stmt->execute([$newStatus, $sessionId]);

$otherUserId = ($session['sender_id'] == $userId) ? $session['receiver_id'] : $session['sender_id'];
$stmt = $db->prepare('SELECT full_name FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$actorName = $stmt->fetchColumn();

$message = $action === 'complete'
    ? $actorName . ' marked your session together as completed.'
    : $actorName . ' cancelled your upcoming session.';

$stmt = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
$stmt->execute([$otherUserId, $message, '/sessions.php']);

setFlash('success', 'Session marked as ' . $newStatus . '.');
header('Location: /sessions.php');
exit;
