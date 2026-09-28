<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $fullName = trim($name . ' ' . $surname);

    // --- Server-side validation (never trust the client) ---
    if ($name === '' || $surname === '') {
        setFlash('error', 'Please enter your full name.');
        header('Location: /auth/register.php');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please enter a valid email address.');
        header('Location: /auth/register.php');
        exit;
    }
    if (strlen($password) < 8) {
        setFlash('error', 'Password must be at least 8 characters.');
        header('Location: /auth/register.php');
        exit;
    }
    if ($password !== $confirm) {
        setFlash('error', 'Passwords do not match.');
        header('Location: /auth/register.php');
        exit;
    }

    $db = getDB();

    // Check for duplicate email
    $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        setFlash('error', 'An account with that email already exists.');
        header('Location: /auth/login.php');
        exit;
    }

    // Every new signup is a plain 'student' — is_verified starts at 0 and is only
    // set to 1 once an admin approves an uploaded certificate (see Phase 3).
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, is_verified) VALUES (?, ?, ?, ?, 0)'
    );
    $stmt->execute([$fullName, $email, $hash, 'student']);

    setFlash('success', 'Account created! You can now sign in.');
    header('Location: /auth/login.php');
    exit;
}

// GET request — show the dedicated registration page.
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Create Account';
require __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center py-4 py-md-5 animate-fade-in-up">
  <div class="col-12 col-sm-11 col-md-9 col-lg-7 col-xl-6">
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
          <div class="d-inline-flex align-items-center gap-1 badge bg-primary mb-2">
            <i data-lucide="sparkles" style="width: 12px; height: 12px;"></i>
            <span>Free Student Account</span>
          </div>
          <h2 class="h3 fw-bold mb-1" style="font-family: 'Sora', sans-serif;">Join SkillSwap</h2>
          <p class="text-muted small">Start trading knowledge with fellow students today</p>
        </div>

        <form method="POST" action="/auth/register.php" id="register-form">
          <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
              <label for="name" class="form-label">First Name</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                  <i data-lucide="user" style="width: 17px; height: 17px;"></i>
                </span>
                <input type="text" id="name" name="name" class="form-control border-start-0 ps-1"
                       placeholder="Arjun" required autofocus>
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label for="surname" class="form-label">Last Name</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                  <i data-lucide="user" style="width: 17px; height: 17px;"></i>
                </span>
                <input type="text" id="surname" name="surname" class="form-control border-start-0 ps-1"
                       placeholder="Sharma" required>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">College Email Address</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0 text-muted">
                <i data-lucide="mail" style="width: 17px; height: 17px;"></i>
              </span>
              <input type="email" id="email" name="email" class="form-control border-start-0 ps-1"
                     placeholder="arjun@college.edu" required autocomplete="email">
            </div>
          </div>

          <div class="p-3 mb-3 rounded-3" style="background: var(--ss-primary-50); border: 1px solid var(--ss-primary-100);">
            <div class="d-flex align-items-start gap-2 text-primary small">
              <i data-lucide="shield-check" style="width: 18px; height: 18px; flex-shrink: 0; margin-top: 1px;"></i>
              <div>
                Every new member joins as a Student. Upload a course certificate after signing in to unlock the <strong>Verified Teacher</strong> badge!
              </div>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                  <i data-lucide="lock" style="width: 17px; height: 17px;"></i>
                </span>
                <input type="password" id="password" name="password" class="form-control border-start-0 ps-1"
                       placeholder="Min. 8 characters" required minlength="8" autocomplete="new-password">
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted">
                  <i data-lucide="lock" style="width: 17px; height: 17px;"></i>
                </span>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control border-start-0 ps-1"
                       placeholder="Re-enter password" required minlength="8" autocomplete="new-password">
              </div>
            </div>
          </div>

          <div id="register-error" class="alert alert-danger d-none mb-3"></div>

          <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
            <span>Create Free Account</span>
            <i data-lucide="arrow-right" style="width: 17px; height: 17px;"></i>
          </button>
        </form>

        <!-- Switch to Sign In -->
        <div class="mt-4 pt-3 border-top text-center">
          <p class="text-muted small mb-0">
            Already have an account? 
            <a href="/auth/login.php" class="fw-bold text-primary">Sign in</a>
          </p>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('register-form').addEventListener('submit', function (e) {
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    const err = document.getElementById('register-error');
    if (pw !== cpw) {
      e.preventDefault();
      err.textContent = 'Passwords do not match.';
      err.classList.remove('d-none');
    }
  });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

