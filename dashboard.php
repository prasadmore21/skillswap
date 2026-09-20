<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

// Current user profile
$stmt = $db->prepare('SELECT * FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Skill counts
$stmt = $db->prepare("SELECT COUNT(*) c FROM user_skills WHERE user_id = ? AND type = 'teach'");
$stmt->execute([$userId]);
$teachCount = (int)$stmt->fetch()['c'];

$stmt = $db->prepare("SELECT COUNT(*) c FROM user_skills WHERE user_id = ? AND type = 'learn'");
$stmt->execute([$userId]);
$learnCount = (int)$stmt->fetch()['c'];

// Pending requests received
$stmt = $db->prepare("SELECT COUNT(*) c FROM exchange_requests WHERE receiver_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pendingReceived = (int)$stmt->fetch()['c'];

// Upcoming scheduled sessions
$stmt = $db->prepare(
    "SELECT COUNT(*) c FROM sessions_schedule ss JOIN exchange_requests r ON r.request_id = ss.request_id
     WHERE (r.sender_id = ? OR r.receiver_id = ?) AND ss.status = 'scheduled' AND ss.scheduled_date >= NOW()"
);
$stmt->execute([$userId, $userId]);
$upcomingSessions = (int)$stmt->fetch()['c'];

// Unread notifications
$stmt = $db->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$userId]);
$unreadNotifications = (int)$stmt->fetch()['c'];

// Pending certificates
$stmt = $db->prepare("SELECT COUNT(*) c FROM certificates WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pendingCerts = (int)$stmt->fetch()['c'];

// Recent pending requests received preview
$stmt = $db->prepare(
    "SELECT r.*, u.full_name AS sender_name, u.profile_picture AS sender_pic,
            os.skill_name AS offered_skill, rs.skill_name AS requested_skill
     FROM exchange_requests r
     JOIN users u ON u.user_id = r.sender_id
     JOIN skills os ON os.skill_id = r.offered_skill_id
     JOIN skills rs ON rs.skill_id = r.requested_skill_id
     WHERE r.receiver_id = ? AND r.status = 'pending'
     ORDER BY r.created_at DESC LIMIT 3"
);
$stmt->execute([$userId]);
$recentRequests = $stmt->fetchAll();

// Upcoming sessions preview
$stmt = $db->prepare(
    "SELECT ss.*, r.sender_id, r.receiver_id,
            os.skill_name AS offered_skill, rs.skill_name AS requested_skill,
            su.full_name AS sender_name, ru.full_name AS receiver_name
     FROM sessions_schedule ss
     JOIN exchange_requests r ON r.request_id = ss.request_id
     JOIN skills os ON os.skill_id = r.offered_skill_id
     JOIN skills rs ON rs.skill_id = r.requested_skill_id
     JOIN users su ON su.user_id = r.sender_id
     JOIN users ru ON ru.user_id = r.receiver_id
     WHERE (r.sender_id = ? OR r.receiver_id = ?) AND ss.status = 'scheduled' AND ss.scheduled_date >= NOW()
     ORDER BY ss.scheduled_date ASC LIMIT 3"
);
$stmt->execute([$userId, $userId]);
$recentUpcoming = $stmt->fetchAll();

// User skills preview
$stmt = $db->prepare(
    "SELECT us.type, us.proficiency, s.skill_name
     FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.user_id = ? ORDER BY us.type ASC, s.skill_name ASC LIMIT 6"
);
$stmt->execute([$userId]);
$dashboardSkills = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<!-- ================================================================
     DASHBOARD RADIANT HERO CARD
================================================================ -->
<div class="ss-hero-card animate-fade-in-up">
  <div class="row align-items-center g-4 position-relative" style="z-index: 2;">
    <div class="col-12 col-lg-8">
      <div class="d-flex align-items-center gap-3">
        <?php if (!empty($user['profile_picture'])): ?>
          <img src="/uploads/profile_pictures/<?php echo clean($user['profile_picture']); ?>"
               alt="Profile" class="ss-avatar" width="68" height="68" style="border: 3px solid rgba(255,255,255,0.85); box-shadow: 0 8px 24px rgba(0,0,0,0.2);">
        <?php else: ?>
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
               style="width:68px;height:68px;background:rgba(255,255,255,0.2);backdrop-filter:blur(10px);border:2px solid rgba(255,255,255,0.4);color:#fff;font-size:1.6rem;font-weight:800;font-family:'Sora',sans-serif;box-shadow:0 8px 24px rgba(0,0,0,0.15);">
            <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
          </div>
        <?php endif; ?>
        <div>
          <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h1 class="h2 mb-0 ss-hero-title">
              Welcome back, <?php echo clean($_SESSION['full_name']); ?>!
            </h1>
            <?php if (!empty($user['is_verified'])): ?>
              <span class="verified-badge">
                <i data-lucide="shield-check" style="width:14px;height:14px;"></i> Verified Teacher
              </span>
            <?php else: ?>
              <span class="badge" style="background:rgba(255,255,255,0.2);color:#ffffff;border:1px solid rgba(255,255,255,0.3);font-size:0.75rem;">
                Student Member
              </span>
            <?php endif; ?>
          </div>
          <p class="ss-hero-subtitle mb-0">
            Discover peer partners, exchange knowledge freely, and expand your skillset through verified collaborative learning.
          </p>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4 text-lg-end">
      <div class="d-flex gap-2 justify-content-lg-end flex-wrap">
        <a href="/matches.php" class="btn btn-accent shadow-sm px-3.5 py-2.5">
          <i data-lucide="sparkles" style="width:17px;height:17px;"></i>
          <span>Find Skill Match</span>
        </a>
        <a href="/profile.php" class="btn px-3.5 py-2.5" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);">
          <i data-lucide="user" style="width:17px;height:17px;"></i>
          <span>My Profile</span>
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ================================================================
     UNVERIFIED TEACHER NOTICE
