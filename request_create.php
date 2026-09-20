<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /matches.php');
    exit;
}

$db = getDB();
$senderId = currentUserId();
$receiverId = (int) ($_POST['receiver_id'] ?? 0);
$offeredSkillId = (int) ($_POST['offered_skill_id'] ?? 0);
$requestedSkillId = (int) ($_POST['requested_skill_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($receiverId === $senderId || $receiverId <= 0) {
    setFlash('error', 'Invalid request.');
    header('Location: /matches.php');
    exit;
}

// Server-side re-check: sender must actually teach the offered skill,
// and the receiver must actually teach the requested skill.
$stmt = $db->prepare("SELECT 1 FROM user_skills WHERE user_id = ? AND skill_id = ? AND type = 'teach'");
$stmt->execute([$senderId, $offeredSkillId]);
if (!$stmt->fetch()) {
    setFlash('error', 'You can only offer a skill you teach.');
    header('Location: /request_form.php?to=' . $receiverId);
    exit;
}
$stmt->execute([$receiverId, $requestedSkillId]);
if (!$stmt->fetch()) {
    setFlash('error', 'That student does not teach the skill you selected.');
    header('Location: /request_form.php?to=' . $receiverId);
    exit;
}

// Avoid duplicate pending requests for the same pairing
$stmt = $db->prepare(
    "SELECT 1 FROM exchange_requests
     WHERE sender_id = ? AND receiver_id = ? AND offered_skill_id = ? AND requested_skill_id = ? AND status = 'pending'"
);
$stmt->execute([$senderId, $receiverId, $offeredSkillId, $requestedSkillId]);
if ($stmt->fetch()) {
    setFlash('error', 'You already have a pending request like this with that student.');
    header('Location: /exchange_requests.php');
    exit;
}

$stmt = $db->prepare(
    "INSERT INTO exchange_requests (sender_id, receiver_id, offered_skill_id, requested_skill_id, message)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$senderId, $receiverId, $offeredSkillId, $requestedSkillId, $message ?: null]);

$stmt = $db->prepare('SELECT full_name FROM users WHERE user_id = ?');
$stmt->execute([$senderId]);
$senderName = $stmt->fetchColumn();

$stmt = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
$stmt->execute([$receiverId, $senderName . ' sent you a skill exchange request.', '/exchange_requests.php']);

setFlash('success', 'Request sent!');
header('Location: /exchange_requests.php');
exit;
