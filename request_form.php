<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();
$targetId = (int) ($_GET['to'] ?? 0);

if ($targetId === $userId || $targetId <= 0) {
    setFlash('error', 'Invalid request.');
    header('Location: /matches.php');
    exit;
}

$stmt = $db->prepare('SELECT user_id, full_name, is_verified FROM users WHERE user_id = ?');
$stmt->execute([$targetId]);
$target = $stmt->fetch();
if (!$target) {
    setFlash('error', 'That student could not be found.');
    header('Location: /matches.php');
    exit;
}

// Skills the target teaches — this is what the requester wants to learn
$stmt = $db->prepare(
    "SELECT s.skill_id, s.skill_name FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.user_id = ? AND us.type = 'teach' ORDER BY s.skill_name"
);
$stmt->execute([$targetId]);
$theirTeachSkills = $stmt->fetchAll();

// My own skills — what I can offer in return
$stmt = $db->prepare(
    "SELECT s.skill_id, s.skill_name FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.user_id = ? AND us.type = 'teach' ORDER BY s.skill_name"
);
$stmt->execute([$userId]);
$myTeachSkills = $stmt->fetchAll();

$pageTitle = 'Request an Exchange';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center animate-fade-in-up">
  <div class="col-12 col-md-8 col-lg-6">

    <!-- Back link -->
    <div class="mb-3">
      <a href="/matches.php" class="text-muted small fw-semibold text-decoration-none">
        <i data-lucide="arrow-left" style="width: 14px; height: 14px; display: inline-block;"></i> Back to Match Finder
      </a>
    </div>

    <!-- Partner Banner -->
    <div class="card shadow-sm mb-4 border-primary border-opacity-25" style="background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
          <div class="brand-icon-box" style="width: 44px; height: 44px; border-radius: 12px;">
            <i data-lucide="arrow-left-right" style="width: 22px; height: 22px;"></i>
          </div>
          <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Request Skill Exchange</h2>
            <p class="text-muted small mb-0">
              Propose a two-way learning session with <strong><?php echo clean($target['full_name']); ?></strong>
              <?php if (!empty($target['is_verified'])): ?>
                <span class="verified-badge ms-1" style="font-size: 0.68rem; padding: 0.1rem 0.45rem;">
                  <i data-lucide="shield-check" style="width: 11px; height: 11px;"></i> Verified
                </span>
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <?php if (empty($theirTeachSkills)): ?>
      <div class="alert alert-warning">
        <i data-lucide="alert-triangle" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <div>This student hasn't listed any skills to teach yet.</div>
      </div>
    <?php elseif (empty($myTeachSkills)): ?>
      <div class="alert alert-warning">
        <i data-lucide="alert-circle" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
        <div>
          You need at least one skill on your "Skills I Can Teach" list before requesting an exchange &mdash;
          <a href="/profile.php" class="fw-bold">add one on your profile</a> first.
        </div>
      </div>
    <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <form action="/request_create.php" method="POST">
            <input type="hidden" name="receiver_id" value="<?php echo (int)$target['user_id']; ?>">

            <div class="mb-4">
              <label class="form-label d-flex align-items-center gap-1">
                <i data-lucide="book-open" style="width: 16px; height: 16px; color: var(--ss-primary);"></i>
                <span>What do you want to learn from <?php echo clean($target['full_name']); ?>?</span>
              </label>
              <select name="requested_skill_id" class="form-select" required>
                <option value="" disabled selected>Select a skill they teach&hellip;</option>
                <?php foreach ($theirTeachSkills as $s): ?>
                  <option value="<?php echo (int)$s['skill_id']; ?>"><?php echo clean($s['skill_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label d-flex align-items-center gap-1">
                <i data-lucide="graduation-cap" style="width: 16px; height: 16px; color: var(--ss-accent);"></i>
                <span>What skill will you teach in return?</span>
              </label>
              <select name="offered_skill_id" class="form-select" required>
                <option value="" disabled selected>Select a skill from your profile&hellip;</option>
                <?php foreach ($myTeachSkills as $s): ?>
                  <option value="<?php echo (int)$s['skill_id']; ?>"><?php echo clean($s['skill_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label d-flex align-items-center gap-1">
                <i data-lucide="message-square" style="width: 16px; height: 16px; color: var(--ss-text-muted);"></i>
                <span>Personal Message (optional)</span>
              </label>
              <textarea name="message" class="form-control" rows="3" maxlength="500"
                        placeholder="Say hello, share your current level, or suggest convenient meeting times..."></textarea>
              <div class="form-text">Keep it friendly and concise (max 500 characters).</div>
            </div>

            <div class="d-flex gap-2 pt-2 border-top">
              <button type="submit" class="btn btn-primary flex-fill py-2">
                <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                <span>Send Exchange Proposal</span>
              </button>
              <a href="/matches.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

