<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();
$requestId = (int) ($_GET['request_id'] ?? 0);

$stmt = $db->prepare(
    "SELECT r.*, os.skill_name AS offered_skill_name, rs.skill_name AS requested_skill_name,
            su.full_name AS sender_name, ru.full_name AS receiver_name
     FROM exchange_requests r
     JOIN skills os ON os.skill_id = r.offered_skill_id
     JOIN skills rs ON rs.skill_id = r.requested_skill_id
     JOIN users su ON su.user_id = r.sender_id
     JOIN users ru ON ru.user_id = r.receiver_id
     WHERE r.request_id = ?"
);
$stmt->execute([$requestId]);
$request = $stmt->fetch();

// Only the two people involved can schedule, and only once the request is accepted.
if (!$request || ($request['sender_id'] != $userId && $request['receiver_id'] != $userId)) {
    setFlash('error', 'That request could not be found.');
    header('Location: /exchange_requests.php');
    exit;
}
if ($request['status'] !== 'accepted') {
    setFlash('error', 'You can only schedule a session for an accepted request.');
    header('Location: /exchange_requests.php');
    exit;
}

$otherPartyName = ($request['sender_id'] == $userId) ? $request['receiver_name'] : $request['sender_name'];

// Pre-generate a unique Jitsi Meet room name
$jitsiRoomName = 'SkillSwap-Session-' . $requestId . '-' . bin2hex(random_bytes(4));
$jitsiLink = 'https://meet.jit.si/' . $jitsiRoomName;

