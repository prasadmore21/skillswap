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
$rating = (int) ($_POST['rating'] ?? 0);
$review = trim($_POST['review'] ?? '');

if ($rating < 1 || $rating > 5) {
    setFlash('error', 'Please choose a rating between 1 and 5.');
    header('Location: /rate_session.php?session_id=' . $sessionId);
    exit;
}

$stmt = $db->prepare(
    "SELECT ss.status, r.request_id, r.sender_id, r.receiver_id
     FROM sessions_schedule ss
     JOIN exchange_requests r ON r.request_id = ss.request_id
     WHERE ss.session_id = ?"
);
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session || ($session['sender_id'] != $userId && $session['receiver_id'] != $userId)) {
    setFlash('error', 'That session could not be found.');
    header('Location: /sessions.php');
    exit;
}
if ($session['status'] !== 'completed') {
    setFlash('error', 'You can only rate a completed session.');
    header('Location: /sessions.php');
    exit;
}

$ratedUserId = ($session['sender_id'] == $userId) ? $session['receiver_id'] : $session['sender_id'];

// Re-check for an existing rating (guards against double-submit / back-button resubmit).
$stmt = $db->prepare('SELECT 1 FROM ratings WHERE request_id = ? AND rater_id = ?');
$stmt->execute([$session['request_id'], $userId]);
if ($stmt->fetch()) {
    setFlash('error', 'You already rated this exchange.');
    header('Location: /sessions.php');
    exit;
}

$stmt = $db->prepare(
    "INSERT INTO ratings (request_id, rater_id, rated_user_id, rating, review) VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$session['request_id'], $userId, $ratedUserId, $rating, $review ?: null]);

$stmt = $db->prepare('SELECT full_name FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$raterName = $stmt->fetchColumn();

$stmt = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
$stmt->execute([$ratedUserId, $raterName . ' left you a ' . $rating . '-star rating.', '/profile.php']);

setFlash('success', 'Thanks for your feedback!');
header('Location: /sessions.php');
exit;
