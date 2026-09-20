<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

$stmt = $db->prepare(
    "SELECT ss.*, r.sender_id, r.receiver_id, r.request_id,
            os.skill_name AS offered_skill_name, rs.skill_name AS requested_skill_name,
            su.full_name AS sender_name, ru.full_name AS receiver_name,
            (SELECT 1 FROM ratings WHERE request_id = r.request_id AND rater_id = ?) AS already_rated
     FROM sessions_schedule ss
     JOIN exchange_requests r ON r.request_id = ss.request_id
     JOIN skills os ON os.skill_id = r.offered_skill_id
     JOIN skills rs ON rs.skill_id = r.requested_skill_id
     JOIN users su ON su.user_id = r.sender_id
     JOIN users ru ON ru.user_id = r.receiver_id
     WHERE r.sender_id = ? OR r.receiver_id = ?
     ORDER BY ss.scheduled_date ASC"
);
$stmt->execute([$userId, $userId, $userId]);
$allSessions = $stmt->fetchAll();

// Helper to classify visual status based on database state & scheduled timestamp
function getSessionVisualStatus(array $s): array {
    $rawStatus = $s['status'];
    $scheduledTime = strtotime($s['scheduled_date']);
    $now = time();

    if ($rawStatus === 'cancelled') {
        return ['code' => 'cancelled', 'label' => 'Cancelled', 'badge' => 'bg-secondary', 'border' => 'border-secondary'];
    }
    if ($rawStatus === 'completed') {
        return ['code' => 'completed', 'label' => 'Completed', 'badge' => 'bg-success', 'border' => 'border-success'];
    }

    // rawStatus is 'scheduled'
    if ($scheduledTime > $now) {
        // Today or starting in < 2 hours?
        if (date('Y-m-d', $scheduledTime) === date('Y-m-d', $now) || ($scheduledTime - $now) <= 7200) {
            return ['code' => 'active', 'label' => 'Active / Today', 'badge' => 'bg-warning text-dark', 'border' => 'border-warning'];
        }
        return ['code' => 'upcoming', 'label' => 'Upcoming', 'badge' => 'bg-primary', 'border' => 'border-primary'];
    } else {
        // Scheduled date is in the past
        if (($now - $scheduledTime) <= 7200) {
            return ['code' => 'active', 'label' => 'Active Now', 'badge' => 'bg-warning text-dark', 'border' => 'border-warning'];
        }
        return ['code' => 'overdue', 'label' => 'Overdue', 'badge' => 'bg-danger', 'border' => 'border-danger'];
    }
}

$upcoming = [];
$past = [];
$overdue = [];

foreach ($allSessions as $s) {
    $visual = getSessionVisualStatus($s);
    $s['visual'] = $visual;
    
    if ($visual['code'] === 'overdue') {
        $overdue[] = $s;
    } elseif ($visual['code'] === 'upcoming' || $visual['code'] === 'active') {
        $upcoming[] = $s;
    } else {
        $past[] = $s;
    }
}

function otherPartyName($s, $userId) {
    return ($s['sender_id'] == $userId) ? $s['receiver_name'] : $s['sender_name'];
}

$pageTitle = 'Learning Sessions';
require __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="ss-hero-card mb-4 animate-fade-in-up" style="padding: 1.75rem 2rem;">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index: 2;">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(255,255,255,0.2);">
          <i data-lucide="calendar" style="width:18px;height:18px;color:#fff;"></i>
        </div>
        <h1 class="h3 mb-0 ss-hero-title">Learning Sessions</h1>
      </div>
      <p class="ss-hero-subtitle mb-0">
        Attend upcoming video calls, meet on campus, and record peer reviews for completed exchanges.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="/exchange_requests.php" class="btn btn-sm px-3.5 py-2 text-nowrap" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.35);">
        <i data-lucide="plus-circle" style="width:14px;height:14px;"></i>
        <span>Schedule from Requests</span>
      </a>
    </div>
  </div>
</div>

