<?php
/* ============================================================
   VST Ponto — bootstrap. Include first on every page.
   Reuses the website's DB connection (includes/db.php + config),
   but keeps its OWN session so it never mixes with the site admin.
   ============================================================ */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/lang.php';

define('PT_DEFAULT_PASSWORD', 'vst123456');  // temporary password for new users / resets
define('PT_MIN_PASSWORD', 8);
define('PT_TZ', 'America/Guyana');           // UTC-4, no daylight saving
define('PT_SESSION_NAME', 'vst_ponto');

date_default_timezone_set(PT_TZ);
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    session_name(PT_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $https,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* Same clock for PHP and MySQL (server time is the only time that counts). */
function pt_db(): PDO {
    static $ready = false;
    $pdo = db();
    if (!$ready) {
        $pdo->exec("SET time_zone = '-04:00'");
        $ready = true;
    }
    return $pdo;
}

/* HTML-escape helper */
function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): void {
    header('Location: ' . $to);
    exit;
}

function client_ip(): string {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
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
        die(e(t('err_csrf')));
    }
}

/* ---- Flash messages (survive one redirect) ---- */
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = [$type, $msg];
}
function take_flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---- Login state ----
   The user row is re-read on every request, so deactivating a user or
   resetting a password takes effect immediately. */
function current_user(): ?array {
    static $user = false;
    if ($user !== false) return $user;
    $user = null;
    if (!empty($_SESSION['pt_uid'])) {
        $st = pt_db()->prepare("SELECT * FROM pt_users WHERE id = ? AND is_active = 1 LIMIT 1");
        $st->execute([(int)$_SESSION['pt_uid']]);
        $user = $st->fetch() ?: null;
        if (!$user) logout_user();
    }
    return $user;
}
function is_admin(): bool {
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

/* Base URL of /sistema/ relative to the current script (works from /sistema and /sistema/admin). */
function base(): string {
    return basename(dirname($_SERVER['SCRIPT_NAME'])) === 'admin' ? '../' : '';
}

/* Pages call one of these. Anyone with the temporary password is sent
   to the password page and can do nothing else until they change it. */
function require_user(): array {
    $u = current_user();
    if (!$u) redirect(base() . 'login.php');
    if ((int)$u['must_change_password'] === 1) redirect(base() . 'senha.php');
    return $u;
}
function require_admin(): array {
    $u = require_user();
    if ($u['role'] !== 'admin') redirect(base() . 'painel.php');
    return $u;
}

function login_user(array $u): void {
    session_regenerate_id(true);
    $_SESSION['pt_uid'] = (int)$u['id'];
    pt_db()->prepare("UPDATE pt_users SET last_login_at = NOW() WHERE id = ?")->execute([$u['id']]);
}
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function home_for(array $u): string {
    return $u['role'] === 'admin' ? 'admin/' : 'painel.php';
}

/* ---- Audit log: every admin change is recorded with old/new values ---- */
function audit(string $action, string $entity, ?int $entityId, ?array $old, ?array $new, string $reason = ''): void {
    $strip = function (?array $row) {
        if ($row === null) return null;
        unset($row['password_hash']);
        return json_encode($row, JSON_UNESCAPED_UNICODE);
    };
    $u = current_user();
    pt_db()->prepare("INSERT INTO pt_audit_log (actor_id, action, entity, entity_id, old_data, new_data, reason, ip)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$u ? (int)$u['id'] : 0, $action, $entity, $entityId, $strip($old), $strip($new), $reason, client_ip()]);
}

/* ---- Usernames: firstname.lastname, ASCII only ----
   Suffixes (Junior, Filho...) and particles (da, de...) are ignored.
   On a clash the middle-name initial is added (rakesh.c.edmund),
   then a number as last resort. */
function name_tokens(string $name): array {
    $map = ['á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n','ý'=>'y','ÿ'=>'y'];
    $s = strtr(mb_strtolower(trim($name), 'UTF-8'), $map);
    $s = preg_replace('/[^a-z\s]/', ' ', $s);
    $skip = ['junior','jr','filho','neto','sobrinho','segundo','ii','iii',
             'da','de','do','das','dos','e','del','la','las','los','van','von'];
    return array_values(array_filter(preg_split('/\s+/', $s), fn($w) => $w !== '' && !in_array($w, $skip, true)));
}
function username_taken(string $username, int $exceptId = 0): bool {
    $st = pt_db()->prepare("SELECT COUNT(*) FROM pt_users WHERE username = ? AND id <> ?");
    $st->execute([$username, $exceptId]);
    return (int)$st->fetchColumn() > 0;
}
/* $reserved: names already handed out but not saved yet (import preview). */
function generate_username(string $fullName, int $exceptId = 0, array $reserved = []): string {
    $t = name_tokens($fullName);
    if (!$t) return '';
    $free  = fn($u) => !isset($reserved[$u]) && !username_taken($u, $exceptId);
    $first = $t[0];
    $last  = count($t) > 1 ? $t[count($t) - 1] : '';
    $base  = $last !== '' ? "$first.$last" : $first;
    if ($free($base)) return $base;
    foreach (array_slice($t, 1, -1) as $middle) {
        $try = "$first.{$middle[0]}.$last";
        if ($free($try)) return $try;
    }
    for ($n = 2; ; $n++) {
        if ($free($base . $n)) return $base . $n;
    }
}
function valid_username(string $u): bool {
    return (bool)preg_match('/^[a-z][a-z0-9.]{1,78}[a-z0-9]$/', $u);
}

/* Returns an error key, or '' when the new password is acceptable. */
function password_problem(string $pass, string $confirm): string {
    if (mb_strlen($pass) < PT_MIN_PASSWORD) return 'pw_too_short';
    if ($pass === PT_DEFAULT_PASSWORD)     return 'pw_is_default';
    if (!preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) return 'pw_needs_mix';
    if ($pass !== $confirm)                return 'pw_mismatch';
    return '';
}

function work_modes(): array { return ['office', 'field', 'flexible']; }
function roles(): array      { return ['employee', 'admin']; }
