<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

// ---------- Output / redirect ----------
function redirect(string $url): void { header("Location: $url"); exit; }
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---------- Session user ----------
function isLoggedIn(): bool { return !empty($_SESSION['user_id']); }

function currentUser(): ?array {
    static $u = null;
    if ($u !== null) return $u;
    if (!isLoggedIn()) return null;
    $u = db_one('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
    return $u;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/';
        redirect(SITE_URL . '/login.php');
    }
    // session idle timeout
    if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset(); session_destroy();
        redirect(SITE_URL . '/login.php?timeout=1');
    }
    $_SESSION['last_activity'] = time();
}

// ---------- Flash messages ----------
function setFlash(string $type, string $msg): void { $_SESSION['flash'][$type] = $msg; }
function getFlash(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ---------- Formatting ----------
function formatDate(?string $d, string $fmt = 'd M Y'): string {
    return $d ? date($fmt, strtotime($d)) : '—';
}
function formatCurrency(float $n, string $c = '₹'): string { return $c . number_format($n, 2); }

// ---------- Notifications ----------
function createNotification(int $userId, string $title, string $message, string $link = null, string $type = 'info'): void {
    db_insert('notifications', [
        'user_id' => $userId, 'title' => $title, 'message' => $message,
        'link'    => $link,   'type'  => $type
    ]);
}

// ---------- Random tokens ----------
function generateToken(int $len = 32): string { return bin2hex(random_bytes($len / 2)); }

// ---------- Settings ----------
function setting(string $key, $default = null) {
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    $row = db_one('SELECT svalue FROM settings WHERE skey = ?', [$key]);
    return $cache[$key] = $row ? $row['svalue'] : $default;
}