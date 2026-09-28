<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

// --- User overview ---
$userStats = $db->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(status = 'suspended') AS suspended,
        SUM(is_verified = 1) AS verified
     FROM users WHERE role = 'student'"
)->fetch();

// --- Signups per month (last 6 months) ---
$signupsByMonth = $db->query(
    "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS total
     FROM users
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY ym ORDER BY ym DESC"
)->fetchAll();

// --- Most in-demand skills (top 5 each way) ---
$topTeach = $db->query(
    "SELECT s.skill_name, COUNT(*) AS total FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.type = 'teach' GROUP BY s.skill_id ORDER BY total DESC LIMIT 5"
)->fetchAll();

$topLearn = $db->query(
    "SELECT s.skill_name, COUNT(*) AS total FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.type = 'learn' GROUP BY s.skill_id ORDER BY total DESC LIMIT 5"
)->fetchAll();
$maxTeachCount = max(array_column($topTeach, 'total') ?: [1]);
$maxLearnCount = max(array_column($topLearn, 'total') ?: [1]);

// --- Exchange request funnel ---
$requestStats = $db->query(
    "SELECT status, COUNT(*) AS total FROM exchange_requests GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$requestStatuses = ['pending', 'accepted', 'rejected', 'cancelled', 'completed'];
$totalRequests = array_sum($requestStats ?: []);

// --- Session funnel ---
$sessionStats = $db->query(
    "SELECT status, COUNT(*) AS total FROM sessions_schedule GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$sessionStatuses = ['scheduled', 'completed', 'cancelled'];
$totalSessions = array_sum($sessionStats ?: []);

// --- Certificate funnel ---
$certStats = $db->query(
    "SELECT status, COUNT(*) AS total FROM certificates GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$certStatuses = ['pending', 'approved', 'rejected'];
$totalCerts = array_sum($certStats ?: []);

// --- Ratings overview ---
$ratingOverview = $db->query(
    "SELECT COUNT(*) AS total, AVG(rating) AS avg_rating FROM ratings"
)->fetch();
$avgRating = (float)($ratingOverview['avg_rating'] ?? 0);
$ratingCount = (int)($ratingOverview['total'] ?? 0);

$pageTitle = 'Reports & Analytics';
require __DIR__ . '/../includes/header.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
  <div>
    <h2 class="h3 fw-bold text-gray-900 mb-1 font-display">Reports &amp; Analytics</h2>
    <p class="text-muted small mb-0">Real-time metrics, skill demand distributions, conversion funnels, and community quality insights.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="/admin/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
      <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Admin Console
    </a>
  </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="w-10 h-10 rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-center flex-shrink-0" style="width:42px;height:42px;">
          <i data-lucide="users" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="text-muted small fw-medium">Total Students</div>
          <div class="h4 fw-bold text-gray-900 mb-0 font-display"><?php echo (int)$userStats['total']; ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="w-10 h-10 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-center flex-shrink-0" style="width:42px;height:42px;">
          <i data-lucide="user-check" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="text-muted small fw-medium">Active Accounts</div>
          <div class="h4 fw-bold text-success mb-0 font-display"><?php echo (int)$userStats['active']; ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="w-10 h-10 rounded-3 bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-center flex-shrink-0" style="width:42px;height:42px;">
          <i data-lucide="shield-check" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="text-muted small fw-medium">Verified Teachers</div>
          <div class="h4 fw-bold text-warning-emphasis mb-0 font-display"><?php echo (int)$userStats['verified']; ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="w-10 h-10 rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-center flex-shrink-0" style="width:42px;height:42px;">
          <i data-lucide="user-x" style="width:20px;height:20px;"></i>
        </div>
        <div>
          <div class="text-muted small fw-medium">Suspended</div>
          <div class="h4 fw-bold text-danger mb-0 font-display"><?php echo (int)$userStats['suspended']; ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Skill Distribution Row -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="book-open" class="text-primary" style="width:18px;height:18px;"></i>
          <h5 class="fw-bold mb-0 font-display fs-6">Most Taught Skills</h5>
        </div>
        <span class="badge bg-primary-subtle text-primary small">Supply</span>
      </div>
      <div class="card-body p-4">
        <?php if (empty($topTeach)): ?>
          <p class="text-muted text-center py-4 mb-0">No teaching skills listed yet.</p>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($topTeach as $s): ?>
              <?php $pct = round(($s['total'] / $maxTeachCount) * 100); ?>
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold text-dark fs-7"><?php echo clean($s['skill_name']); ?></span>
                  <span class="badge bg-light text-dark border small"><?php echo (int)$s['total']; ?> student<?php echo $s['total'] > 1 ? 's' : ''; ?></span>
                </div>
                <div class="progress" style="height: 8px; border-radius: 9999px; background-color: #f1f5f9;">
                  <div class="progress-bar" role="progressbar" style="width: <?php echo $pct; ?>%; background: linear-gradient(90deg, #3366ff, #598eff); border-radius: 9999px;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="target" class="text-accent" style="width:18px;height:18px;"></i>
          <h5 class="fw-bold mb-0 font-display fs-6">Most Wanted Skills</h5>
        </div>
        <span class="badge bg-warning-subtle text-warning-emphasis small">Demand</span>
      </div>
      <div class="card-body p-4">
        <?php if (empty($topLearn)): ?>
          <p class="text-muted text-center py-4 mb-0">No wanted skills listed yet.</p>
        <?php else: ?>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($topLearn as $s): ?>
              <?php $pct = round(($s['total'] / $maxLearnCount) * 100); ?>
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold text-dark fs-7"><?php echo clean($s['skill_name']); ?></span>
                  <span class="badge bg-light text-dark border small"><?php echo (int)$s['total']; ?> seeker<?php echo $s['total'] > 1 ? 's' : ''; ?></span>
                </div>
                <div class="progress" style="height: 8px; border-radius: 9999px; background-color: #f1f5f9;">
                  <div class="progress-bar" role="progressbar" style="width: <?php echo $pct; ?>%; background: linear-gradient(90deg, #f97316, #fb923c); border-radius: 9999px;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Funnels Row -->
<div class="row g-4 mb-4">
  <!-- Exchange Requests Funnel -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
        <h6 class="fw-bold mb-0 font-display">Exchange Requests</h6>
        <span class="badge bg-light text-dark border"><?php echo $totalRequests; ?> total</span>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($requestStatuses as $st): ?>
            <?php 
              $val = (int)($requestStats[$st] ?? 0); 
              $badgeClass = match($st) {
                'accepted', 'completed' => 'bg-success-subtle text-success',
                'pending' => 'bg-warning-subtle text-warning-emphasis',
                'rejected', 'cancelled' => 'bg-secondary-subtle text-secondary',
                default => 'bg-light text-dark'
              };
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5">
              <span class="text-capitalize text-muted small fw-medium"><?php echo clean($st); ?></span>
              <span class="badge <?php echo $badgeClass; ?> rounded-pill px-2.5"><?php echo $val; ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- Sessions Funnel -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
        <h6 class="fw-bold mb-0 font-display">Sessions Scheduled</h6>
        <span class="badge bg-light text-dark border"><?php echo $totalSessions; ?> total</span>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($sessionStatuses as $st): ?>
            <?php 
              $val = (int)($sessionStats[$st] ?? 0); 
              $badgeClass = match($st) {
                'completed' => 'bg-success-subtle text-success',
                'scheduled' => 'bg-primary-subtle text-primary',
                'cancelled' => 'bg-secondary-subtle text-secondary',
                default => 'bg-light text-dark'
              };
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5">
              <span class="text-capitalize text-muted small fw-medium"><?php echo clean($st); ?></span>
              <span class="badge <?php echo $badgeClass; ?> rounded-pill px-2.5"><?php echo $val; ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <!-- Certificates Funnel -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
        <h6 class="fw-bold mb-0 font-display">Certificates</h6>
        <span class="badge bg-light text-dark border"><?php echo $totalCerts; ?> total</span>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($certStatuses as $st): ?>
            <?php 
              $val = (int)($certStats[$st] ?? 0); 
              $badgeClass = match($st) {
                'approved' => 'bg-success-subtle text-success',
                'pending' => 'bg-warning-subtle text-warning-emphasis',
                'rejected' => 'bg-danger-subtle text-danger',
                default => 'bg-light text-dark'
              };
            ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-2.5">
              <span class="text-capitalize text-muted small fw-medium"><?php echo clean($st); ?></span>
              <span class="badge <?php echo $badgeClass; ?> rounded-pill px-2.5"><?php echo $val; ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Signups & Rating Overview Row -->
<div class="row g-4">
  <!-- Signups Over Time -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="trending-up" class="text-primary" style="width:18px;height:18px;"></i>
          <h5 class="fw-bold mb-0 font-display fs-6">User Signups (Last 6 Months)</h5>
        </div>
      </div>
      <div class="card-body p-0">
        <?php if (empty($signupsByMonth)): ?>
          <div class="p-4 text-center text-muted small">No signup data recorded in this period.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
              <thead class="bg-light fs-7 text-uppercase text-muted border-bottom">
                <tr>
                  <th class="ps-4 py-2.5">Month</th>
                  <th class="py-2.5">Registrations</th>
                  <th class="pe-4 py-2.5 text-end">Relative Volume</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                  $maxMonthTotal = max(array_column($signupsByMonth, 'total') ?: [1]); 
                  foreach ($signupsByMonth as $row): 
                    $monthPct = round(($row['total'] / $maxMonthTotal) * 100);
                ?>
                  <tr>
                    <td class="ps-4 py-3 fw-semibold text-dark">
                      <?php echo clean(date('F Y', strtotime($row['ym'] . '-01'))); ?>
                    </td>
                    <td class="py-3">
                      <span class="badge bg-primary-subtle text-primary fw-bold px-2.5 py-1">
                        <?php echo (int)$row['total']; ?> new student<?php echo $row['total'] > 1 ? 's' : ''; ?>
                      </span>
                    </td>
                    <td class="pe-4 py-3 text-end" style="width: 35%;">
                      <div class="progress" style="height: 6px; border-radius: 9999px; background-color: #f1f5f9;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $monthPct; ?>%; border-radius: 9999px;"></div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Overall Ratings Card -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-between">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="star" class="text-warning" style="width:18px;height:18px;"></i>
          <h5 class="fw-bold mb-0 font-display fs-6">Quality Rating</h5>
        </div>
      </div>
      <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
        <?php if ($ratingCount === 0): ?>
          <div class="w-12 h-12 rounded-circle bg-light text-muted d-inline-flex align-items-center justify-center mx-auto mb-2" style="width:48px;height:48px;">
            <i data-lucide="star-off" style="width:24px;height:24px;"></i>
          </div>
          <h6 class="fw-bold text-dark mb-1">No Ratings Yet</h6>
          <p class="text-muted small mb-0">Ratings will appear here once students complete and evaluate their exchange sessions.</p>
        <?php else: ?>
          <div class="display-4 fw-black text-gray-900 mb-1 font-display"><?php echo number_format($avgRating, 1); ?></div>
          <div class="text-warning fs-5 mb-2">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <?php if ($i <= round($avgRating)): ?>
                ★
              <?php else: ?>
                ☆
              <?php endif; ?>
            <?php endfor; ?>
          </div>
          <p class="text-muted small mb-0">
            Average satisfaction calculated from <strong class="text-dark"><?php echo $ratingCount; ?></strong> peer review<?php echo $ratingCount > 1 ? 's' : ''; ?> across completed sessions.
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
