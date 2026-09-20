<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

// Current user record
$stmt = $db->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Skills this user teaches / wants to learn (joined with the master skill list)
$stmt = $db->prepare(
    "SELECT us.user_skill_id, us.type, us.proficiency, s.skill_name
     FROM user_skills us JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.user_id = ? ORDER BY s.skill_name"
);
$stmt->execute([$userId]);
$allSkills = $stmt->fetchAll();
$teachSkills = array_filter($allSkills, fn($s) => $s['type'] === 'teach');
$learnSkills = array_filter($allSkills, fn($s) => $s['type'] === 'learn');

// Full master list, for the "add a skill" dropdown
$masterSkills = $db->query('SELECT skill_id, skill_name FROM skills ORDER BY skill_name')->fetchAll();

// Ratings received
$stmt = $db->prepare('SELECT AVG(rating) avg_rating, COUNT(*) total FROM ratings WHERE rated_user_id = ?');
$stmt->execute([$userId]);
$ratingSummary = $stmt->fetch();

$stmt = $db->prepare(
    "SELECT rt.rating, rt.review, rt.created_at, u.full_name AS rater_name
     FROM ratings rt JOIN users u ON u.user_id = rt.rater_id
     WHERE rt.rated_user_id = ? ORDER BY rt.created_at DESC LIMIT 10"
);
$stmt->execute([$userId]);
$myReviews = $stmt->fetchAll();

$pageTitle = 'My Profile';
require __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="ss-hero-card mb-4 animate-fade-in-up" style="padding: 1.75rem 2rem;">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index: 2;">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(255,255,255,0.2);">
          <i data-lucide="user-check" style="width:18px;height:18px;color:#fff;"></i>
        </div>
        <h1 class="h3 mb-0 ss-hero-title">My Profile &amp; Skills</h1>
      </div>
      <p class="ss-hero-subtitle mb-0">
        Manage your student presence, update teach/learn subjects, and review ratings from exchange partners.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="/view_profile.php?id=<?php echo (int)$userId; ?>" class="btn btn-sm px-3.5 py-2 text-nowrap" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.35);">
        <i data-lucide="external-link" style="width:14px;height:14px;"></i>
        <span>Preview Public Profile</span>
      </a>
    </div>
  </div>
</div>

