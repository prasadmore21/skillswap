<?php
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$db = getDB();
$userId = currentUserId();

$stmt = $db->prepare(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50'
);
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

$pageTitle = 'Notifications';
require __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="ss-page-header animate-fade-in-up">
  <div>
    <div class="d-flex align-items-center gap-2">
      <h1 class="h2 ss-page-title">Notifications</h1>
      <?php if ($unreadCount > 0): ?>
        <span class="badge bg-danger rounded-pill"><?php echo $unreadCount; ?> new</span>
      <?php endif; ?>
    </div>
    <p class="ss-page-subtitle">Stay updated on exchange requests, session bookings, and student ratings.</p>
  </div>
  <?php if ($unreadCount > 0): ?>
    <div>
      <form action="/notifications_mark_all_read.php" method="POST">
        <button type="submit" class="btn btn-outline-secondary btn-sm">
          <i data-lucide="check-check" style="width: 14px; height: 14px;"></i>
          <span>Mark all as read</span>
        </button>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
  <div class="ss-empty-state animate-fade-in-up">
    <div class="ss-empty-icon">
      <i data-lucide="bell-off" style="width: 28px; height: 28px;"></i>
    </div>
    <h3 class="ss-empty-title">All Caught Up!</h3>
    <p class="ss-empty-desc">You don't have any notifications right now. Activity on your exchange requests and learning sessions will appear here.</p>
    <a href="/matches.php" class="btn btn-outline-primary btn-sm">Discover Peers to Swap Skills</a>
  </div>
<?php else: ?>
  <div class="card shadow-sm border-0 animate-fade-in-up">
    <div class="list-group list-group-flush">
      <?php foreach ($notifications as $n): ?>
        <a href="/notification_click.php?id=<?php echo (int)$n['notification_id']; ?>"
           class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 px-4 <?php echo $n['is_read'] ? '' : 'bg-light border-start border-4 border-primary'; ?>">
          
          <div class="rounded-circle p-2 d-inline-flex flex-shrink-0"
               style="background: <?php echo $n['is_read'] ? '#f1f5f9' : 'var(--ss-primary-50)'; ?>; color: <?php echo $n['is_read'] ? '#64748b' : 'var(--ss-primary)'; ?>;">
            <i data-lucide="<?php echo $n['is_read'] ? 'bell' : 'bell-ring'; ?>" style="width: 18px; height: 18px;"></i>
          </div>

          <div class="flex-grow-1">
            <div class="<?php echo $n['is_read'] ? 'text-secondary' : 'fw-bold text-dark'; ?>" style="font-size: 0.925rem;">
              <?php echo clean($n['message']); ?>
            </div>
            <div class="text-muted small mt-1 d-flex align-items-center gap-1">
              <i data-lucide="clock" style="width: 12px; height: 12px;"></i>
              <span><?php echo clean(date('M j, Y \a\t g:i A', strtotime($n['created_at']))); ?></span>
            </div>
          </div>

          <?php if (!$n['is_read']): ?>
            <span class="badge bg-primary flex-shrink-0">New</span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

