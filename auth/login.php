<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        setFlash('error', 'Please enter both your email and password.');
        header('Location: /auth/login.php');
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT user_id, full_name, email, password_hash, role, status FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Deliberately vague error message — never reveal whether the email exists.
    if (!$user || !password_verify($password, $user['password_hash'])) {
        setFlash('error', 'Invalid email or password.');
        header('Location: /auth/login.php');
        exit;
    }

    if ($user['status'] === 'suspended') {
        setFlash('error', 'This account has been suspended. Contact an administrator.');
        header('Location: /auth/login.php');
        exit;
    }

    // Regenerate session ID on login to prevent session fixation attacks.
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];

    header('Location: /dashboard.php');
    exit;
}

// GET request — show the dedicated Sign In page.
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Sign In';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4 py-md-5 animate-fade-in-up">
  <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
    <div class="card border-0 shadow-lg" style="border-radius: var(--ss-radius-xl); box-shadow: var(--ss-shadow-hover);">
      <div class="card-body p-4 p-sm-5">
        
        <!-- Header -->
        <div class="text-center mb-4">
          <div class="brand-icon-box mx-auto mb-3" style="width: 48px; height: 48px; border-radius: 14px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M8 3H5a2 2 0 0 0-2 2v3"/>
              <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
              <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
              <path d="M16 21h3a2 2 0 0 0 2-2v-3"/>
              <path d="m7 16 3-3 2 2 3-4 2 5"/>
            </svg>
          </div>
          <h2 class="h3 fw-bold mb-1" style="font-family: 'Sora', sans-serif;">Welcome Back</h2>
          <p class="text-muted small">Sign in to your SkillSwap student account</p>
        </div>

        <!-- Sign In Form -->
        <form method="POST" action="/auth/login.php">
          <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i data-lucide="mail" style="width: 18px; height: 18px;"></i>
              </span>
              <input type="email" id="email" name="email" class="form-control border-start-0 ps-1"
                     placeholder="student@college.edu" required autofocus autocomplete="email">
            </div>
          </div>

          <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
              </span>
              <input type="password" id="password" name="password" class="form-control border-start-0 ps-1"
                     placeholder="Enter your password" required autocomplete="current-password">
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <span>Sign In to Account</span>
            <i data-lucide="arrow-right" style="width: 17px; height: 17px;"></i>
          </button>
        </form>

        <!-- Switch to Register -->
        <div class="mt-4 pt-3 border-top text-center">
          <p class="text-muted small mb-0">
            Don't have an account? 
            <a href="/auth/register.php" class="fw-bold text-primary">Create an account</a>
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>

