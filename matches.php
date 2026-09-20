<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

// --- My skill lists ---
$stmt = $db->prepare("SELECT skill_id FROM user_skills WHERE user_id = ? AND type = 'teach'");
$stmt->execute([$userId]);
$myTeachIds = array_column($stmt->fetchAll(), 'skill_id');

$stmt = $db->prepare("SELECT skill_id FROM user_skills WHERE user_id = ? AND type = 'learn'");
$stmt->execute([$userId]);
$myLearnIds = array_column($stmt->fetchAll(), 'skill_id');

/**
 * Find other users who have a given type of skill within a given skill-id list,
 * grouped by user, with the matching skill names attached.
 */
function findUsersBySkills(PDO $db, array $skillIds, string $type, int $excludeUserId): array {
    if (empty($skillIds)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
    $sql = "SELECT u.user_id, u.full_name, u.bio, u.is_verified, u.profile_picture, s.skill_name, s.skill_id
            FROM user_skills us
            JOIN users u ON u.user_id = us.user_id
            JOIN skills s ON s.skill_id = us.skill_id
            WHERE us.type = ? AND us.skill_id IN ($placeholders) AND us.user_id != ?
            ORDER BY u.is_verified DESC, u.full_name";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([$type], $skillIds, [$excludeUserId]));

    $grouped = [];
    foreach ($stmt->fetchAll() as $row) {
        $uid = $row['user_id'];
        if (!isset($grouped[$uid])) {
            $grouped[$uid] = [
                'user_id' => $uid,
                'full_name' => $row['full_name'],
                'bio' => $row['bio'],
                'is_verified' => $row['is_verified'],
                'profile_picture' => $row['profile_picture'],
                'skills' => [],
            ];
        }
        $grouped[$uid]['skills'][] = ['skill_id' => $row['skill_id'], 'skill_name' => $row['skill_name']];
    }
    return $grouped;
}

// People who teach something I want to learn
$potentialTeachers = findUsersBySkills($db, $myLearnIds, 'teach', $userId);

// People who want to learn something I teach
$potentialStudents = findUsersBySkills($db, $myTeachIds, 'learn', $userId);

// Mutual matches: appear in both lists = a genuine two-way swap opportunity
$mutualIds = array_intersect(array_keys($potentialTeachers), array_keys($potentialStudents));
$mutualMatches = [];
$teachOnly = [];
$learnOnly = [];

foreach ($potentialTeachers as $uid => $data) {
    if (in_array($uid, $mutualIds, true)) {
        $mutualMatches[$uid] = [
            'user' => $data,
            'they_teach_me' => $data['skills'],
            'i_teach_them' => $potentialStudents[$uid]['skills'],
        ];
    } else {
        $teachOnly[$uid] = $data;
    }
}
foreach ($potentialStudents as $uid => $data) {
    if (!in_array($uid, $mutualIds, true)) {
        $learnOnly[$uid] = $data;
    }
}

$pageTitle = 'Find a Match';
require __DIR__ . '/includes/header.php';

function renderAvatarBadge($pic, $name) {
    if ($pic) {
        echo '<img src="/uploads/profile_pictures/' . htmlspecialchars($pic) . '" class="ss-avatar me-3" width="54" height="54" alt="' . htmlspecialchars($name) . '">';
    } else {
        $initial = strtoupper(substr($name, 0, 1));
        echo '<div class="d-inline-flex align-items-center justify-content-center rounded-circle me-3 text-white fw-bold shadow-sm" style="width:54px;height:54px;background:linear-gradient(135deg,var(--ss-primary) 0%,var(--ss-primary-hover) 100%);font-size:1.25rem;">' . htmlspecialchars($initial) . '</div>';
    }
}
?>

<!-- Page Header -->
<div class="ss-hero-card mb-4 animate-fade-in-up" style="padding: 1.75rem 2rem;">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index: 2;">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(255,255,255,0.2);">
          <i data-lucide="sparkles" style="width:18px;height:18px;color:#fff;"></i>
        </div>
        <h1 class="h3 mb-0 ss-hero-title">Skill Match Engine</h1>
      </div>
      <p class="ss-hero-subtitle mb-0">
        Connect with peer students whose abilities perfectly complement yours &mdash; find mutual 2-way swaps or discover trusted teachers.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="/profile.php" class="btn btn-sm px-3.5 py-2 text-nowrap" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.35);">
        <i data-lucide="sliders" style="width:14px;height:14px;"></i>
        <span>Adjust My Skills</span>
      </a>
    </div>
  </div>
</div>

