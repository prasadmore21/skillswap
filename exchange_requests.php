<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

$statusBadge = [
    'pending'   => 'bg-warning text-dark',
    'accepted'  => 'bg-success',
    'rejected'  => 'bg-danger',
    'cancelled' => 'bg-secondary',
    'completed' => 'bg-primary',
];

$stmt = $db->prepare(
    "SELECT r.*, u.full_name AS sender_name, os.skill_name AS offered_skill_name, rs.skill_name AS requested_skill_name
     FROM exchange_requests r
     JOIN users u ON u.user_id = r.sender_id
     JOIN skills os ON os.skill_id = r.offered_skill_id
     JOIN skills rs ON rs.skill_id = r.requested_skill_id
     WHERE r.receiver_id = ? ORDER BY r.created_at DESC"
);
$stmt->execute([$userId]);
$received = $stmt->fetchAll();

$stmt = $db->prepare(
    "SELECT r.*, u.full_name AS receiver_name, os.skill_name AS offered_skill_name, rs.skill_name AS requested_skill_name
     FROM exchange_requests r
     JOIN users u ON u.user_id = r.receiver_id
     JOIN skills os ON os.skill_id = r.offered_skill_id
     JOIN skills rs ON rs.skill_id = r.requested_skill_id
     WHERE r.sender_id = ? ORDER BY r.created_at DESC"
);
$stmt->execute([$userId]);
$sent = $stmt->fetchAll();

$pageTitle = 'Exchange Requests';
require __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="ss-hero-card mb-4 animate-fade-in-up" style="padding: 1.75rem 2rem;">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 position-relative" style="z-index: 2;">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:rgba(255,255,255,0.2);">
          <i data-lucide="arrow-left-right" style="width:18px;height:18px;color:#fff;"></i>
        </div>
        <h1 class="h3 mb-0 ss-hero-title">Exchange Proposals</h1>
      </div>
      <p class="ss-hero-subtitle mb-0">
        Review incoming peer learning requests, respond with one click, or track proposals you have sent out to others.
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="/matches.php" class="btn btn-accent btn-sm px-3.5 py-2 text-nowrap">
        <i data-lucide="sparkles" style="width:14px;height:14px;"></i>
        <span>Discover Matches</span>
      </a>
    </div>
  </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4 animate-fade-in-up" role="tablist">
  <li class="nav-item">
    <button class="nav-link active d-inline-flex align-items-center gap-2" data-bs-toggle="tab" data-bs-target="#received">
      <i data-lucide="inbox" style="width: 16px; height: 16px;"></i>
      <span>Received Proposals</span>
      <span class="badge bg-secondary"><?php echo count($received); ?></span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link d-inline-flex align-items-center gap-2" data-bs-toggle="tab" data-bs-target="#sent">
      <i data-lucide="send" style="width: 16px; height: 16px;"></i>
      <span>Sent by You</span>
      <span class="badge bg-secondary"><?php echo count($sent); ?></span>
    </button>
  </li>
</ul>

