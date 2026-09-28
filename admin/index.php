<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

$pendingCerts  = (int)$db->query("SELECT COUNT(*) c FROM certificates WHERE status = 'pending'")->fetch()['c'];
$totalUsers    = (int)$db->query("SELECT COUNT(*) c FROM users WHERE role = 'student'")->fetch()['c'];
$verifiedUsers = (int)$db->query("SELECT COUNT(*) c FROM users WHERE is_verified = 1")->fetch()['c'];
$totalRequests = (int)$db->query("SELECT COUNT(*) c FROM exchange_requests")->fetch()['c'];
$totalSessions = (int)$db->query("SELECT COUNT(*) c FROM sessions_schedule")->fetch()['c'];

// Recent pending certificates for quick review
$stmt = $db->query(
    "SELECT c.certificate_id, c.file_path, c.uploaded_at, u.full_name, u.email, s.skill_name
     FROM certificates c
     JOIN users u ON u.user_id = c.user_id
     JOIN skills s ON s.skill_id = c.skill_id
     WHERE c.status = 'pending'
     ORDER BY c.uploaded_at DESC LIMIT 3"
);
$quickReviewCerts = $stmt->fetchAll();

$pageTitle = 'Admin Console';
require __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="ss-page-header animate-fade-in-up">
  <div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-danger">Superadmin</span>
      <h1 class="h2 ss-page-title mb-0">Platform Administration Console</h1>
    </div>
    <p class="ss-page-subtitle">Real-time overview of platform users, student verifications, and exchange metrics.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="/admin/users.php" class="btn btn-outline-secondary btn-sm">
      <i data-lucide="users" style="width: 14px; height: 14px;"></i>
      <span>Manage Users</span>
    </a>
    <a href="/admin/reports.php" class="btn btn-primary btn-sm">
      <i data-lucide="bar-chart-3" style="width: 14px; height: 14px;"></i>
      <span>Reports &amp; Analytics</span>
    </a>
  </div>
</div>

<!-- KPI METRICS ROW -->
<div class="row g-3 mb-4 animate-fade-in-up">
  
  <!-- Pending Review Queue -->
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="stat-card stat-warning">
      <div class="stat-icon-wrapper">
        <i data-lucide="file-clock" style="width: 22px; height: 22px;"></i>
      </div>
      <div>
        <div class="stat-number"><?php echo $pendingCerts; ?></div>
        <p class="stat-label">Certificates Pending Review</p>
      </div>
      <div class="mt-3 pt-2 border-top">
        <a href="/admin/certificates.php" class="small fw-semibold text-warning text-decoration-none">
          Open Review Queue &rarr;
        </a>
      </div>
    </div>
  </div>

  <!-- Registered Students -->
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="stat-card stat-primary">
      <div class="stat-icon-wrapper">
        <i data-lucide="users" style="width: 22px; height: 22px;"></i>
      </div>
      <div>
        <div class="stat-number"><?php echo $totalUsers; ?></div>
        <p class="stat-label">Registered Students</p>
      </div>
      <div class="mt-3 pt-2 border-top">
        <a href="/admin/users.php" class="small fw-semibold text-primary text-decoration-none">
          Manage Accounts &rarr;
        </a>
      </div>
    </div>
  </div>

  <!-- Verified Teachers -->
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="stat-card stat-success">
      <div class="stat-icon-wrapper">
        <i data-lucide="shield-check" style="width: 22px; height: 22px;"></i>
      </div>
      <div>
        <div class="stat-number"><?php echo $verifiedUsers; ?></div>
        <p class="stat-label">Verified Teachers</p>
      </div>
      <div class="mt-3 pt-2 border-top">
        <span class="small text-muted">
          <?php echo ($totalUsers > 0) ? round(($verifiedUsers / $totalUsers) * 100) : 0; ?>% of student base
        </span>
      </div>
    </div>
  </div>

  <!-- Total Exchanges Created -->
  <div class="col-12 col-sm-6 col-lg-3">
    <div class="stat-card stat-accent">
      <div class="stat-icon-wrapper">
        <i data-lucide="arrow-left-right" style="width: 22px; height: 22px;"></i>
      </div>
      <div>
        <div class="stat-number"><?php echo $totalRequests; ?></div>
        <p class="stat-label">Exchange Proposals (<?php echo $totalSessions; ?> sessions)</p>
      </div>
      <div class="mt-3 pt-2 border-top">
        <a href="/admin/reports.php" class="small fw-semibold text-accent text-decoration-none">
          View Conversion Funnel &rarr;
        </a>
      </div>
    </div>
  </div>