$pageTitle = 'Schedule a Session';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center animate-fade-in-up">
  <div class="col-12 col-md-8 col-lg-6">

    <!-- Back button -->
    <div class="mb-3">
      <a href="/exchange_requests.php" class="text-muted small fw-semibold text-decoration-none">
        <i data-lucide="arrow-left" style="width: 14px; height: 14px; display: inline-block;"></i> Back to Exchange Requests
      </a>
    </div>

    <!-- Partner & Exchange Summary -->
    <div class="card shadow-sm mb-4 border-primary border-opacity-25" style="background: linear-gradient(135deg, #ffffff 0%, #f8faff 100%);">
      <div class="card-body p-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="brand-icon-box" style="width: 44px; height: 44px; border-radius: 12px;">
            <i data-lucide="calendar-plus" style="width: 22px; height: 22px;"></i>
          </div>
          <div>
            <h2 class="h4 fw-bold mb-0 text-dark">Schedule Learning Session</h2>
            <p class="text-muted small mb-0">With peer partner <strong><?php echo clean($otherPartyName); ?></strong></p>
          </div>
        </div>

        <div class="p-2 px-3 rounded-2 bg-white border d-flex align-items-center justify-content-between small">
          <span class="text-muted">Skills to Exchange:</span>
          <div class="fw-semibold text-dark">
            <span class="badge bg-primary me-1"><?php echo clean($request['requested_skill_name']); ?></span>
            &harr;
            <span class="badge bg-info ms-1"><?php echo clean($request['offered_skill_name']); ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Scheduling Form Card -->
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <form action="/session_create.php" method="POST">
          <input type="hidden" name="request_id" value="<?php echo (int)$requestId; ?>">

          <div class="mb-4">
            <label class="form-label d-flex align-items-center gap-1">
              <i data-lucide="calendar" style="width: 16px; height: 16px; color: var(--ss-primary);"></i>
              <span>Date &amp; Time</span>
            </label>
            <input type="datetime-local" name="scheduled_date" class="form-control" required
                   min="<?php echo date('Y-m-d\TH:i'); ?>">
            <div class="form-text">Choose an upcoming date and time you both agreed on.</div>
          </div>

          <div class="mb-4">
            <label class="form-label d-flex align-items-center gap-1">
              <i data-lucide="monitor" style="width: 16px; height: 16px; color: var(--ss-info);"></i>
              <span>Meeting Mode</span>
            </label>
            <select name="mode" class="form-select" id="modeSelect" required>
              <option value="online" selected>Online (Auto-Generated Meeting Link)</option>
              <option value="in-person">In-Person (Campus Library, Student Center, Cafe)</option>
            </select>
          </div>

          <!-- Online Meeting Link Section (auto-generated) -->
          <div class="mb-4" id="onlineLinkSection">
            <label class="form-label d-flex align-items-center gap-1">
              <i data-lucide="video" style="width: 16px; height: 16px; color: var(--ss-info);"></i>
              <span>Meeting Link</span>
              <span class="badge bg-success-subtle text-success ms-2 small fw-medium">Auto-Generated</span>
            </label>

            <!-- Auto-generated Jitsi link card -->
            <div class="p-3 rounded-3 border border-success border-opacity-25 mb-2" style="background: #f0fdf4;" id="autoLinkCard">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px;height:28px;">
                  <i data-lucide="video" style="width:14px;height:14px;color:#fff;"></i>
                </div>
                <div>
                  <div class="small fw-bold text-dark">Jitsi Meet — Free Video Call</div>
                  <div class="text-muted" style="font-size:0.72rem;">No sign-up required. Works in any browser. Both you and your partner simply click the link to join.</div>
                </div>
              </div>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i data-lucide="link" style="width:13px;height:13px;"></i></span>
                <input type="text" class="form-control bg-white text-primary fw-semibold" id="autoLinkInput"
                       value="<?php echo clean($jitsiLink); ?>" readonly>
                <button type="button" class="btn btn-outline-success" onclick="copyMeetLink()" id="copyBtn">
                  <i data-lucide="copy" style="width:13px;height:13px;"></i> Copy
                </button>
              </div>
            </div>

            <input type="hidden" name="location_or_link" id="locationInput" value="<?php echo clean($jitsiLink); ?>">

            <div class="form-text">
              <i data-lucide="info" style="width:12px;height:12px;display:inline-block;vertical-align:-1px;"></i>
              A unique video meeting room has been auto-generated. Both participants will see this link on the session card.
            </div>

            <!-- Toggle to use a custom link -->
            <div class="mt-2">
              <a href="#" class="small text-muted text-decoration-none" id="toggleCustomLink">
                <i data-lucide="edit-3" style="width:12px;height:12px;display:inline-block;vertical-align:-1px;"></i>
                Use a custom link instead (Google Meet, Zoom, Discord, etc.)
              </a>
            </div>
            <div class="mt-2 d-none" id="customLinkSection">
              <input type="text" class="form-control" id="customLinkInput"
                     placeholder="https://meet.google.com/xyz-abcd-efg or https://zoom.us/j/123456789">
              <a href="#" class="small text-success text-decoration-none mt-1 d-inline-block" id="revertAutoLink">
                <i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:-1px;"></i>
                Revert to auto-generated link
              </a>
            </div>
          </div>

          <!-- In-Person Location Section (hidden by default) -->
          <div class="mb-4 d-none" id="inPersonSection">
            <label class="form-label d-flex align-items-center gap-1">
              <i data-lucide="map-pin" style="width: 16px; height: 16px; color: var(--ss-accent);"></i>
              <span>Campus / Meetup Location</span>
            </label>
            <input type="text" class="form-control" id="inPersonInput"
                   placeholder="e.g. Science Library, 2nd Floor Study Room 204">
          </div>

          <div class="d-flex gap-2 pt-2 border-top">
            <button type="submit" class="btn btn-primary flex-fill py-2">
              <i data-lucide="calendar-check" style="width: 16px; height: 16px;"></i>
              <span>Confirm &amp; Schedule Session</span>
            </button>
            <a href="/exchange_requests.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<script>
  const autoJitsiLink = <?php echo json_encode($jitsiLink); ?>;
  const modeSelect = document.getElementById('modeSelect');
  const onlineLinkSection = document.getElementById('onlineLinkSection');
  const inPersonSection = document.getElementById('inPersonSection');
  const locationInput = document.getElementById('locationInput');
  const autoLinkCard = document.getElementById('autoLinkCard');
  const autoLinkInput = document.getElementById('autoLinkInput');
  const customLinkSection = document.getElementById('customLinkSection');
  const customLinkInput = document.getElementById('customLinkInput');
  const inPersonInput = document.getElementById('inPersonInput');
  const toggleCustomLink = document.getElementById('toggleCustomLink');
  const revertAutoLink = document.getElementById('revertAutoLink');

  // Switch between online and in-person
  modeSelect.addEventListener('change', function () {
    if (this.value === 'online') {
      onlineLinkSection.classList.remove('d-none');
      inPersonSection.classList.add('d-none');
      // Restore the online link value
      locationInput.value = customLinkSection.classList.contains('d-none')
        ? autoJitsiLink
        : (customLinkInput.value.trim() || autoJitsiLink);
    } else {
      onlineLinkSection.classList.add('d-none');
      inPersonSection.classList.remove('d-none');
      // Sync location input
      locationInput.value = inPersonInput.value.trim();
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
  });

  // Sync in-person input to hidden field
  inPersonInput.addEventListener('input', function () {
    locationInput.value = this.value.trim();
  });

  // Toggle custom link entry
  toggleCustomLink.addEventListener('click', function (e) {
    e.preventDefault();
    autoLinkCard.classList.add('d-none');
    customLinkSection.classList.remove('d-none');
    this.classList.add('d-none');
    customLinkInput.focus();
    if (typeof lucide !== 'undefined') lucide.createIcons();
  });

  // Sync custom link to hidden field
  customLinkInput.addEventListener('input', function () {
    locationInput.value = this.value.trim() || autoJitsiLink;
  });

  // Revert to auto-generated link
  revertAutoLink.addEventListener('click', function (e) {
    e.preventDefault();
    autoLinkCard.classList.remove('d-none');
    customLinkSection.classList.add('d-none');
    toggleCustomLink.classList.remove('d-none');
    customLinkInput.value = '';
    locationInput.value = autoJitsiLink;
    if (typeof lucide !== 'undefined') lucide.createIcons();
  });

  // Copy link to clipboard
  function copyMeetLink() {
    const link = autoLinkInput.value;
    navigator.clipboard.writeText(link).then(() => {
      const btn = document.getElementById('copyBtn');
      btn.innerHTML = '<i data-lucide="check" style="width:13px;height:13px;"></i> Copied!';
      btn.classList.remove('btn-outline-success');
      btn.classList.add('btn-success');
      if (typeof lucide !== 'undefined') lucide.createIcons();
      setTimeout(() => {
        btn.innerHTML = '<i data-lucide="copy" style="width:13px;height:13px;"></i> Copy';
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-success');
        if (typeof lucide !== 'undefined') lucide.createIcons();
      }, 2000);
    });
  }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