<div class="row g-4">

  <!-- LEFT: Profile Info & Avatar Management -->
  <div class="col-12 col-lg-5">
    <div class="card shadow-sm mb-4">
      <div class="card-body text-center p-4">
        
        <!-- Avatar Wrapper -->
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
        <p class="text-muted small mb-2"><?php echo clean($user['email']); ?></p>

        <!-- Verified Status Badge -->
        <div class="mb-3">
          <?php if (!empty($user['is_verified'])): ?>
            <span class="verified-badge">
              <i data-lucide="shield-check" style="width:14px;height:14px;"></i> Verified Teacher
            </span>
          <?php else: ?>
            <span class="badge bg-secondary">Student Member</span>
          <?php endif; ?>
        </div>

        <!-- Rating Summary -->
        <?php if (!empty($ratingSummary['total']) && $ratingSummary['total'] > 0): ?>
          <div class="p-2 mb-3 rounded-3 bg-light border d-inline-flex align-items-center gap-2">
            <span class="ss-stars fs-6"><?php echo str_repeat('★', (int)round($ratingSummary['avg_rating'])) . str_repeat('☆', 5 - (int)round($ratingSummary['avg_rating'])); ?></span>
            <span class="fw-bold text-dark small"><?php echo number_format((float)$ratingSummary['avg_rating'], 1); ?></span>
            <span class="text-muted small">(<?php echo (int)$ratingSummary['total']; ?> review<?php echo $ratingSummary['total'] == 1 ? '' : 's'; ?>)</span>
          </div>
        <?php endif; ?>

        <!-- Edit Profile Form -->
        <form action="/profile_update.php" method="POST" enctype="multipart/form-data" class="text-start mt-3 pt-3 border-top">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required
                   value="<?php echo clean($user['full_name']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-control" rows="3" maxlength="1000"
                      placeholder="Share your background, interests, or study focus..."><?php echo clean($user['bio'] ?? ''); ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Change Profile Picture</label>
            <input type="file" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png,.gif">
            <div class="form-text">JPG, PNG, or GIF, max 2MB.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2">
            <i data-lucide="check" style="width:16px;height:16px;"></i>
            <span>Save Profile Changes</span>
          </button>
        </form>

      </div>
    </div>
  </div>

  <!-- RIGHT: Skills Management -->
  <div class="col-12 col-lg-7">

    <!-- SKILLS I CAN TEACH -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="graduation-cap" style="width:20px;height:20px;color:var(--ss-primary);"></i>
          <span class="fw-bold">Skills I Can Teach</span>
        </div>
        <span class="badge bg-primary"><?php echo count($teachSkills); ?> listed</span>
      </div>
      <div class="card-body">
        <?php if (empty($teachSkills)): ?>
          <div class="p-3 text-center rounded bg-light border mb-3">
            <p class="text-muted small mb-0">You haven't listed any skills to teach yet. Add your first skill below!</p>
          </div>
        <?php else: ?>
          <div class="list-group mb-3">
            <?php foreach ($teachSkills as $s): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                  <span class="fw-semibold text-dark"><?php echo clean($s['skill_name']); ?></span>
                  <?php
                    $prof = strtolower($s['proficiency'] ?? 'intermediate');
                    $badgeClass = match($prof) {
                      'expert' => 'bg-success',
                      'advanced' => 'bg-primary',
                      'intermediate' => 'bg-info',
                      default => 'bg-secondary'
                    };
                  ?>
                  <span class="badge <?php echo $badgeClass; ?>"><?php echo clean(ucfirst($s['proficiency'])); ?></span>
                </div>
                <form action="/skill_remove.php" method="POST" class="d-inline" onsubmit="return confirm('Remove this skill from your profile?');">
                  <input type="hidden" name="user_skill_id" value="<?php echo (int)$s['user_skill_id']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Remove skill">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Add Skill Form -->
        <div class="p-3 rounded-3 bg-light border">
          <h6 class="fw-bold mb-2 small text-uppercase text-muted">Add a Skill You Can Teach</h6>
          <form action="/skill_add.php" method="POST" class="row g-2">
            <input type="hidden" name="type" value="teach">
            <div class="col-12 col-sm-6">
              <select name="skill_id" class="form-select form-select-sm" required>
                <option value="" disabled selected>Choose a skill&hellip;</option>
                <?php foreach ($masterSkills as $ms): ?>
                  <option value="<?php echo (int)$ms['skill_id']; ?>"><?php echo clean($ms['skill_name']); ?></option>
                <?php endforeach; ?>
                <option value="new">+ Add a new custom skill&hellip;</option>
              </select>
            </div>
            <div class="col-12 col-sm-4">
              <select name="proficiency" class="form-select form-select-sm">
                <option value="beginner">Beginner</option>
                <option value="intermediate" selected>Intermediate</option>
                <option value="advanced">Advanced</option>
                <option value="expert">Expert</option>
              </select>
            </div>
            <div class="col-12 col-sm-2">
              <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
            </div>
            <div class="col-12">
              <input type="text" name="new_skill_name" class="form-control form-control-sm"
                     placeholder="New skill name (only if you picked '+ Add a new custom skill')">
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- SKILLS I WANT TO LEARN -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="sparkles" style="width:20px;height:20px;color:var(--ss-accent);"></i>
          <span class="fw-bold">Skills I Want to Learn</span>
        </div>
        <span class="badge bg-info"><?php echo count($learnSkills); ?> listed</span>
      </div>
      <div class="card-body">
        <?php if (empty($learnSkills)): ?>
          <div class="p-3 text-center rounded bg-light border mb-3">
            <p class="text-muted small mb-0">You haven't added any skills you want to learn. Add one to find matching teachers!</p>
          </div>
        <?php else: ?>
          <div class="list-group mb-3">
            <?php foreach ($learnSkills as $s): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                <span class="fw-semibold text-dark"><?php echo clean($s['skill_name']); ?></span>
                <form action="/skill_remove.php" method="POST" class="d-inline" onsubmit="return confirm('Remove this skill from your learning list?');">
                  <input type="hidden" name="user_skill_id" value="<?php echo (int)$s['user_skill_id']; ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Remove skill">
                    <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Add Skill to Learn Form -->
        <div class="p-3 rounded-3 bg-light border">
          <h6 class="fw-bold mb-2 small text-uppercase text-muted">Add a Skill You Want to Learn</h6>
          <form action="/skill_add.php" method="POST" class="row g-2">
            <input type="hidden" name="type" value="learn">
            <div class="col-12 col-sm-9">
              <select name="skill_id" class="form-select form-select-sm" required>
                <option value="" disabled selected>Choose a skill to learn&hellip;</option>
                <?php foreach ($masterSkills as $ms): ?>
                  <option value="<?php echo (int)$ms['skill_id']; ?>"><?php echo clean($ms['skill_name']); ?></option>
                <?php endforeach; ?>
                <option value="new">+ Add a new custom skill&hellip;</option>
              </select>
            </div>
            <div class="col-12 col-sm-3">
              <button type="submit" class="btn btn-primary btn-sm w-100">Add to List</button>
            </div>
            <div class="col-12">
              <input type="text" name="new_skill_name" class="form-control form-control-sm"
                     placeholder="New skill name (only if you picked '+ Add a new custom skill')">
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- REVIEWS RECEIVED -->
<?php if (!empty($myReviews)): ?>
<div class="card mt-2 shadow-sm">
  <div class="card-header bg-white py-3">
    <div class="d-flex align-items-center gap-2">
      <i data-lucide="star" style="width:18px;height:18px;color:#fbbf24;"></i>
      <span class="fw-bold">Student Reviews You've Received</span>
    </div>
    <span class="badge bg-secondary"><?php echo count($myReviews); ?> recent</span>
  </div>
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      <?php foreach ($myReviews as $r): ?>
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
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

