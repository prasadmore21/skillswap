<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$targetId = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$targetId]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'That student could not be found.');
    header('Location: /matches.php');
    exit;
}

$stmt = $db->prepare(
    "SELECT s.skill_name, us.type, us.proficiency FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id WHERE us.user_id = ? ORDER BY s.skill_name"
);
$stmt->execute([$targetId]);
$skills = $stmt->fetchAll();
$teachSkills = array_filter($skills, fn($s) => $s['type'] === 'teach');
$learnSkills = array_filter($skills, fn($s) => $s['type'] === 'learn');

$stmt = $db->prepare('SELECT AVG(rating) avg_rating, COUNT(*) total FROM ratings WHERE rated_user_id = ?');
$stmt->execute([$targetId]);
$ratingSummary = $stmt->fetch();

$stmt = $db->prepare(
    "SELECT rt.rating, rt.review, rt.created_at, u.full_name AS rater_name
     FROM ratings rt JOIN users u ON u.user_id = rt.rater_id
     WHERE rt.rated_user_id = ? ORDER BY rt.created_at DESC LIMIT 20"
);
$stmt->execute([$targetId]);
$reviews = $stmt->fetchAll();

$pageTitle = clean($user['full_name']);
require __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb / Back -->
<div class="mb-3 animate-fade-in-up">
  <a href="/matches.php" class="text-muted small fw-semibold text-decoration-none">
    <i data-lucide="arrow-left" style="width: 14px; height: 14px; display: inline-block;"></i> Back to Match Finder
  </a>
</div>

<div class="row g-4 animate-fade-in-up">

  <!-- LEFT: Student Summary & Exchange CTA -->
  <div class="col-12 col-lg-4">
    <div class="card shadow-sm text-center">
      <div class="card-body p-4">
        <div class="position-relative d-inline-block mb-3">
          <?php if (!empty($user['profile_picture'])): ?>
            <img src="/uploads/profile_pictures/<?php echo clean($user['profile_picture']); ?>"
                 alt="Profile" class="ss-avatar" width="110" height="110">
          <?php else: ?>
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white shadow-sm"
                 style="width:110px;height:110px;background:linear-gradient(135deg,var(--ss-primary) 0%,var(--ss-primary-hover) 100%);font-size:2.4rem;font-weight:700;">
              <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
          <?php endif; ?>
        </div>

        <h3 class="h4 fw-bold mb-1"><?php echo clean($user['full_name']); ?></h3>

        <div class="mb-3">
          <?php if ($user['is_verified']): ?>
            <span class="verified-badge">
              <i data-lucide="shield-check" style="width:14px;height:14px;"></i> Verified Teacher
            </span>
          <?php else: ?>
            <span class="badge bg-secondary">Student Member</span>
          <?php endif; ?>
        </div>

        <?php if ($ratingSummary['total'] > 0): ?>
          <div class="p-2 mb-3 rounded-3 bg-light border d-inline-flex align-items-center gap-2">
            <span class="ss-stars fs-6"><?php echo str_repeat('★', (int)round($ratingSummary['avg_rating'])) . str_repeat('☆', 5 - (int)round($ratingSummary['avg_rating'])); ?></span>
            <span class="fw-bold text-dark small"><?php echo number_format((float)$ratingSummary['avg_rating'], 1); ?></span>
            <span class="text-muted small">(<?php echo (int)$ratingSummary['total']; ?> review<?php echo $ratingSummary['total'] == 1 ? '' : 's'; ?>)</span>
          </div>
        <?php else: ?>
          <p class="text-muted small mb-3">No reviews yet &bull; New member</p>
        <?php endif; ?>

        <?php if (!empty($user['bio'])): ?>
          <div class="text-start p-3 bg-light rounded-3 small text-secondary mb-3">
            <?php echo nl2br(clean($user['bio'])); ?>
          </div>
        <?php endif; ?>

        <?php if ($targetId !== currentUserId()): ?>
          <a href="/request_form.php?to=<?php echo (int)$targetId; ?>" class="btn btn-primary w-100 py-2">
            <i data-lucide="send" style="width: 16px; height: 16px;"></i>
            <span>Request Skill Exchange</span>
          </a>
        <?php else: ?>
          <a href="/profile.php" class="btn btn-outline-secondary w-100 py-2">Edit My Profile</a>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- RIGHT: Detailed Skills & Feedback History -->
  <div class="col-12 col-lg-8">

    <!-- Skills Can Teach -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="graduation-cap" style="width:20px;height:20px;color:var(--ss-primary);"></i>
          <span class="fw-bold">Skills They Can Teach</span>
        </div>
        <span class="badge bg-primary"><?php echo count($teachSkills); ?> available</span>
      </div>
      <div class="card-body">
        <?php if (empty($teachSkills)): ?>
          <p class="text-muted small mb-0">No teaching skills listed yet.</p>
        <?php else: ?>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($teachSkills as $ts): ?>
              <?php
                $prof = strtolower($ts['proficiency'] ?? 'intermediate');
                $badgeClass = match($prof) {
                  'expert' => 'bg-success',
                  'advanced' => 'bg-primary',
                  'intermediate' => 'bg-info',
                  default => 'bg-secondary'
                };
              ?>
              <div class="p-2 px-3 rounded-pill bg-light border d-inline-flex align-items-center gap-2">
                <span class="fw-semibold text-dark small"><?php echo clean($ts['skill_name']); ?></span>
                <span class="badge <?php echo $badgeClass; ?>" style="font-size: 0.68rem;"><?php echo clean(ucfirst($ts['proficiency'])); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Skills Wants to Learn -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="sparkles" style="width:20px;height:20px;color:var(--ss-accent);"></i>
          <span class="fw-bold">Skills They Want to Learn</span>
        </div>
        <span class="badge bg-info"><?php echo count($learnSkills); ?> wanted</span>
      </div>
      <div class="card-body">
        <?php if (empty($learnSkills)): ?>
          <p class="text-muted small mb-0">No learning interests listed yet.</p>
        <?php else: ?>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($learnSkills as $ls): ?>
              <span class="badge bg-secondary p-2 px-3 fs-6 fw-normal text-dark border">
                <?php echo clean($ls['skill_name']); ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Reviews Received -->
    <div class="card shadow-sm">
      <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="star" style="width:18px;height:18px;color:#fbbf24;"></i>
          <span class="fw-bold">Peer Reviews &amp; Ratings</span>
        </div>
        <span class="badge bg-secondary"><?php echo count($reviews); ?></span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($reviews)): ?>
          <div class="p-4 text-center text-muted small">
            This student has not received any session ratings yet.
          </div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($reviews as $r): ?>
              <div class="list-group-item p-3 px-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark"><?php echo clean($r['rater_name']); ?></span>
                    <span class="ss-stars small"><?php echo str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']); ?></span>
                  </div>
                  <span class="text-muted small"><?php echo clean(date('M j, Y', strtotime($r['created_at']))); ?></span>
                </div>
                <?php if ($r['review']): ?>
                  <p class="mb-0 text-secondary small mt-1">&ldquo;<?php echo clean($r['review']); ?>&rdquo;</p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