================================================================ -->
<?php if (!$user['is_verified'] && $teachCount > 0): ?>
  <div class="card mb-4 border-primary border-opacity-25" style="background: linear-gradient(135deg, #eef5ff 0%, #ffffff 100%);">
    <div class="card-body p-3 p-md-4">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle p-2 d-inline-flex text-primary" style="background: rgba(51, 102, 255, 0.12);">
            <i data-lucide="award" style="width: 24px; height: 24px;"></i>
          </div>
          <div>
            <h5 class="mb-1 fw-bold text-dark">Get Your Verified Teacher Badge</h5>
            <p class="text-muted small mb-0">
              You've listed <strong><?php echo $teachCount; ?> skill<?php echo $teachCount === 1 ? '' : 's'; ?></strong> to teach! Submit course certificates to earn student trust and boost swap requests.
            </p>
          </div>
        </div>
        <div>
          <a href="/certificates.php" class="btn btn-primary btn-sm text-nowrap">
            <i data-lucide="upload" style="width: 14px; height: 14px;"></i>
            <span>Upload Certificate</span>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- ================================================================
     KEY KPI METRICS
================================================================ -->
<div class="row g-3 mb-4">
  <!-- Skills Count -->
  <div class="col-6 col-lg-4 col-xl-2">
    <a href="/profile.php" class="text-decoration-none">
      <div class="stat-card stat-primary">
        <div class="stat-icon-wrapper">
          <i data-lucide="book-open" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="stat-number"><?php echo $teachCount; ?> <span class="text-muted fs-6 fw-normal">/ <?php echo $learnCount; ?></span></div>
          <p class="stat-label">Teach / Learn Skills</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Pending Requests -->
  <div class="col-6 col-lg-4 col-xl-2">
    <a href="/exchange_requests.php" class="text-decoration-none">
      <div class="stat-card stat-warning">
        <div class="stat-icon-wrapper">
          <i data-lucide="arrow-left-right" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="stat-number"><?php echo $pendingReceived; ?></div>
          <p class="stat-label">Pending Requests</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Upcoming Sessions -->
  <div class="col-6 col-lg-4 col-xl-2">
    <a href="/sessions.php" class="text-decoration-none">
      <div class="stat-card stat-success">
        <div class="stat-icon-wrapper">
          <i data-lucide="calendar" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="stat-number"><?php echo $upcomingSessions; ?></div>
          <p class="stat-label">Upcoming Sessions</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Unread Notifications -->
  <div class="col-6 col-lg-4 col-xl-2">
    <a href="/notifications.php" class="text-decoration-none">
      <div class="stat-card stat-accent">
        <div class="stat-icon-wrapper">
          <i data-lucide="bell" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="stat-number"><?php echo $unreadNotifications; ?></div>
          <p class="stat-label">Notifications</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Certificates Under Review -->
  <div class="col-6 col-lg-4 col-xl-2">
    <a href="/certificates.php" class="text-decoration-none">
      <div class="stat-card stat-primary">
        <div class="stat-icon-wrapper">
          <i data-lucide="file-check" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="stat-number"><?php echo $pendingCerts; ?></div>
          <p class="stat-label">Pending Reviews</p>
        </div>
      </div>
    </a>
  </div>

  <!-- Find a Match Shortcut -->
  <div class="col-6 col-lg-4 col-xl-2">
    <a href="/matches.php" class="text-decoration-none">
      <div class="stat-card" style="border-color: var(--ss-accent); background: linear-gradient(180deg, #fff 0%, #fff7ed 100%);">
        <div class="stat-icon-wrapper" style="background: var(--ss-accent-light); color: var(--ss-accent);">
          <i data-lucide="search" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="stat-number text-dark" style="font-size:1.4rem;">Explore</div>
          <p class="stat-label" style="color:var(--ss-accent); font-weight:600;">Match Engine &rarr;</p>
        </div>
      </div>
    </a>
  </div>