<?php if (empty($myTeachIds) && empty($myLearnIds)): ?>
  <div class="ss-empty-state mb-5 animate-fade-in-up">
    <div class="ss-empty-icon">
      <i data-lucide="sparkles" style="width:28px;height:28px;"></i>
    </div>
    <h3 class="ss-empty-title">Your Skills List is Empty</h3>
    <p class="ss-empty-desc">
      To find ideal skill partners, add skills you can teach and skills you want to learn to your profile.
    </p>
    <a href="/profile.php" class="btn btn-primary">
      <i data-lucide="plus" style="width:16px;height:16px;"></i>
      <span>Add Skills to Profile</span>
    </a>
  </div>
<?php endif; ?>

<!-- ================================================================
     1. PERFECT SWAPS (MUTUAL MATCHES)
================================================================ -->
<div class="mb-5 animate-fade-in-up">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle p-2 d-inline-flex" style="background:var(--ss-primary-50);color:var(--ss-primary);">
        <i data-lucide="repeat" style="width:18px;height:18px;"></i>
      </div>
      <div>
        <h4 class="h5 fw-bold mb-0">Perfect Swaps (Mutual Matches)</h4>
        <small class="text-muted">You teach what they want, and they teach what you want &mdash; the ideal 2-way exchange.</small>
      </div>
    </div>
    <span class="badge bg-primary fs-6"><?php echo count($mutualMatches); ?></span>
  </div>

  <?php if (empty($mutualMatches)): ?>
    <div class="card p-4 text-center border-dashed">
      <p class="text-muted small mb-0">No mutual two-way matches found yet. Try adding more skills or check back soon!</p>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($mutualMatches as $m): ?>
        <div class="col-12 col-md-6">
          <div class="card h-100 card-interactive shadow-sm border-primary border-opacity-25"
               style="background: linear-gradient(180deg, #ffffff 0%, #f8faff 100%);">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
              
              <div>
                <!-- User Header -->
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <div class="d-flex align-items-center">
                    <?php renderAvatarBadge($m['user']['profile_picture'], $m['user']['full_name']); ?>
                    <div>
                      <h5 class="mb-0 fw-bold">
                        <a href="/view_profile.php?id=<?php echo (int)$m['user']['user_id']; ?>" class="text-dark text-decoration-none">
                          <?php echo clean($m['user']['full_name']); ?>
                        </a>
                      </h5>
                      <div class="mt-1">
                        <?php if ($m['user']['is_verified']): ?>
                          <span class="verified-badge" style="font-size:0.7rem;padding:0.15rem 0.5rem;">
                            <i data-lucide="shield-check" style="width:12px;height:12px;"></i> Verified
                          </span>
                        <?php else: ?>
                          <span class="badge bg-secondary" style="font-size:0.7rem;">Student</span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- 2-Way Exchange Box -->
                <div class="p-3 rounded-3 mb-3" style="background: #f1f5f9; border: 1px solid #cbd5e1;">
                  <div class="row g-2 align-items-center text-center">
                    <div class="col-5">
                      <span class="d-block fw-bold text-dark text-uppercase mb-1" style="font-size:0.72rem;letter-spacing:0.04em;">They Teach You</span>
                      <span class="badge bg-primary mt-1 text-wrap d-inline-block" style="font-size:0.85rem; color:#ffffff !important;"><?php echo clean(implode(', ', array_column($m['they_teach_me'], 'skill_name'))); ?></span>
                    </div>
                    <div class="col-2 text-center">
                      <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#ffffff;box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                        <i data-lucide="arrow-left-right" style="width:16px;height:16px;color:var(--ss-accent);"></i>
                      </div>
                    </div>
                    <div class="col-5">
                      <span class="d-block fw-bold text-dark text-uppercase mb-1" style="font-size:0.72rem;letter-spacing:0.04em;">You Teach Them</span>
                      <span class="badge bg-info mt-1 text-wrap d-inline-block" style="font-size:0.85rem; color:#ffffff !important;"><?php echo clean(implode(', ', array_column($m['i_teach_them'], 'skill_name'))); ?></span>
                    </div>
                  </div>
                </div>

                <?php if (!empty($m['user']['bio'])): ?>
                  <p class="text-dark small mb-3 text-truncate-2" style="color: #334155 !important;">
                    &ldquo;<?php echo clean($m['user']['bio']); ?>&rdquo;
                  </p>
                <?php endif; ?>
              </div>

              <div class="d-flex gap-2 pt-2 border-top">
                <a href="/request_form.php?to=<?php echo (int)$m['user']['user_id']; ?>" class="btn btn-primary btn-sm flex-fill">
                  <i data-lucide="send" style="width:14px;height:14px;"></i>
                  <span>Request Exchange</span>
                </a>
                <a href="/view_profile.php?id=<?php echo (int)$m['user']['user_id']; ?>" class="btn btn-outline-secondary btn-sm">
                  <span>Profile</span>
                </a>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ================================================================
     2. TEACHERS FOR YOU (THEY TEACH WHAT YOU WANT)
