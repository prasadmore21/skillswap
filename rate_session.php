<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();
$sessionId = (int) ($_GET['session_id'] ?? 0);

$stmt = $db->prepare(
    "SELECT ss.*, r.request_id, r.sender_id, r.receiver_id,
            os.skill_name AS offered_skill_name, rs.skill_name AS requested_skill_name,
            su.full_name AS sender_name, ru.full_name AS receiver_name
     FROM sessions_schedule ss
     JOIN exchange_requests r ON r.request_id = ss.request_id
     JOIN skills os ON os.skill_id = r.offered_skill_id
     JOIN skills rs ON rs.skill_id = r.requested_skill_id
     JOIN users su ON su.user_id = r.sender_id
     JOIN users ru ON ru.user_id = r.receiver_id
     WHERE ss.session_id = ?"
);
$stmt->execute([$sessionId]);
$session = $stmt->fetch();

if (!$session || ($session['sender_id'] != $userId && $session['receiver_id'] != $userId)) {
    setFlash('error', 'That session could not be found.');
    header('Location: /sessions.php');
    exit;
}
if ($session['status'] !== 'completed') {
    setFlash('error', 'You can only rate a completed session.');
    header('Location: /sessions.php');
    exit;
}

$ratedUserId = ($session['sender_id'] == $userId) ? $session['receiver_id'] : $session['sender_id'];
$ratedUserName = ($session['sender_id'] == $userId) ? $session['receiver_name'] : $session['sender_name'];

// One rating per rater per exchange request (a request may have several sessions).
$stmt = $db->prepare('SELECT 1 FROM ratings WHERE request_id = ? AND rater_id = ?');
$stmt->execute([$session['request_id'], $userId]);
if ($stmt->fetch()) {
    setFlash('error', 'You already rated this exchange.');
    header('Location: /sessions.php');
    exit;
}

$pageTitle = 'Rate Your Session';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center animate-fade-in-up">
  <div class="col-12 col-md-8 col-lg-6">

    <!-- Back link -->
    <div class="mb-3">
      <a href="/sessions.php" class="text-muted small fw-semibold text-decoration-none">
        <i data-lucide="arrow-left" style="width: 14px; height: 14px; display: inline-block;"></i> Back to My Sessions
      </a>
    </div>

    <!-- Partner Header Card -->
    <div class="card shadow-sm mb-4 border-warning border-opacity-25" style="background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle p-2 d-inline-flex" style="background: #fef3c7; color: #d97706;">
            <i data-lucide="star" style="width: 24px; height: 24px;"></i>
          </div>
          <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Rate Your Session</h2>
            <p class="text-muted small mb-0">
              With <strong><?php echo clean($ratedUserName); ?></strong> &bull;
              <span><?php echo clean($session['requested_skill_name']); ?> &harr; <?php echo clean($session['offered_skill_name']); ?></span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Rating Form -->
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <form action="/rate_session_submit.php" method="POST">
          <input type="hidden" name="session_id" value="<?php echo (int)$sessionId; ?>">

          <div class="mb-4">
            <label class="form-label d-flex align-items-center gap-1">
              <i data-lucide="award" style="width: 16px; height: 16px; color: #fbbf24;"></i>
              <span>Overall Rating</span>
            </label>
            <select name="rating" class="form-select fs-6 py-2" required>
              <option value="" disabled selected>Select star rating&hellip;</option>
              <option value="5">★★★★★ — 5 Stars (Excellent / Highly Recommended)</option>
              <option value="4">★★★★☆ — 4 Stars (Very Good / Helpful)</option>
              <option value="3">★★★☆☆ — 3 Stars (Average / Met Expectations)</option>
              <option value="2">★★☆☆☆ — 2 Stars (Below Expectations)</option>
              <option value="1">★☆☆☆☆ — 1 Star (Poor Experience)</option>
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label d-flex align-items-center gap-1">
              <i data-lucide="message-square" style="width: 16px; height: 16px; color: var(--ss-text-muted);"></i>
              <span>Written Feedback (optional)</span>
            </label>
            <textarea name="review" class="form-control" rows="4" maxlength="1000"
                      placeholder="How was the session? Did you learn effectively? Would you recommend them to other students?"></textarea>
            <div class="form-text">Your honest review helps other students discover verified and helpful peers.</div>
          </div>

          <div class="d-flex gap-2 pt-2 border-top">
            <button type="submit" class="btn btn-primary flex-fill py-2">
              <i data-lucide="check" style="width: 16px; height: 16px;"></i>
              <span>Submit Rating &amp; Review</span>
            </button>
            <a href="/sessions.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

