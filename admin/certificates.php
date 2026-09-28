<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$db = getDB();

$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT c.certificate_id, c.file_path, c.status, c.uploaded_at, c.admin_remarks,
               u.user_id, u.full_name, u.email, s.skill_name
        FROM certificates c
        JOIN users u ON u.user_id = c.user_id
        JOIN skills s ON s.skill_id = c.skill_id";

$params = [];
if (in_array($filter, ['pending', 'approved', 'rejected'], true)) {
    $sql .= " WHERE c.status = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY (c.status = 'pending') DESC, c.uploaded_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$certificates = $stmt->fetchAll();

// Counts for filter pills
$countPending = (int)$db->query("SELECT COUNT(*) FROM certificates WHERE status = 'pending'")->fetchColumn();
$countApproved = (int)$db->query("SELECT COUNT(*) FROM certificates WHERE status = 'approved'")->fetchColumn();
$countRejected = (int)$db->query("SELECT COUNT(*) FROM certificates WHERE status = 'rejected'")->fetchColumn();
$countTotal = $countPending + $countApproved + $countRejected;

$pageTitle = 'Certificate Review Queue';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
  <div>
    <div class="d-flex align-items-center gap-2 mb-1">
      <h2 class="h3 fw-bold text-gray-900 mb-0 font-display">Certificate Review Queue</h2>
      <?php if ($countPending > 0): ?>
        <span class="badge bg-warning text-dark fw-semibold rounded-pill px-2.5 py-1">
          <?php echo $countPending; ?> pending
        </span>
      <?php endif; ?>
    </div>
    <p class="text-muted small mb-0">Review submitted student credentials. Approving a certificate automatically bestows the Verified Teacher badge.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="/admin/index.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
      <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Back to Console
    </a>
  </div>
</div>

<!-- Stats / Filters Row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="/admin/certificates.php?filter=all" class="text-decoration-none">
      <div class="card h-100 border-0 shadow-sm <?php echo $filter === 'all' ? 'border-primary border-2 ring-1' : ''; ?>">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="w-10 h-10 rounded-3 bg-light text-primary d-flex align-items-center justify-center flex-shrink-0" style="width:40px;height:40px;">
            <i data-lucide="file-text" style="width:20px;height:20px;"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">All Submissions</div>
            <div class="h5 fw-bold text-dark mb-0 font-display"><?php echo $countTotal; ?></div>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-6 col-md-3">
    <a href="/admin/certificates.php?filter=pending" class="text-decoration-none">
      <div class="card h-100 border-0 shadow-sm <?php echo $filter === 'pending' ? 'border-warning border-2' : ''; ?>">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="w-10 h-10 rounded-3 bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-center flex-shrink-0" style="width:40px;height:40px;">
            <i data-lucide="clock" style="width:20px;height:20px;"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Pending Review</div>
            <div class="h5 fw-bold text-warning-emphasis mb-0 font-display"><?php echo $countPending; ?></div>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-6 col-md-3">
    <a href="/admin/certificates.php?filter=approved" class="text-decoration-none">
      <div class="card h-100 border-0 shadow-sm <?php echo $filter === 'approved' ? 'border-success border-2' : ''; ?>">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="w-10 h-10 rounded-3 bg-success-subtle text-success d-flex align-items-center justify-center flex-shrink-0" style="width:40px;height:40px;">
            <i data-lucide="shield-check" style="width:20px;height:20px;"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Approved</div>
            <div class="h5 fw-bold text-success mb-0 font-display"><?php echo $countApproved; ?></div>
          </div>
        </div>
      </div>
    </a>
  </div>

  <div class="col-6 col-md-3">
    <a href="/admin/certificates.php?filter=rejected" class="text-decoration-none">
      <div class="card h-100 border-0 shadow-sm <?php echo $filter === 'rejected' ? 'border-danger border-2' : ''; ?>">
        <div class="card-body p-3 d-flex align-items-center gap-3">
          <div class="w-10 h-10 rounded-3 bg-danger-subtle text-danger d-flex align-items-center justify-center flex-shrink-0" style="width:40px;height:40px;">
            <i data-lucide="x-circle" style="width:20px;height:20px;"></i>
          </div>
          <div>
            <div class="text-muted small fw-medium">Rejected</div>
            <div class="h5 fw-bold text-danger mb-0 font-display"><?php echo $countRejected; ?></div>
          </div>
        </div>
      </div>
    </a>
  </div>
</div>

<!-- Table Card -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <?php if (empty($certificates)): ?>
      <div class="p-5 text-center">
        <div class="w-12 h-12 rounded-circle bg-light text-muted d-inline-flex align-items-center justify-center mb-3" style="width:48px;height:48px;">
          <i data-lucide="folder-check" style="width:24px;height:24px;"></i>
        </div>
        <h5 class="fw-bold text-dark">Queue is empty</h5>
        <p class="text-muted small mb-0">No certificate submissions found for the selected filter.</p>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle table-hover mb-0">
          <thead class="bg-light text-uppercase fs-7 text-muted border-bottom">
            <tr>
              <th class="ps-4 py-3">Student</th>
              <th class="py-3">Subject Skill</th>
              <th class="py-3">Certificate Document</th>
              <th class="py-3">Status</th>
              <th class="py-3">Submitted</th>
              <th class="pe-4 py-3 text-end">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php foreach ($certificates as $c): ?>
              <tr>
                <td class="ps-4 py-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="w-9 h-9 rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-center flex-shrink-0 font-display" style="width:36px;height:36px;font-size:0.875rem;">
                      <?php echo strtoupper(substr($c['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                      <div class="fw-semibold text-dark"><?php echo clean($c['full_name']); ?></div>
                      <a href="/view_profile.php?id=<?php echo (int)$c['user_id']; ?>" class="text-muted small text-decoration-none hover-primary">
                        <?php echo clean($c['email']); ?> &bull; View profile &rarr;
                      </a>
                    </div>
                  </div>
                </td>
                <td class="py-3">
                  <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-medium">
                    <i data-lucide="award" class="me-1 text-primary" style="width:13px;height:13px;display:inline-block;vertical-align:-2px;"></i>
                    <?php echo clean($c['skill_name']); ?>
                  </span>
                </td>
                <td class="py-3">
                  <a href="/uploads/certificates/<?php echo clean($c['file_path']); ?>" target="_blank" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                    <i data-lucide="external-link" style="width:13px;height:13px;"></i> View Document
                  </a>
                </td>
                <td class="py-3">
                  <?php if ($c['status'] === 'approved'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                      <i data-lucide="check-circle-2" style="width:12px;height:12px;"></i> Approved
                    </span>
                  <?php elseif ($c['status'] === 'rejected'): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                      <i data-lucide="x-circle" style="width:12px;height:12px;"></i> Rejected
                    </span>
                  <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5 rounded-pill fw-medium d-inline-flex align-items-center gap-1">
                      <i data-lucide="clock" style="width:12px;height:12px;"></i> Pending Review
                    </span>
                  <?php endif; ?>
                  
                  <?php if (!empty($c['admin_remarks'])): ?>
                    <div class="text-muted small mt-1.5 fst-italic" style="max-width: 240px; font-size: 0.78rem;">
                      <i data-lucide="message-square" style="width:11px;height:11px;display:inline-block;vertical-align:-1px;"></i>
                      <?php echo clean($c['admin_remarks']); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="py-3 text-muted small">
                  <?php echo clean(date('M j, Y', strtotime($c['uploaded_at']))); ?>
                </td>
                <td class="pe-4 py-3 text-end">
                  <?php if ($c['status'] === 'pending'): ?>
                    <div class="d-inline-flex gap-2">
                      <form action="/admin/certificate_action.php" method="POST" class="d-inline">
                        <input type="hidden" name="certificate_id" value="<?php echo (int)$c['certificate_id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1" onclick="return confirm('Approve this certificate and verify teacher status for <?php echo addslashes(clean($c['full_name'])); ?>?');">
                          <i data-lucide="check" style="width:13px;height:13px;"></i> Approve
                        </button>
                      </form>
                      <form action="/admin/certificate_action.php" method="POST" class="d-inline">
                        <input type="hidden" name="certificate_id" value="<?php echo (int)$c['certificate_id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="remarks" value="">
                        <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1" onclick="return promptRejectReason(this)">
                          <i data-lucide="x" style="width:13px;height:13px;"></i> Reject
                        </button>
                      </form>
                    </div>
                  <?php else: ?>
                    <span class="text-muted small d-inline-flex align-items-center gap-1">
                      <i data-lucide="check-check" style="width:14px;height:14px;" class="text-muted"></i> Resolved
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Ask admin for rejection note shown to student
  function promptRejectReason(button) {
    const reason = prompt('Please enter the reason for rejecting this certificate (this will be sent to the student):');
    if (reason === null || reason.trim() === '') return false;
    button.form.querySelector('input[name="remarks"]').value = reason.trim();
    return true;
  }
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