<div class="tab-content">

  <!-- RECEIVED REQUESTS -->
  <div class="tab-pane fade show active" id="received">
    <?php if (empty($received)): ?>
      <div class="ss-empty-state">
        <div class="ss-empty-icon">
          <i data-lucide="inbox" style="width:28px;height:28px;"></i>
        </div>
        <h3 class="ss-empty-title">No Received Requests Yet</h3>
        <p class="ss-empty-desc">When students find your skills and ask to swap, their requests will appear here for you to accept or decline.</p>
        <a href="/matches.php" class="btn btn-outline-primary btn-sm">Find Students to Connect With</a>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($received as $r): ?>
          <div class="col-12">
            <div class="card shadow-sm border-0 border-start border-4 <?php echo $r['status'] === 'pending' ? 'border-warning' : ($r['status'] === 'accepted' ? 'border-success' : 'border-secondary'); ?>">
              <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                  
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                      <h5 class="fw-bold mb-0 text-dark"><?php echo clean($r['sender_name']); ?></h5>
                      <span class="badge <?php echo $statusBadge[$r['status']] ?? 'bg-secondary'; ?>">
                        <?php echo clean(ucfirst($r['status'])); ?>
                      </span>
                      <small class="text-muted ms-auto"><?php echo clean(date('M j, Y', strtotime($r['created_at']))); ?></small>
                    </div>

                    <!-- Skills swap visual pill -->
                    <div class="p-3 bg-light rounded-3 d-inline-flex flex-wrap align-items-center gap-2 mb-2">
                      <span class="small text-muted">Wants to learn:</span>
                      <span class="badge bg-primary fs-6"><?php echo clean($r['requested_skill_name']); ?></span>
                      <i data-lucide="arrow-left-right" style="width:16px;height:16px;color:var(--ss-accent);" class="mx-1"></i>
                      <span class="small text-muted">Offers to teach:</span>
                      <span class="badge bg-info fs-6"><?php echo clean($r['offered_skill_name']); ?></span>
                    </div>

                    <?php if ($r['message']): ?>
                      <div class="small p-3 rounded-2 bg-white border mt-2 text-secondary fst-italic">
                        <i data-lucide="message-square" style="width: 14px; height: 14px; display:inline-block; margin-right:4px;"></i>
                        &ldquo;<?php echo clean($r['message']); ?>&rdquo;
                      </div>
                    <?php endif; ?>
                  </div>

                </div>

                <!-- Action Toolbar -->
                <div class="mt-3 pt-3 border-top d-flex flex-wrap gap-2 align-items-center justify-content-end">
                  <?php if ($r['status'] === 'pending'): ?>
                    <form action="/request_action.php" method="POST" class="d-inline-flex gap-2">
                      <input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>">
                      <button type="submit" name="action" value="accept" class="btn btn-sm btn-success">
                        <i data-lucide="check" style="width:14px;height:14px;"></i> Accept Exchange
                      </button>
                      <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger"
                              onclick="return confirm('Decline this exchange request?');">
                        <i data-lucide="x" style="width:14px;height:14px;"></i> Decline
                      </button>
                    </form>
                  <?php elseif ($r['status'] === 'accepted'): ?>
                    <a href="/session_form.php?request_id=<?php echo (int)$r['request_id']; ?>" class="btn btn-sm btn-primary">
                      <i data-lucide="calendar" style="width:14px;height:14px;"></i> Schedule Learning Session
                    </a>
                  <?php endif; ?>
                </div>

              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- SENT REQUESTS -->
  <div class="tab-pane fade" id="sent">
    <?php if (empty($sent)): ?>
      <div class="ss-empty-state">
        <div class="ss-empty-icon">
          <i data-lucide="send" style="width:28px;height:28px;"></i>
        </div>
        <h3 class="ss-empty-title">No Sent Requests Yet</h3>
        <p class="ss-empty-desc">Discover students with skills you'd love to learn and send them a swap request!</p>
        <a href="/matches.php" class="btn btn-primary btn-sm">Find Students to Connect With</a>
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($sent as $r): ?>
          <div class="col-12">
            <div class="card shadow-sm border-0 border-start border-4 <?php echo $r['status'] === 'pending' ? 'border-warning' : ($r['status'] === 'accepted' ? 'border-success' : 'border-secondary'); ?>">
              <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                  
                  <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                      <span class="text-muted small">Sent to:</span>
                      <h5 class="fw-bold mb-0 text-dark"><?php echo clean($r['receiver_name']); ?></h5>
                      <span class="badge <?php echo $statusBadge[$r['status']] ?? 'bg-secondary'; ?>">
                        <?php echo clean(ucfirst($r['status'])); ?>
                      </span>
                      <small class="text-muted ms-auto"><?php echo clean(date('M j, Y', strtotime($r['created_at']))); ?></small>
                    </div>

                    <div class="p-3 bg-light rounded-3 d-inline-flex flex-wrap align-items-center gap-2 mb-2">
                      <span class="small text-muted">Asked to learn:</span>
                      <span class="badge bg-primary fs-6"><?php echo clean($r['requested_skill_name']); ?></span>
                      <i data-lucide="arrow-left-right" style="width:16px;height:16px;color:var(--ss-accent);" class="mx-1"></i>
                      <span class="small text-muted">Offered to teach:</span>
                      <span class="badge bg-info fs-6"><?php echo clean($r['offered_skill_name']); ?></span>
                    </div>

                    <?php if ($r['message']): ?>
                      <div class="small p-3 rounded-2 bg-white border mt-2 text-secondary fst-italic">
                        &ldquo;<?php echo clean($r['message']); ?>&rdquo;
                      </div>
                    <?php endif; ?>
                  </div>

                </div>

                <div class="mt-3 pt-3 border-top d-flex justify-content-end gap-2">
                  <?php if ($r['status'] === 'pending'): ?>
                    <form action="/request_action.php" method="POST" class="d-inline" onsubmit="return confirm('Cancel this exchange request?');">
                      <input type="hidden" name="request_id" value="<?php echo (int)$r['request_id']; ?>">
                      <button type="submit" name="action" value="cancel" class="btn btn-sm btn-outline-secondary">
                        Cancel Request
                      </button>
                    </form>
                  <?php elseif ($r['status'] === 'accepted'): ?>
                    <a href="/session_form.php?request_id=<?php echo (int)$r['request_id']; ?>" class="btn btn-sm btn-primary">
                      <i data-lucide="calendar" style="width:14px;height:14px;"></i> Schedule Session
                    </a>
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