</div>

<!-- ADMIN SECTIONS -->
<div class="row g-4">
  
  <!-- LEFT: Pending Verification Queue Snapshot -->
  <div class="col-12 col-lg-7">
    <div class="card shadow-sm h-100">
      <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="award" style="width: 20px; height: 20px; color: var(--ss-warning);"></i>
          <span class="fw-bold">Awaiting Certificate Verification</span>
        </div>
        <a href="/admin/certificates.php" class="text-primary small fw-semibold">View All (<?php echo $pendingCerts; ?>) &rarr;</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($quickReviewCerts)): ?>
          <div class="p-4 text-center">
            <div class="ss-empty-icon mx-auto" style="width:44px;height:44px;">
              <i data-lucide="check" style="width:20px;height:20px;"></i>
            </div>
            <p class="text-muted small mb-0">No certificates currently waiting for review!</p>
          </div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($quickReviewCerts as $qc): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                <div>
                  <div class="fw-bold text-dark"><?php echo clean($qc['full_name']); ?></div>
                  <div class="small text-muted">
                    Skill: <span class="badge bg-primary"><?php echo clean($qc['skill_name']); ?></span>
                    &bull; Submitted <?php echo clean(date('M j, Y', strtotime($qc['uploaded_at']))); ?>
                  </div>
                </div>
                <div class="d-flex gap-2">
                  <a href="/uploads/certificates/<?php echo clean($qc['file_path']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                    View File
                  </a>
                  <a href="/admin/certificates.php" class="btn btn-primary btn-sm">
                    Review
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RIGHT: Admin Navigation & Quick Controls -->
  <div class="col-12 col-lg-5">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white py-3">
        <span class="fw-bold">Administration Tools</span>
      </div>
      <div class="card-body p-3">
        <div class="d-grid gap-2">
          
          <a href="/admin/users.php" class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between text-dark text-decoration-none hover-shadow">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle p-2 bg-white text-primary shadow-xs">
                <i data-lucide="user-check" style="width: 20px; height: 20px;"></i>
              </div>
              <div>
                <div class="fw-bold">User Management</div>
                <div class="small text-muted">Search, suspend, activate, or promote admins</div>
              </div>
            </div>
            <i data-lucide="chevron-right" style="width: 18px; height: 18px; color: var(--ss-text-subtle);"></i>
          </a>

          <a href="/admin/certificates.php" class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between text-dark text-decoration-none hover-shadow">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle p-2 bg-white text-warning shadow-xs">
                <i data-lucide="file-check-2" style="width: 20px; height: 20px;"></i>
              </div>
              <div>
                <div class="fw-bold">Certificate Queue</div>
                <div class="small text-muted">Review proof documents and grant verified badges</div>
              </div>
            </div>
            <i data-lucide="chevron-right" style="width: 18px; height: 18px; color: var(--ss-text-subtle);"></i>
          </a>

          <a href="/admin/reports.php" class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between text-dark text-decoration-none hover-shadow">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle p-2 bg-white text-info shadow-xs">
                <i data-lucide="trending-up" style="width: 20px; height: 20px;"></i>
              </div>
              <div>
                <div class="fw-bold">Analytics &amp; Growth</div>
                <div class="small text-muted">Signups trend, skill demand, and platform funnels</div>
              </div>
            </div>
            <i data-lucide="chevron-right" style="width: 18px; height: 18px; color: var(--ss-text-subtle);"></i>
          </a>

        </div>
      </div>
    </div>
  </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

