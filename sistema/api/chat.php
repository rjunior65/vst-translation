<?php
/* ============================================================
   Chat API (JSON)
   GET  ?action=unread                  → {unread}
   GET  ?action=poll&c=ID&after=N       → {html, last, unread, typing}
   POST ?action=typing&c=ID  (csrf)     → {ok}   "I'm typing" (shown to the others for a few seconds)
   POST ?action=send&c=ID  (multipart: csrf, body, files[]) → {ok, html, last}
   ============================================================ */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/chat.php';
require_once __DIR__ . '/../inc/media.php';

header('Content-Type: application/json; charset=utf-8');

function reply(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$user = current_user();
if (!$user) reply(401, ['ok' => false, 'error' => 'login']);
if ((int)$user['must_change_password'] === 1) reply(403, ['ok' => false, 'error' => 'password']);

$action = $_GET['action'] ?? '';
if ($action === 'unread') {
    session_write_close();   // don't hold the session lock while other requests run
    reply(200, ['ok' => true, 'unread' => chat_unread_total($user)]);
}

$conv = chat_get((int)($_GET['c'] ?? 0), $user);
if (!$conv) reply(404, ['ok' => false, 'error' => 'not_found']);

/* Messages after $after as HTML, with day separators when the day changes. */
function render_after(array $conv, array $user, int $after, ?string $prevDay): array {
    $html = '';
    $last = $after;
    foreach (chat_messages((int)$conv['id'], $after) as $m) {
        $d = substr($m['created_at'], 0, 10);
        if ($d !== $prevDay) { $html .= '<div class="day-sep" data-day="' . e($d) . '"><span>' . e(chat_day_label($d)) . '</span></div>'; $prevDay = $d; }
        $html .= chat_render_message($m, $user, '');
        $last = (int)$m['id'];
    }
    if ($last > $after) chat_mark_read((int)$conv['id'], (int)$user['id'], $last);
    return [$html, $last];
}
$prevDay = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['day'] ?? '') ? $_GET['day'] : null;

/* Who else is typing in this conversation right now (pinged in the last 6 s). */
function typing_html(array $conv, array $user): string {
    $st = pt_db()->prepare("SELECT u.id, u.full_name, u.photo, u.employee_id FROM pt_chat_typing t JOIN pt_users u ON u.id = t.user_id
                            WHERE t.conversation_id = ? AND t.user_id <> ? AND t.at > NOW() - INTERVAL 6 SECOND ORDER BY t.at DESC LIMIT 3");
    $st->execute([$conv['id'], $user['id']]);
    $who = $st->fetchAll();
    if (!$who) return '';
    $names = implode(', ', array_map(fn($u) => explode(' ', $u['full_name'])[0], $who));
    return avatar_html($who[0], 'xs') . '<span>' . e(t(count($who) > 1 ? 'typing_many' : 'typing_one', ['n' => $names])) . '</span>'
         . '<span class="dots"><i></i><i></i><i></i></span>';
}

if ($action === 'poll') {
    session_write_close();
    [$html, $last] = render_after($conv, $user, max(0, (int)($_GET['after'] ?? 0)), $prevDay);
    reply(200, ['ok' => true, 'html' => $html, 'last' => $last, 'unread' => chat_unread_total($user),
                'typing' => typing_html($conv, $user)]);
}

if ($action === 'typing' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) reply(400, ['ok' => false]);
    session_write_close();
    pt_db()->prepare("REPLACE INTO pt_chat_typing (conversation_id, user_id, at) VALUES (?, ?, NOW())")->execute([$conv['id'], $user['id']]);
    reply(200, ['ok' => true]);
}

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // A post bigger than post_max_size arrives with empty $_POST: report it as "too big".
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        reply(413, ['ok' => false, 'message' => t('chat_err_size', ['mb' => PT_CHAT_MAX_BYTES / 1048576])]);
    }
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) reply(400, ['ok' => false, 'message' => t('err_csrf')]);

    $after = max(0, (int)($_GET['after'] ?? 0));
    [$id, $err] = chat_send($conv, $user, (string)($_POST['body'] ?? ''), chat_uploaded_files());
    pt_db()->prepare("DELETE FROM pt_chat_typing WHERE conversation_id = ? AND user_id = ?")->execute([$conv['id'], $user['id']]);
    if ($err) reply(422, ['ok' => false, 'message' => t($err, ['mb' => PT_CHAT_MAX_BYTES / 1048576, 'n' => PT_CHAT_MAX_FILES])]);
    // Return everything new since what the page already shows (includes messages that arrived meanwhile).
    [$html, $last] = render_after($conv, $user, $after, $prevDay);
    reply(200, ['ok' => true, 'id' => $id, 'html' => $html, 'last' => $last]);
}

reply(400, ['ok' => false, 'error' => 'action']);