<!-- OVERDUE NOTICE (IF ANY SESSIONS ARE PAST SCHEDULE TIME AND NOT WRAPPED UP) -->
<?php if (!empty($overdue)): ?>
  <div class="card mb-4 border-danger border-opacity-50 shadow-sm" style="background:#fff5f5;">
    <div class="card-header bg-transparent py-3 border-danger border-opacity-25 text-danger">
      <div class="d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
        <span class="fw-bold">Overdue Sessions Requiring Wrap-Up</span>
      </div>
      <span class="badge bg-danger"><?php echo count($overdue); ?></span>
    </div>
    <div class="card-body p-3">
      <p class="small text-muted mb-3">These sessions have passed their scheduled time. Please mark them as completed to rate your partner, or cancel if the meetup didn't happen.</p>
      <div class="row g-3">
        <?php foreach ($overdue as $s): ?>
          <div class="col-12 col-md-6">
            <div class="p-3 bg-white rounded-3 border border-danger border-opacity-25 shadow-xs">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-dark">With <?php echo clean(otherPartyName($s, $userId)); ?></span>
                <span class="badge bg-danger">Overdue</span>
              </div>
              <div class="small text-muted mb-2">
                <?php echo clean($s['requested_skill_name']); ?> &harr; <?php echo clean($s['offered_skill_name']); ?>
                <div class="mt-1"><?php echo clean(date('M j, Y \a\t g:i A', strtotime($s['scheduled_date']))); ?></div>
              </div>
              <form action="/session_action.php" method="POST" class="d-flex gap-2 mt-2">
                <input type="hidden" name="session_id" value="<?php echo (int)$s['session_id']; ?>">
                <button type="submit" name="action" value="complete" class="btn btn-sm btn-success flex-fill">
                  <i data-lucide="check" style="width:13px;height:13px;"></i> Mark Completed
                </button>
                <button type="submit" name="action" value="cancel" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this session?');">
                  Cancel
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- TABS: UPCOMING & ACTIVE VS PAST & COMPLETED -->
<ul class="nav nav-tabs mb-4 animate-fade-in-up" role="tablist">
  <li class="nav-item">
    <button class="nav-link active d-inline-flex align-items-center gap-2" data-bs-toggle="tab" data-bs-target="#upcomingTab">
      <i data-lucide="clock" style="width: 16px; height: 16px;"></i>
      <span>Upcoming &amp; Active</span>
      <span class="badge bg-primary"><?php echo count($upcoming); ?></span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link d-inline-flex align-items-center gap-2" data-bs-toggle="tab" data-bs-target="#pastTab">
      <i data-lucide="check-circle" style="width: 16px; height: 16px;"></i>
      <span>Completed &amp; Past</span>
      <span class="badge bg-secondary"><?php echo count($past); ?></span>
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- UPCOMING & ACTIVE TAB -->
  <div class="tab-pane fade show active" id="upcomingTab">
    <?php if (empty($upcoming)): ?>
      <div class="ss-empty-state">
        <div class="ss-empty-icon">
          <i data-lucide="calendar-x" style="width: 28px; height: 28px;"></i>
        </div>
        <h3 class="ss-empty-title">No Upcoming Sessions</h3>
        <p class="ss-empty-desc">You have no upcoming sessions scheduled. Accept an exchange request to schedule a learning time!</p>
        <a href="/exchange_requests.php" class="btn btn-outline-primary btn-sm">View Accepted Requests</a>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($upcoming as $s): ?>
          <div class="col-12 col-md-6">
            <div class="card h-100 shadow-sm border-0 border-start border-4 <?php echo $s['visual']['border']; ?>">
              <div class="card-body p-4 d-flex flex-column justify-content-between">
                
                <div>
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                      <h5 class="fw-bold mb-1 text-dark">
                        With <?php echo clean(otherPartyName($s, $userId)); ?>
                      </h5>
                      <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge bg-primary"><?php echo clean($s['requested_skill_name']); ?></span>
                        <span class="text-muted small">&harr;</span>
                        <span class="badge bg-info"><?php echo clean($s['offered_skill_name']); ?></span>
                      </div>
                    </div>
                    <span class="badge <?php echo $s['visual']['badge']; ?>">
                      <?php echo $s['visual']['label']; ?>
                    </span>
                  </div>

                  <!-- Date & Mode Info -->
                  <div class="p-3 bg-light rounded-3 my-3">
                    <div class="d-flex align-items-center gap-2 text-dark fw-semibold small mb-1">
                      <i data-lucide="calendar" style="width: 15px; height: 15px; color: var(--ss-primary);"></i>
                      <span><?php echo clean(date('l, F j, Y', strtotime($s['scheduled_date']))); ?></span>
                      &bull;
                      <span><?php echo clean(date('g:i A', strtotime($s['scheduled_date']))); ?></span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2 text-muted small mt-2">
                      <?php if ($s['mode'] === 'online'): ?>
                        <i data-lucide="video" style="width: 15px; height: 15px; color: var(--ss-info);"></i>
                        <span>Online Session</span>
                        <?php if (!empty($s['location_or_link'])): ?>
                          &mdash;
                          <a href="<?php echo (str_starts_with($s['location_or_link'], 'http') ? clean($s['location_or_link']) : 'https://' . clean($s['location_or_link'])); ?>"
                             target="_blank" rel="noopener noreferrer" class="text-primary fw-semibold text-truncate d-inline-block" style="max-width:180px;">
                            Join Meeting <i data-lucide="external-link" style="width:11px;height:11px;"></i>
                          </a>
                        <?php endif; ?>
                      <?php else: ?>
                        <i data-lucide="map-pin" style="width: 15px; height: 15px; color: var(--ss-accent);"></i>
                        <span>In-Person: <?php echo clean($s['location_or_link'] ?? 'Campus / Library'); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <!-- Action Form -->
                <div class="pt-3 border-top d-flex gap-2">
                  <form action="/session_action.php" method="POST" class="d-flex gap-2 w-100">
                    <input type="hidden" name="session_id" value="<?php echo (int)$s['session_id']; ?>">
                    <button type="submit" name="action" value="complete" class="btn btn-sm btn-success flex-fill">
                      <i data-lucide="check" style="width: 14px; height: 14px;"></i> Mark Completed
                    </button>
                    <button type="submit" name="action" value="cancel" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('Cancel this scheduled session?');">
                      Cancel
                    </button>
                  </form>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- PAST & COMPLETED TAB -->
  <div class="tab-pane fade" id="pastTab">
    <?php if (empty($past)): ?>
      <div class="ss-empty-state">
        <div class="ss-empty-icon">
          <i data-lucide="calendar" style="width: 28px; height: 28px;"></i>
        </div>
        <h3 class="ss-empty-title">No Past Sessions</h3>
        <p class="ss-empty-desc">Completed learning sessions and your session history will be archived here.</p>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($past as $s): ?>
          <div class="col-12 col-md-6">
            <div class="card h-100 shadow-sm border-0 border-start border-4 <?php echo $s['visual']['border']; ?>">
              <div class="card-body p-4 d-flex flex-column justify-content-between">
                
                <div>
                  <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                      <h5 class="fw-bold mb-1 text-dark">
                        With <?php echo clean(otherPartyName($s, $userId)); ?>
                      </h5>
                      <div class="small text-muted">
                        <?php echo clean($s['requested_skill_name']); ?> &harr; <?php echo clean($s['offered_skill_name']); ?>
                      </div>
                    </div>
                    <span class="badge <?php echo $s['visual']['badge']; ?>">
                      <?php echo $s['visual']['label']; ?>
                    </span>
                  </div>

                  <div class="small text-muted mt-2">
                    <i data-lucide="clock" style="width:13px;height:13px;display:inline-block;"></i>
                    Held on <?php echo clean(date('M j, Y \a\t g:i A', strtotime($s['scheduled_date']))); ?>
                  </div>
                </div>

                <!-- Rating action if completed -->
                <div class="pt-3 mt-3 border-top d-flex justify-content-between align-items-center">
                  <?php if ($s['status'] === 'completed' && empty($s['already_rated'])): ?>
                    <a href="/rate_session.php?session_id=<?php echo (int)$s['session_id']; ?>" class="btn btn-sm btn-primary">
                      <i data-lucide="star" style="width: 14px; height: 14px;"></i> Leave a Rating &rarr;
                    </a>
                  <?php elseif ($s['status'] === 'completed'): ?>
                    <span class="badge bg-light text-success border border-success border-opacity-25">
                      <i data-lucide="check" style="width:12px;height:12px;"></i> Rating Submitted
                    </span>
                  <?php else: ?>
                    <span class="text-muted small">Session <?php echo clean($s['status']); ?></span>
                  <?php endif; ?>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

