<?php
/* Session, login state, CSRF and small helpers. Include on every admin page. */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,      // sent only over HTTPS when available
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* HTML-escape helper */
function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ---- CSRF ---- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_verify(): void {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        die('Invalid or missing security token. Go back and try again.');
    }
}

/* ---- Login state ---- */
function is_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}
function login_admin(array $admin): void {
    session_regenerate_id(true);
    $_SESSION['admin_id']   = (int)$admin['id'];
    $_SESSION['admin_name'] = $admin['name'];
    $_SESSION['admin_email']= $admin['email'];
}
function logout_admin(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function current_admin_name(): string {
    return $_SESSION['admin_name'] ?? '';
}

/* Allowed departments (must match the filter buttons on the public site) */
function departments(): array {
    return ['Administration','Translation','Tourism','Documentation','Customer Service','Marketing','Other'];
}