</div>

<!-- ================================================================
     DASHBOARD CORE WORKFLOW GRIDS
================================================================ -->
<div class="row g-4">

  <!-- LEFT COLUMN: Upcoming Sessions & Pending Requests -->
  <div class="col-12 col-lg-7">

    <!-- Upcoming Sessions Panel -->
    <div class="card mb-4">
      <div class="card-header">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="calendar" style="width: 18px; height: 18px; color: var(--ss-primary);"></i>
          <span>Upcoming Sessions</span>
        </div>
        <a href="/sessions.php" class="text-primary small fw-semibold">View All &rarr;</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentUpcoming)): ?>
          <div class="p-4 text-center">
            <div class="ss-empty-icon mx-auto" style="width: 44px; height: 44px;">
              <i data-lucide="calendar-x" style="width: 20px; height: 20px;"></i>
            </div>
            <p class="text-muted small mb-2">No sessions scheduled right now.</p>
            <a href="/exchange_requests.php" class="btn btn-outline-primary btn-sm">Schedule from an accepted request</a>
          </div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($recentUpcoming as $s): ?>
              <?php $partner = ($s['sender_id'] == $userId) ? $s['receiver_name'] : $s['sender_name']; ?>
              <div class="list-group-item d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 py-3 px-4">
                <div>
                  <div class="fw-bold text-dark">
                    With <?php echo clean($partner); ?>
                  </div>
                  <div class="text-muted small mt-1">
                    <span class="badge bg-primary me-1"><?php echo clean($s['requested_skill']); ?></span>
                    &harr;
                    <span class="badge bg-secondary ms-1"><?php echo clean($s['offered_skill']); ?></span>
                  </div>
                  <div class="text-muted small mt-1">
                    <i data-lucide="clock" style="width: 13px; height: 13px; display: inline-block;"></i>
                    <?php echo clean(date('D, M j &bull; g:i A', strtotime($s['scheduled_date']))); ?>
                    &middot;
                    <span class="text-capitalize"><?php echo clean($s['mode']); ?></span>
                  </div>
                </div>
                <div class="text-sm-end">
                  <a href="/sessions.php" class="btn btn-outline-primary btn-sm">Details</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Pending Exchange Requests Received -->
    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="inbox" style="width: 18px; height: 18px; color: var(--ss-warning);"></i>
          <span>Pending Exchange Requests</span>
        </div>
        <a href="/exchange_requests.php" class="text-primary small fw-semibold">View All &rarr;</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentRequests)): ?>
          <div class="p-4 text-center">
            <p class="text-muted small mb-2">No pending requests waiting for your response.</p>
            <a href="/matches.php" class="btn btn-outline-primary btn-sm">Discover partners to connect with</a>
          </div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($recentRequests as $r): ?>
              <div class="list-group-item py-3 px-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold text-dark"><?php echo clean($r['sender_name']); ?></span>
                  <span class="badge bg-warning">Pending Review</span>
                </div>
                <p class="text-muted small mb-2">
                  Wants to learn <strong><?php echo clean($r['requested_skill']); ?></strong> &bull; Offers to teach <strong><?php echo clean($r['offered_skill']); ?></strong>
                </p>
                <?php if ($r['message']): ?>
                  <div class="small p-2 rounded bg-light border mb-2 text-secondary fst-italic">
                    &ldquo;<?php echo clean($r['message']); ?>&rdquo;
                  </div>
                <?php endif; ?>
                <div class="d-flex gap-2 mt-2">
                  <form action="/request_action.php" method="POST" class="d-inline">
                    <input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>">
                    <button type="submit" name="action" value="accept" class="btn btn-sm btn-success">Accept</button>
                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger">Decline</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- RIGHT COLUMN: Skills Summary & Quick Actions -->
  <div class="col-12 col-lg-5">

    <!-- Quick Action Launchpad -->
    <div class="card mb-4">
      <div class="card-header">
        <span>Quick Actions</span>
      </div>
      <div class="card-body">
        <div class="d-grid gap-3">
          <a href="/matches.php" class="btn-action-launch text-decoration-none d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;background:linear-gradient(135deg,#3366ff 0%,#182b8f 100%);color:#fff;box-shadow:0 4px 14px rgba(51,102,255,0.3);">
                <i data-lucide="sparkles" style="width: 20px; height: 20px;"></i>
              </div>
              <div>
                <div class="fw-bold text-dark">Explore Skill Matches</div>
                <div class="small text-muted">Connect with compatible peers</div>
              </div>
            </div>
            <i data-lucide="chevron-right" style="width: 18px; height: 18px; color: #94a3b8;"></i>
          </a>

          <a href="/profile.php" class="btn-action-launch text-decoration-none d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;background:linear-gradient(135deg,#0284c7 0%,#0369a1 100%);color:#fff;box-shadow:0 4px 14px rgba(2,132,199,0.3);">
                <i data-lucide="plus-circle" style="width: 20px; height: 20px;"></i>
              </div>
              <div>
                <div class="fw-bold text-dark">Manage My Skills</div>
                <div class="small text-muted">Add skills to teach or learn</div>
              </div>
            </div>
            <i data-lucide="chevron-right" style="width: 18px; height: 18px; color: #94a3b8;"></i>
          </a>

          <a href="/certificates.php" class="btn-action-launch text-decoration-none d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;background:linear-gradient(135deg,#f97316 0%,#ea580c 100%);color:#fff;box-shadow:0 4px 14px rgba(249,115,22,0.3);">
                <i data-lucide="award" style="width: 20px; height: 20px;"></i>
              </div>
              <div>
                <div class="fw-bold text-dark">Upload Verification Proof</div>
                <div class="small text-muted">Get your Verified Teacher badge</div>
              </div>
            </div>
            <i data-lucide="chevron-right" style="width: 18px; height: 18px; color: #94a3b8;"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- My Listed Skills Preview -->
    <div class="card mb-4">
      <div class="card-header">
        <span>My Skills Snapshot</span>
        <a href="/profile.php" class="text-primary small fw-semibold">Edit Skills &rarr;</a>
      </div>
      <div class="card-body">
        <?php if (empty($dashboardSkills)): ?>
          <p class="text-muted small mb-2">You haven't listed any skills yet.</p>
          <a href="/profile.php" class="btn btn-outline-primary btn-sm">Add your first skill</a>
        <?php else: ?>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($dashboardSkills as $sk): ?>
              <?php if ($sk['type'] === 'teach'): ?>
                <span class="badge bg-primary">
                  <i data-lucide="graduation-cap" style="width:12px;height:12px;"></i>
                  Teaches: <?php echo clean($sk['skill_name']); ?>
                </span>
              <?php else: ?>
                <span class="badge bg-info">
                  <i data-lucide="sparkles" style="width:12px;height:12px;"></i>
                  Learns: <?php echo clean($sk['skill_name']); ?>
                </span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Admin Panel Access if Admin -->
    <?php if (isAdmin()): ?>
      <div class="card border-dark border-opacity-25" style="background: #0f172a; color: #fff;">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <span class="badge bg-danger mb-2">Admin Portal</span>
              <h5 class="text-white mb-1">Administrative Console</h5>
              <p class="text-secondary small mb-0">Moderate users, review certificates, and view platform metrics.</p>
            </div>
            <a href="/admin/index.php" class="btn btn-primary btn-sm text-nowrap">Open Console &rarr;</a>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

