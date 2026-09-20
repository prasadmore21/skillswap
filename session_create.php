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
$scheduledDate = $_POST['scheduled_date'] ?? '';
$mode = $_POST['mode'] ?? 'online';
$locationOrLink = trim($_POST['location_or_link'] ?? '');

if (!in_array($mode, ['online', 'in-person'], true)) {
    $mode = 'online';
}

$stmt = $db->prepare('SELECT * FROM exchange_requests WHERE request_id = ?');
$stmt->execute([$requestId]);
$request = $stmt->fetch();

// Only the two people involved, and only for an accepted request.
if (!$request || ($request['sender_id'] != $userId && $request['receiver_id'] != $userId)) {
    setFlash('error', 'That request could not be found.');
    header('Location: /exchange_requests.php');
    exit;
}
if ($request['status'] !== 'accepted') {
    setFlash('error', 'You can only schedule a session for an accepted request.');
    header('Location: /exchange_requests.php');
    exit;
}

// Validate the date: must parse, and must not be in the past.
$timestamp = strtotime($scheduledDate);
if ($timestamp === false || $timestamp < time() - 60) {
    setFlash('error', 'Please choose a valid future date and time.');
    header('Location: /session_form.php?request_id=' . $requestId);
    exit;
}

// If mode is online and no meeting link was provided, auto-generate a Jitsi Meet room link
if ($mode === 'online' && empty($locationOrLink)) {
    $jitsiRoom = 'SkillSwap-Session-' . $requestId . '-' . bin2hex(random_bytes(4));
    $locationOrLink = 'https://meet.jit.si/' . $jitsiRoom;
}

$stmt = $db->prepare(
    "INSERT INTO sessions_schedule (request_id, scheduled_date, mode, location_or_link)
     VALUES (?, ?, ?, ?)"
);
$stmt->execute([$requestId, date('Y-m-d H:i:s', $timestamp), $mode, $locationOrLink ?: null]);

// Notify the other party
$otherUserId = ($request['sender_id'] == $userId) ? $request['receiver_id'] : $request['sender_id'];
$stmt = $db->prepare('SELECT full_name FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$actorName = $stmt->fetchColumn();

$stmt = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
$stmt->execute([
    $otherUserId,
    $actorName . ' scheduled a session for ' . date('M j, Y g:i A', $timestamp) . '.',
    '/sessions.php',
]);

setFlash('success', 'Session scheduled.');
header('Location: /sessions.php');
exit;
