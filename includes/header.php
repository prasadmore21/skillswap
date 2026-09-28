<?php
require_once __DIR__ . '/functions.php';
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$currentDir  = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$isAdminPage = ($currentDir === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? clean($pageTitle) . ' &mdash; SkillSwap' : 'SkillSwap &mdash; Student Skill Exchange'; ?></title>
    
    <!-- Google Fonts: Inter & Sora -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- SkillSwap Modern SaaS Stylesheet -->
    <link rel="stylesheet" href="/assets/css/style.css?v=<?php echo time(); ?>">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>

<!-- ================================================================
     SAAS HEADER / NAVIGATION
================================================================ -->
<nav class="navbar navbar-expand-lg ss-navbar">
  <div class="container">
    <!-- Brand Logo -->
    <a class="navbar-brand" href="<?php echo isLoggedIn() ? '/dashboard.php' : '/index.php'; ?>">
      <div class="brand-icon-box">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 3H5a2 2 0 0 0-2 2v3"/>
          <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
          <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
          <path d="M16 21h3a2 2 0 0 0 2-2v-3"/>
          <path d="m7 16 3-3 2 2 3-4 2 5"/>
        </svg>
      </div>
      <span>Skill<span style="color:var(--ss-accent)">Swap</span></span>
    </a>

    <!-- Mobile Hamburger -->
    <button class="navbar-toggler border-0 shadow-none p-1" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu" aria-label="Toggle navigation">
      <i data-lucide="menu" class="w-6 h-6 text-slate-700"></i>
    </button>

    <!-- Nav Items -->
    <div class="collapse navbar-collapse mt-2 mt-lg-0" id="navMenu">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <?php if (isLoggedIn()): ?>
          <?php
            $stmt = getDB()->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0');
            $stmt->execute([currentUserId()]);
            $unreadCount = (int)$stmt->fetch()['c'];
          ?>
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>" href="/dashboard.php">
              <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'matches.php') ? 'active' : ''; ?>" href="/matches.php">
              <i data-lucide="sparkles" style="width:17px;height:17px;color:var(--ss-accent)"></i>
              <span>Find Match</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'exchange_requests.php' || $currentPage === 'request_form.php') ? 'active' : ''; ?>" href="/exchange_requests.php">
              <i data-lucide="arrow-left-right" style="width:17px;height:17px;"></i>
              <span>Requests</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'sessions.php' || $currentPage === 'session_form.php' || $currentPage === 'rate_session.php') ? 'active' : ''; ?>" href="/sessions.php">
              <i data-lucide="calendar" style="width:17px;height:17px;"></i>
              <span>Sessions</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'certificates.php') ? 'active' : ''; ?>" href="/certificates.php">
              <i data-lucide="award" style="width:17px;height:17px;"></i>
              <span>Certificates</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'notifications.php') ? 'active' : ''; ?>" href="/notifications.php">
              <i data-lucide="bell" style="width:17px;height:17px;"></i>
              <span>Notifications</span>
              <?php if ($unreadCount > 0): ?>
                <span class="nav-badge"><?php echo $unreadCount; ?></span>
              <?php endif; ?>
            </a>
          </li>

          <?php if (isAdmin()): ?>
            <li class="nav-item ms-lg-1">
              <a class="nav-link text-primary <?php echo $isAdminPage ? 'active' : ''; ?>" href="/admin/index.php" style="font-weight:600;">
                <i data-lucide="shield-check" style="width:17px;height:17px;"></i>
                <span>Admin</span>
              </a>
            </li>
          <?php endif; ?>

          <!-- User Dropdown -->
          <li class="nav-item dropdown ms-lg-2 mt-2 mt-lg-0">
            <button class="ss-user-dropdown-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="avatar-initials d-inline-flex align-items-center justify-content-center rounded-circle"
                    style="width:28px;height:28px;background:var(--ss-primary-100);color:var(--ss-primary-active);font-size:0.75rem;font-weight:700;">
                <?php
                  $names = explode(' ', trim($_SESSION['full_name'] ?? 'User'));
                  $initials = strtoupper(substr($names[0] ?? 'U', 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                  echo clean($initials);
                ?>
              </span>
              <span class="d-inline-block text-truncate" style="max-width:140px;"><?php echo clean($_SESSION['full_name']); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end ss-dropdown-menu">
              <li>
                <div class="px-3 py-2">
                  <div class="fw-bold text-dark text-truncate"><?php echo clean($_SESSION['full_name']); ?></div>
                  <small class="text-muted text-capitalize"><?php echo clean($_SESSION['role'] ?? 'Student'); ?></small>
                </div>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="/profile.php">
                  <i data-lucide="user" style="width:16px;height:16px;"></i> My Profile
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="/certificates.php">
                  <i data-lucide="award" style="width:16px;height:16px;"></i> My Certificates
                </a>
              </li>
              <?php if (isAdmin()): ?>
                <li>
                  <a class="dropdown-item" href="/admin/index.php">
                    <i data-lucide="shield" style="width:16px;height:16px;"></i> Admin Console
                  </a>
                </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item text-danger" href="/auth/logout.php">
                  <i data-lucide="log-out" style="width:16px;height:16px;"></i> Sign Out
                </a>
              </li>
            </ul>
          </li>

        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?php echo ($currentPage === 'login.php') ? 'active' : ''; ?>" href="/auth/login.php">
              Sign In
            </a>
          </li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-primary btn-sm" href="/auth/register.php">
              Get Started
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Main App Container -->
<main class="main-content">
<div class="container py-4">

  <!-- Flash Notification Toast/Alert -->
  <?php $flash = getFlash(); ?>
  <?php if ($flash): ?>
    <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : 'success'; ?> alert-dismissible fade show animate-fade-in-up" role="alert">
      <div class="d-flex align-items-center gap-2">
        <?php if ($flash['type'] === 'error'): ?>
          <i data-lucide="alert-circle" style="width:20px;height:20px;flex-shrink:0;"></i>
        <?php else: ?>
          <i data-lucide="check-circle-2" style="width:20px;height:20px;flex-shrink:0;"></i>
        <?php endif; ?>
        <div><?php echo clean($flash['message']); ?></div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

