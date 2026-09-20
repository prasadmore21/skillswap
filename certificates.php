<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

// Skills this user teaches — only these can have a certificate attached.
$stmt = $db->prepare(
    "SELECT s.skill_id, s.skill_name FROM user_skills us
     JOIN skills s ON s.skill_id = us.skill_id
     WHERE us.user_id = ? AND us.type = 'teach' ORDER BY s.skill_name"
);
$stmt->execute([$userId]);
$teachSkills = $stmt->fetchAll();

// This user's certificate submissions so far
$stmt = $db->prepare(
    "SELECT c.*, s.skill_name FROM certificates c
     JOIN skills s ON s.skill_id = c.skill_id
     WHERE c.user_id = ? ORDER BY c.uploaded_at DESC"
);
$stmt->execute([$userId]);
$myCertificates = $stmt->fetchAll();

$statusBadge = [
    'pending'  => 'bg-warning text-dark',
    'approved' => 'bg-success',
    'rejected' => 'bg-danger',
];

$pageTitle = 'Skill Certificates';
require __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="ss-page-header animate-fade-in-up">
  <div>
    <h1 class="h2 ss-page-title">Skill Certificates &amp; Credentials</h1>
    <p class="ss-page-subtitle">Submit course completion certificates or diplomas to earn your platform Verified Teacher badge.</p>
  </div>
  <div>
    <a href="/profile.php" class="btn btn-outline-secondary btn-sm">
      <i data-lucide="user" style="width: 14px; height: 14px;"></i>
      <span>Manage Taught Skills</span>
    </a>
  </div>
</div>

<div class="row g-4 animate-fade-in-up">
  
  <!-- LEFT: Upload Card & Info -->
  <div class="col-12 col-lg-5">
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="upload-cloud" style="width: 20px; height: 20px; color: var(--ss-primary);"></i>
          <span class="fw-bold">Upload Credential Proof</span>
        </div>
      </div>
      <div class="card-body p-4">
        <?php if (empty($teachSkills)): ?>
          <div class="p-3 text-center rounded bg-light border">
            <i data-lucide="alert-circle" style="width: 24px; height: 24px; color: var(--ss-warning);" class="mb-2"></i>
            <p class="text-muted small mb-2">
              You haven't listed any skills on your "Skills I Can Teach" list yet.
            </p>
            <a href="/profile.php" class="btn btn-primary btn-sm">Add a Teaching Skill First</a>
          </div>
        <?php else: ?>
          <form action="/certificate_upload.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
              <label class="form-label">Which skill is this proof for?</label>
              <select name="skill_id" class="form-select" required>
                <option value="" disabled selected>Select a taught skill&hellip;</option>
                <?php foreach ($teachSkills as $s): ?>
                  <option value="<?php echo (int)$s['skill_id']; ?>"><?php echo clean($s['skill_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label">Certificate Document or Image</label>
              <input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
              <div class="form-text">PDF, JPG, or PNG format (max 5MB).</div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
              <i data-lucide="upload" style="width: 16px; height: 16px;"></i>
              <span>Submit Certificate for Review</span>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- Verification Value Card -->
    <div class="card bg-light border-0">
      <div class="card-body p-4">
        <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2">
          <i data-lucide="shield-check" style="width: 18px; height: 18px; color: var(--ss-success);"></i>
          <span>Why get verified?</span>
        </h6>
        <p class="text-muted small mb-0">
          When an administrator verifies your credential, a <strong>Verified Teacher</strong> badge appears on all your profile cards, building trust and prioritizing your profile in match searches.
        </p>
      </div>
    </div>
  </div>

  <!-- RIGHT: Submissions Table -->
  <div class="col-12 col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
          <i data-lucide="file-text" style="width: 20px; height: 20px; color: var(--ss-primary);"></i>
          <span class="fw-bold">Your Submitted Credentials</span>
        </div>
        <span class="badge bg-secondary"><?php echo count($myCertificates); ?> total</span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($myCertificates)): ?>
          <div class="ss-empty-state border-0">
            <div class="ss-empty-icon">
              <i data-lucide="award" style="width: 28px; height: 28px;"></i>
            </div>
            <h3 class="ss-empty-title">No Certificates Uploaded</h3>
            <p class="ss-empty-desc">Upload proof of any skill you teach to verify your knowledge and earn peer trust.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive border-0">
            <table class="table align-middle mb-0">
              <thead>
                <tr>
                  <th>Skill</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Remarks / Feedback</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($myCertificates as $c): ?>
                  <tr>
                    <td>
                      <span class="fw-bold text-dark"><?php echo clean($c['skill_name']); ?></span>
                      <div class="mt-1">
                        <a href="/uploads/certificates/<?php echo clean($c['file_path']); ?>" target="_blank" class="small text-primary text-decoration-none d-inline-flex align-items-center gap-1">
                          <i data-lucide="file" style="width: 12px; height: 12px;"></i> View Document
                        </a>
                      </div>
                    </td>
                    <td>
                      <span class="badge <?php echo $statusBadge[$c['status']] ?? 'bg-secondary'; ?>">
                        <?php echo clean(ucfirst($c['status'])); ?>
                      </span>
                    </td>
                    <td class="text-muted small">
                      <?php echo clean(date('M j, Y', strtotime($c['uploaded_at']))); ?>
                    </td>
                    <td>
                      <?php if ($c['admin_remarks']): ?>
                        <span class="small text-secondary"><?php echo clean($c['admin_remarks']); ?></span>
                      <?php else: ?>
                        <span class="text-muted small">&mdash;</span>
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
  </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