================================================================ -->
<div class="mb-5">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle p-2 d-inline-flex" style="background:var(--ss-primary-50);color:var(--ss-primary);">
        <i data-lucide="graduation-cap" style="width:18px;height:18px;"></i>
      </div>
      <div>
        <h4 class="h5 fw-bold mb-0">Teachers For You</h4>
        <small class="text-muted">Peers who teach skills currently on your learning wishlist.</small>
      </div>
    </div>
    <span class="badge bg-secondary fs-6"><?php echo count($teachOnly); ?></span>
  </div>

  <?php if (empty($teachOnly)): ?>
    <div class="card p-4 text-center border-dashed">
      <p class="text-muted small mb-0">No teachers found for your current learning skills list.</p>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($teachOnly as $t): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
              
              <div class="d-flex align-items-start gap-3 mb-3">
                <?php renderAvatarBadge($t['profile_picture'], $t['full_name']); ?>
                <div>
                  <h6 class="fw-bold mb-0">
                    <a href="/view_profile.php?id=<?php echo (int)$t['user_id']; ?>" class="text-dark text-decoration-none">
                      <?php echo clean($t['full_name']); ?>
                    </a>
                  </h6>
                  <?php if ($t['is_verified']): ?>
                    <span class="verified-badge mt-1" style="font-size:0.68rem;padding:0.1rem 0.45rem;">
                      <i data-lucide="shield-check" style="width:11px;height:11px;"></i> Verified
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="mb-3">
                <span class="d-block fw-bold text-dark small mb-1.5" style="color:#0f172a !important;">Teaches:</span>
                <div class="d-flex flex-wrap gap-1.5">
                  <?php foreach ($t['skills'] as $sk): ?>
                    <span class="badge bg-primary" style="color:#ffffff !important;"><?php echo clean($sk['skill_name']); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="d-flex gap-2">
                <a href="/request_form.php?to=<?php echo (int)$t['user_id']; ?>" class="btn btn-primary btn-sm flex-fill">
                  Request
                </a>
                <a href="/view_profile.php?id=<?php echo (int)$t['user_id']; ?>" class="btn btn-outline-secondary btn-sm">
                  View
                </a>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ================================================================
     3. STUDENTS INTERESTED IN YOUR SKILLS
================================================================ -->
<div>
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle p-2 d-inline-flex" style="background:var(--ss-accent-light);color:var(--ss-accent);">
        <i data-lucide="users" style="width:18px;height:18px;"></i>
      </div>
      <div>
        <h4 class="h5 fw-bold mb-0 text-dark">Students Interested In Your Skills</h4>
        <small class="text-secondary" style="color:#64748b !important;">Peers looking to learn topics that you have listed to teach.</small>
      </div>
    </div>
    <span class="badge bg-secondary fs-6"><?php echo count($learnOnly); ?></span>
  </div>

  <?php if (empty($learnOnly)): ?>
    <div class="card p-4 text-center border-dashed">
      <p class="text-muted small mb-0">No learners currently looking for your taught skills.</p>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($learnOnly as $l): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm border">
            <div class="card-body p-3 d-flex flex-column justify-content-between">
              
              <div class="d-flex align-items-start gap-3 mb-3">
                <?php renderAvatarBadge($l['profile_picture'], $l['full_name']); ?>
                <div>
                  <h6 class="fw-bold mb-0">
                    <a href="/view_profile.php?id=<?php echo (int)$l['user_id']; ?>" class="text-dark text-decoration-none">
                      <?php echo clean($l['full_name']); ?>
                    </a>
                  </h6>
                  <?php if ($l['is_verified']): ?>
                    <span class="verified-badge mt-1" style="font-size:0.68rem;padding:0.1rem 0.45rem;">
                      <i data-lucide="shield-check" style="width:11px;height:11px;"></i> Verified
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="mb-3">
                <span class="d-block fw-bold text-dark small mb-1.5" style="color:#0f172a !important;">Looking to learn:</span>
                <div class="d-flex flex-wrap gap-1.5">
                  <?php foreach ($l['skills'] as $sk): ?>
                    <span class="badge bg-info" style="color:#ffffff !important;"><?php echo clean($sk['skill_name']); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>

              <a href="/view_profile.php?id=<?php echo (int)$l['user_id']; ?>" class="btn btn-outline-secondary btn-sm w-100">
                View Student Profile
              </a>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

