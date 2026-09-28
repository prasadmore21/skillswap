<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

if ($search !== '') {
    $countStmt = $db->prepare("SELECT COUNT(*) c FROM users WHERE full_name LIKE ? OR email LIKE ?");
    $like = '%' . $search . '%';
    $countStmt->execute([$like, $like]);
    $totalUsers = (int)$countStmt->fetch()['c'];

    $stmt = $db->prepare(
        "SELECT u.*,
                (SELECT COUNT(*) FROM user_skills WHERE user_id = u.user_id AND type = 'teach') AS teach_count,
                (SELECT COUNT(*) FROM certificates WHERE user_id = u.user_id AND status = 'approved') AS approved_certs
         FROM users u
         WHERE u.full_name LIKE ? OR u.email LIKE ?
         ORDER BY u.created_at DESC
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute([$like, $like]);
} else {
    $totalUsers = (int)$db->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
    $stmt = $db->prepare(
        "SELECT u.*,
                (SELECT COUNT(*) FROM user_skills WHERE user_id = u.user_id AND type = 'teach') AS teach_count,
                (SELECT COUNT(*) FROM certificates WHERE user_id = u.user_id AND status = 'approved') AS approved_certs
         FROM users u ORDER BY u.created_at DESC
         LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute();
}
$users = $stmt->fetchAll();
$totalPages = max(1, (int)ceil($totalUsers / $perPage));

$pageTitle = 'Manage Users';
require __DIR__ . '/../includes/header.php';
?>

<!-- Page Header -->
<div class="ss-page-header animate-fade-in-up">
  <div>
    <div class="d-flex align-items-center gap-2">
      <a href="/admin/index.php" class="text-muted small fw-semibold text-decoration-none">
        <i data-lucide="arrow-left" style="width: 14px; height: 14px; display:inline-block;"></i> Admin Hub
      </a>
    </div>
    <h1 class="h2 ss-page-title mt-1">User Account Management</h1>
    <p class="ss-page-subtitle">Search, inspect credentials, moderate status, and manage platform administrator permissions.</p>
  </div>
  <div>
    <span class="badge bg-secondary fs-6"><?php echo $totalUsers; ?> Total Accounts</span>
  </div>
</div>

<!-- Search & Filter Bar -->
<div class="card shadow-sm mb-4 animate-fade-in-up">
  <div class="card-body p-3">
    <form class="row g-2 align-items-center" method="GET">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="input-group">
          <span class="input-group-text bg-white border-end-0 text-muted">
            <i data-lucide="search" style="width: 16px; height: 16px;"></i>
          </span>
          <input type="text" name="q" class="form-control border-start-0 ps-1"
                 placeholder="Search student by name or college email..."
                 value="<?php echo clean($search); ?>">
        </div>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      </div>
      <?php if ($search !== ''): ?>
        <div class="col-auto">
          <a href="/admin/users.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Users Table Card -->
<div class="card shadow-sm border-0 animate-fade-in-up">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>Student Profile</th>
          <th>Role</th>
          <th>Verification</th>
          <th>Account Status</th>
          <th>Joined Date</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr>
            <td colspan="6" class="text-center py-4 text-muted">
              No user accounts found matching your query.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-3">
                  <?php if (!empty($u['profile_picture'])): ?>
                    <img src="/uploads/profile_pictures/<?php echo clean($u['profile_picture']); ?>"
                         alt="Avatar" class="ss-avatar" width="40" height="40">
                  <?php else: ?>
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle text-white fw-bold shadow-xs"
                         style="width:40px;height:40px;background:var(--ss-primary);font-size:0.95rem;">
                      <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                    </div>
                  <?php endif; ?>
                  <div>
                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                      <a href="/view_profile.php?id=<?php echo (int)$u['user_id']; ?>" class="text-dark text-decoration-none">
                        <?php echo clean($u['full_name']); ?>
                      </a>
                      <?php if ($u['user_id'] == currentUserId()): ?>
                        <span class="badge bg-info" style="font-size:0.65rem;">You</span>
                      <?php endif; ?>
                    </div>
                    <div class="text-muted small"><?php echo clean($u['email']); ?></div>
                    <div class="text-muted" style="font-size:0.75rem;">
                      <?php echo (int)$u['teach_count']; ?> skill(s) &bull; <?php echo (int)$u['approved_certs']; ?> cert(s)
                    </div>
                  </div>
                </div>
              </td>

              <td>
                <span class="badge <?php echo $u['role'] === 'admin' ? 'bg-dark' : 'bg-secondary'; ?>">
                  <?php echo clean(ucfirst($u['role'])); ?>
                </span>
              </td>

              <td>
                <?php if ($u['is_verified']): ?>
                  <span class="verified-badge" style="font-size: 0.72rem; padding: 0.15rem 0.5rem;">
                    <i data-lucide="shield-check" style="width: 12px; height: 12px;"></i> Verified Teacher
                  </span>
                <?php else: ?>
                  <span class="text-muted small">&mdash;</span>
                <?php endif; ?>
              </td>

              <td>
                <span class="badge <?php echo $u['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                  <span class="status-dot <?php echo $u['status'] === 'active' ? 'active' : 'danger'; ?> me-1"></span>
                  <?php echo clean(ucfirst($u['status'])); ?>
                </span>
              </td>

              <td class="text-muted small">
                <?php echo clean(date('M j, Y', strtotime($u['created_at']))); ?>
              </td>

              <td class="text-end">
                <?php if ($u['user_id'] != currentUserId()): ?>
                  <form action="/admin/user_action.php" method="POST" class="d-inline-flex gap-1 justify-content-end">
                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                    
                    <?php if ($u['status'] === 'active'): ?>
                      <button type="submit" name="action" value="suspend" class="btn btn-sm btn-outline-danger"
                              onclick="return confirm('Suspend this user account? They will not be able to log in.');">
                        Suspend
                      </button>
                    <?php else: ?>
                      <button type="submit" name="action" value="activate" class="btn btn-sm btn-outline-success">
                        Reactivate
                      </button>
                    <?php endif; ?>

                    <?php if ($u['role'] === 'student'): ?>
                      <button type="submit" name="action" value="promote" class="btn btn-sm btn-outline-secondary"
                              onclick="return confirm('Promote this user to Administrator?');">
                        Make Admin
                      </button>
                    <?php else: ?>
                      <button type="submit" name="action" value="demote" class="btn btn-sm btn-outline-secondary"
                              onclick="return confirm('Revoke administrator permissions for this account?');">
                        Demote
                      </button>
                    <?php endif; ?>
                  </form>
                <?php else: ?>
                  <span class="text-muted small fst-italic">Active Admin</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Bar -->
  <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
      <small class="text-muted">
        Showing page <?php echo $page; ?> of <?php echo $totalPages; ?>
      </small>
      <div class="btn-group btn-group-sm">
        <?php if ($page > 1): ?>
          <a href="?q=<?php echo urlencode($search); ?>&p=<?php echo $page - 1; ?>" class="btn btn-outline-secondary">
            &laquo; Prev
          </a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?q=<?php echo urlencode($search); ?>&p=<?php echo $i; ?>"
             class="btn <?php echo ($i === $page) ? 'btn-primary' : 'btn-outline-secondary'; ?>">
            <?php echo $i; ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="?q=<?php echo urlencode($search); ?>&p=<?php echo $page + 1; ?>" class="btn btn-outline-secondary">
            Next &raquo;
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

