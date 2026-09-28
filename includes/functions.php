<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Security / sanitization ---
function clean($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// --- Auth guards ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }

    // Re-check status on every request so a suspension takes effect immediately,
    // not just the next time the person tries to log in.
    static $checked = false;
    if (!$checked) {
        $checked = true;
        $stmt = getDB()->prepare('SELECT status FROM users WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $status = $stmt->fetchColumn();

        if ($status !== 'active') {
            $_SESSION = [];
            session_destroy();
            session_start();
            setFlash('error', 'This account has been suspended. Contact an administrator.');
            header('Location: /auth/login.php');
            exit;
        }
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /index.php');
        exit;
    }
}

// --- Flash messages (one-time alerts shown after redirect) ---
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// --- Current user helper ---
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}
