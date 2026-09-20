<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /profile.php');
    exit;
}

$db = getDB();
$userId = currentUserId();

$type = $_POST['type'] ?? '';
$skillId = $_POST['skill_id'] ?? '';
$proficiency = $_POST['proficiency'] ?? 'beginner';
$newSkillName = trim($_POST['new_skill_name'] ?? '');

if (!in_array($type, ['teach', 'learn'], true)) {
    setFlash('error', 'Invalid request.');
    header('Location: /profile.php');
    exit;
}
if (!in_array($proficiency, ['beginner', 'intermediate', 'advanced', 'expert'], true)) {
    $proficiency = 'beginner';
}

// --- Resolve skill_id: either an existing skill, or create a new one ---
if ($skillId === 'new') {
    if ($newSkillName === '') {
        setFlash('error', 'Please type the name of the new skill.');
        header('Location: /profile.php');
        exit;
    }
    // Reuse an existing skill with the same name (case-insensitive) if it already exists.
    $stmt = $db->prepare('SELECT skill_id FROM skills WHERE LOWER(skill_name) = LOWER(?)');
    $stmt->execute([$newSkillName]);
    $existing = $stmt->fetch();

    if ($existing) {
        $skillId = $existing['skill_id'];
    } else {
        $stmt = $db->prepare('INSERT INTO skills (skill_name) VALUES (?)');
        $stmt->execute([$newSkillName]);
        $skillId = $db->lastInsertId();
    }
} else {
    $skillId = (int) $skillId;
    if ($skillId <= 0) {
        setFlash('error', 'Please choose a skill.');
        header('Location: /profile.php');
        exit;
    }
}

// --- Insert, ignoring if this user/skill/type combo already exists (unique key in schema) ---
try {
    $stmt = $db->prepare(
        'INSERT INTO user_skills (user_id, skill_id, type, proficiency) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $skillId, $type, $type === 'teach' ? $proficiency : 'beginner']);
    setFlash('success', 'Skill added.');
} catch (PDOException $e) {
    // Duplicate entry (unique_user_skill_type) is the only expected failure here.
    setFlash('error', "You've already added that skill to this list.");
}

header('Location: /profile.php');
exit;
