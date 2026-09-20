<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /profile.php');
    exit;
}

$db = getDB();
$userId = currentUserId();
$userSkillId = (int) ($_POST['user_skill_id'] ?? 0);

// The WHERE clause enforces ownership — a user can only delete their own rows,
// even if someone tampers with the hidden form field.
$stmt = $db->prepare('DELETE FROM user_skills WHERE user_skill_id = ? AND user_id = ?');
$stmt->execute([$userSkillId, $userId]);

setFlash('success', 'Skill removed.');
header('Location: /profile.php');
exit;
