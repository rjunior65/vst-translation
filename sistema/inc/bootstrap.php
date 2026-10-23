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
// Private files (chat attachments, punch selfies, profile photos) live OUTSIDE public_html,
// like the website's CVs: /home/<user>/vst-private/. Overridable in includes/config.local.php.
if (!defined('PT_PRIVATE_DIR')) define('PT_PRIVATE_DIR', dirname(__DIR__, 3) . '/vst-private/');

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
    if (empty($_SESSION['pt_uid'])) remember_login();
    if (!empty($_SESSION['pt_uid'])) {
        $st = pt_db()->prepare("SELECT * FROM pt_users WHERE id = ? AND is_active = 1 LIMIT 1");
        $st->execute([(int)$_SESSION['pt_uid']]);
        $user = $st->fetch() ?: null;
        if (!$user) logout_user();
        // Remember the language the person uses, for notifications sent while they're away.
        if ($user && ($user['lang'] ?? current_lang()) !== current_lang()) {
            pt_db()->prepare("UPDATE pt_users SET lang = ? WHERE id = ?")->execute([current_lang(), $user['id']]);
            $user['lang'] = current_lang();
        }
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
/* ---- "Keep me signed in" (30 days on this device) ----
   PHP sessions die after ~24 idle minutes on shared hosting; on a phone
   that would mean signing in again all day. The cookie holds
   selector:validator, the database only a hash of the validator. */
define('PT_REMEMBER_COOKIE', 'vst_ponto_rem');
define('PT_REMEMBER_DAYS', 30);

function remember_cookie(string $value, int $expires): void {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    setcookie(PT_REMEMBER_COOKIE, $value, ['expires' => $expires, 'path' => '/', 'httponly' => true,
                                           'secure' => $https, 'samesite' => 'Lax']);
}
function remember_issue(int $userId): void {
    $selector  = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires   = time() + PT_REMEMBER_DAYS * 86400;
    try {
        pt_db()->prepare("INSERT INTO pt_remember (user_id, selector, validator_hash, expires_at, user_agent) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expires),
                       substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
    } catch (PDOException $e) {
        return;   // table not created yet (database not updated after an upgrade): plain session only
    }
    remember_cookie("$selector:$validator", $expires);
}
function remember_parts(): ?array {
    $c = $_COOKIE[PT_REMEMBER_COOKIE] ?? '';
    return preg_match('/^([0-9a-f]{24}):([0-9a-f]{64})$/', $c, $m) ? [$m[1], $m[2]] : null;
}
function remember_login(): void {
    if (!$p = remember_parts()) return;
    try {
        $st = pt_db()->prepare("SELECT r.user_id, r.validator_hash FROM pt_remember r JOIN pt_users u ON u.id = r.user_id
                                WHERE r.selector = ? AND r.expires_at > NOW() AND u.is_active = 1");
        $st->execute([$p[0]]);
        $row = $st->fetch();
    } catch (PDOException $e) {
        return;
    }
    if ($row && hash_equals($row['validator_hash'], hash('sha256', $p[1]))) {
        session_regenerate_id(true);
        $_SESSION['pt_uid'] = (int)$row['user_id'];
    } else {
        remember_cookie('', time() - 3600);   // stale or forged cookie
    }
}
/* Sign out every device of a user (password reset), optionally keeping this one. */
function remember_forget_user(int $userId, bool $keepThisDevice = false): void {
    $p = $keepThisDevice ? remember_parts() : null;
    try {
        pt_db()->prepare("DELETE FROM pt_remember WHERE user_id = ? AND selector <> ?")->execute([$userId, $p[0] ?? '']);
    } catch (PDOException $e) {
        // table not created yet: nothing to forget
    }
}

function logout_user(): void {
    if ($p = remember_parts()) {
        pt_db()->prepare("DELETE FROM pt_remember WHERE selector = ?")->execute([$p[0]]);
        remember_cookie('', time() - 3600);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function home_for(array $u): string {
    return 'painel.php';   // everyone starts on the punch screen
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
