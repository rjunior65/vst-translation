<?php
/* ============================================================
   VST Ponto — chat between the administration and employees.
   - direct: one thread per employee with "the administration"
     (every admin can read and answer it)
   - group:  created by an admin, with chosen members
   Attachments are stored OUTSIDE public_html, like the website's CVs:
   /home/<user>/vst-private/chat/ — only arquivo.php can serve them.
   ============================================================ */

if (!defined('PT_CHAT_DIR')) define('PT_CHAT_DIR', dirname(__DIR__, 3) . '/vst-private/chat/');  // overridable in config.local.php
define('PT_CHAT_MAX_BYTES', 10 * 1024 * 1024);   // 10 MB per file
define('PT_CHAT_MAX_FILES', 5);                  // files per message
define('PT_CHAT_MAX_BODY', 5000);                // characters per message
define('PT_CHAT_MAX_PER_MIN', 30);               // messages per person per minute (anti-flood)

/* extension => accepted MIME types (checked on the real file content) */
function chat_allowed_types(): array {
    $ole = ['application/msword', 'application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-office'];
    return [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
        'heic' => ['image/heic', 'image/heif', 'application/octet-stream'],   // iPhone photos
        'doc'  => $ole, 'xls' => $ole,
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'txt'  => ['text/plain'],
    ];
}
/* What the browser gets when the file is served (never the uploaded claim). */
function chat_serve_type(string $ext): string {
    return [
        'pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'webp' => 'image/webp', 'gif' => 'image/gif', 'heic' => 'image/heic', 'txt' => 'text/plain; charset=utf-8',
        'doc' => 'application/msword', 'xls' => 'application/vnd.ms-excel',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ][$ext] ?? 'application/octet-stream';
}
function chat_is_image(string $ext): bool {
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
}

/* ---- Access ---- */
function chat_can_access(array $conv, array $user): bool {
    if ($user['role'] === 'admin') return true;
    if ($conv['type'] === 'direct') return (int)$conv['employee_user_id'] === (int)$user['id'];
    $st = pt_db()->prepare("SELECT 1 FROM pt_chat_members WHERE conversation_id = ? AND user_id = ?");
    $st->execute([$conv['id'], $user['id']]);
    return (bool)$st->fetchColumn();
}
function chat_get(int $id, array $user): ?array {
    $st = pt_db()->prepare("SELECT * FROM pt_chat_conversations WHERE id = ?");
    $st->execute([$id]);
    $c = $st->fetch();
    return ($c && chat_can_access($c, $user)) ? $c : null;
}

/* The employee's thread with the administration (created on first use). */
function chat_direct_for(int $employeeId, int $createdBy): int {
    $st = pt_db()->prepare("SELECT id FROM pt_chat_conversations WHERE type = 'direct' AND employee_user_id = ?");
    $st->execute([$employeeId]);
    if ($id = $st->fetchColumn()) return (int)$id;
    pt_db()->prepare("INSERT IGNORE INTO pt_chat_conversations (type, employee_user_id, created_by) VALUES ('direct', ?, ?)")
        ->execute([$employeeId, $createdBy]);
    $st->execute([$employeeId]);
    return (int)$st->fetchColumn();
}

function chat_title(array $conv, array $user): string {
    if ($conv['type'] === 'group') return (string)$conv['title'];
    if ($user['role'] !== 'admin') return t('administration');
    $st = pt_db()->prepare("SELECT full_name FROM pt_users WHERE id = ?");
    $st->execute([$conv['employee_user_id']]);
    return (string)$st->fetchColumn();
}

function chat_members(int $convId): array {
    $st = pt_db()->prepare("SELECT u.id, u.full_name FROM pt_chat_members m JOIN pt_users u ON u.id = m.user_id
                            WHERE m.conversation_id = ? ORDER BY u.full_name");
    $st->execute([$convId]);
    return $st->fetchAll();
}

/* Conversations visible to $user, newest activity first, with unread counts and a preview. */
function chat_list(array $user): array {
    $uid = (int)$user['id'];
    if ($user['role'] === 'admin') {
        $where = "(c.type = 'group' OR c.last_message_at IS NOT NULL)";
        $args = [];
    } else {
        $where = "(c.employee_user_id = ? OR c.id IN (SELECT conversation_id FROM pt_chat_members WHERE user_id = ?))";
        $args = [$uid, $uid];
    }
    $st = pt_db()->prepare("SELECT c.*,
            (SELECT COUNT(*) FROM pt_chat_messages m WHERE m.conversation_id = c.id AND m.sender_id <> ?
                AND m.id > IFNULL((SELECT r.last_read_id FROM pt_chat_reads r WHERE r.conversation_id = c.id AND r.user_id = ?), 0)) AS unread,
            (SELECT m.id FROM pt_chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_id
        FROM pt_chat_conversations c WHERE $where
        ORDER BY c.last_message_at IS NULL, c.last_message_at DESC, c.id DESC");
    $st->execute(array_merge([$uid, $uid], $args));
    $rows = $st->fetchAll();

    $last = pt_db()->prepare("SELECT m.body, m.created_at, u.full_name,
            (SELECT COUNT(*) FROM pt_chat_files f WHERE f.message_id = m.id) AS files
        FROM pt_chat_messages m JOIN pt_users u ON u.id = m.sender_id WHERE m.id = ?");
    foreach ($rows as &$r) {
        $r['title'] = chat_title($r, $user);
        $r['last'] = null;
        if ($r['last_id']) { $last->execute([$r['last_id']]); $r['last'] = $last->fetch(); }
    }
    return $rows;
}

function chat_unread_total(array $user): int {
    $n = 0;
    foreach (chat_list($user) as $c) $n += (int)$c['unread'];
    return $n;
}

function chat_mark_read(int $convId, int $userId, int $lastId): void {
    if ($lastId <= 0) return;
    pt_db()->prepare("INSERT INTO pt_chat_reads (conversation_id, user_id, last_read_id) VALUES (?, ?, ?)
                      ON DUPLICATE KEY UPDATE last_read_id = GREATEST(last_read_id, VALUES(last_read_id))")
        ->execute([$convId, $userId, $lastId]);
}

/* Messages after $afterId (oldest first), each with sender and files. */
function chat_messages(int $convId, int $afterId = 0, int $limit = 300): array {
    $limit = (int)$limit;
    $st = pt_db()->prepare("SELECT * FROM (
            SELECT m.*, u.full_name, u.role FROM pt_chat_messages m JOIN pt_users u ON u.id = m.sender_id
            WHERE m.conversation_id = ? AND m.id > ? ORDER BY m.id DESC LIMIT $limit) x ORDER BY id");
    $st->execute([$convId, $afterId]);
    $msgs = $st->fetchAll();
    if (!$msgs) return [];
    $ids = implode(',', array_map('intval', array_column($msgs, 'id')));
    $files = [];
    foreach (pt_db()->query("SELECT * FROM pt_chat_files WHERE message_id IN ($ids) ORDER BY id")->fetchAll() as $f) {
        $files[(int)$f['message_id']][] = $f;
    }
    foreach ($msgs as &$m) $m['files'] = $files[(int)$m['id']] ?? [];
    return $msgs;
}

/* $_FILES['files'] (multiple) → list of single-file arrays, skipping empty slots. */
function chat_uploaded_files(): array {
    $f = $_FILES['files'] ?? null;
    if (!$f || !is_array($f['name'])) return [];
    $out = [];
    foreach ($f['name'] as $i => $name) {
        if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $out[] = ['name' => (string)$name, 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => (int)$f['size'][$i]];
    }
    return $out;
}

/* Validates and stores a message. Returns [messageId, null] or [null, errorKey]. */
function chat_send(array $conv, array $user, string $body, array $files): array {
    $body = trim(str_replace("\r\n", "\n", $body));
    if ($body === '' && !$files) return [null, 'chat_err_empty'];
    if (mb_strlen($body) > PT_CHAT_MAX_BODY) return [null, 'chat_err_long'];
    if (count($files) > PT_CHAT_MAX_FILES) return [null, 'chat_err_count'];

    $st = pt_db()->prepare("SELECT COUNT(*) FROM pt_chat_messages WHERE sender_id = ? AND created_at > NOW() - INTERVAL 1 MINUTE");
    $st->execute([$user['id']]);
    if ((int)$st->fetchColumn() >= PT_CHAT_MAX_PER_MIN) return [null, 'chat_err_flood'];

    // Check every file before saving anything.
    $allowed = chat_allowed_types();
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
    $ok = [];
    foreach ($files as $f) {
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > PT_CHAT_MAX_BYTES) return [null, 'chat_err_size'];
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return [null, 'chat_err_upload'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) return [null, 'chat_err_type'];
        if ($finfo) {
            // If the real type can't be read, refuse: never fall back to trusting the extension.
            $mime = @finfo_file($finfo, $f['tmp_name']);
            if (!$mime) return [null, 'chat_err_upload'];
            if (!in_array($mime, $allowed[$ext], true)) return [null, 'chat_err_type'];
        }
        $ok[] = $f + ['ext' => $ext];
    }
    if ($ok && !is_dir(PT_CHAT_DIR) && !@mkdir(PT_CHAT_DIR, 0750, true)) return [null, 'chat_err_upload'];

    $pdo = pt_db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT INTO pt_chat_messages (conversation_id, sender_id, body, created_at) VALUES (?, ?, ?, NOW())")
            ->execute([$conv['id'], $user['id'], $body]);
        $msgId = (int)$pdo->lastInsertId();
        $insFile = $pdo->prepare("INSERT INTO pt_chat_files (message_id, conversation_id, stored_name, original_name, mime, size_bytes)
                                  VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($ok as $f) {
            $stored = 'c' . (int)$conv['id'] . '_' . date('Ymd') . '_' . bin2hex(random_bytes(10)) . '.' . $f['ext'];
            if (!move_uploaded_file($f['tmp_name'], PT_CHAT_DIR . $stored)) throw new RuntimeException('move failed');
            $clean = mb_substr(preg_replace('/[\x00-\x1F\x7F"\\\\\/]/u', '', basename($f['name'])), 0, 200);
            $insFile->execute([$msgId, $conv['id'], $stored, $clean ?: ('file.' . $f['ext']), chat_serve_type($f['ext']), $f['size']]);
        }
        $pdo->prepare("UPDATE pt_chat_conversations SET last_message_at = NOW() WHERE id = ?")->execute([$conv['id']]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('chat send failed: ' . $e->getMessage());
        return [null, 'chat_err_upload'];
    }
    chat_mark_read((int)$conv['id'], (int)$user['id'], $msgId);
    chat_notify($conv, $user, $body, count($ok));
    return [$msgId, null];
}

/* Phone notification for everyone in the conversation except the sender. */
function chat_notify(array $conv, array $sender, string $body, int $files): void {
    require_once __DIR__ . '/push.php';
    $admins = pt_db()->query("SELECT id FROM pt_users WHERE role = 'admin' AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    if ($conv['type'] === 'direct') {
        $to = $sender['role'] === 'admin' ? [(int)$conv['employee_user_id']] : $admins;
    } else {
        $to = array_merge($admins, array_column(chat_members((int)$conv['id']), 'id'));
    }
    $to = array_diff(array_map('intval', $to), [(int)$sender['id']]);
    $first = explode(' ', $sender['full_name'])[0];
    $title = $conv['type'] === 'group' ? $conv['title'] . ' · ' . $first : $first;
    $text = $body !== '' ? mb_strimwidth(str_replace("\n", ' ', $body), 0, 120, '…') : '📎 ' . t('attachment') . ($files > 1 ? " ×$files" : '');
    notify($to, 'chat', $title, $text, 'conversa.php?id=' . (int)$conv['id']);
}

function fmt_bytes(int $b): string {
    if ($b >= 1048576) return number_format($b / 1048576, 1, ',', '.') . ' MB';
    return max(1, (int)round($b / 1024)) . ' KB';
}

/* Plain text → safe HTML with clickable links and line breaks. */
function chat_text(string $s): string {
    $h = e($s);
    $h = preg_replace('~(https?://[^\s<]+[^\s<.,;:!?)\]])~i', '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $h);
    return nl2br($h, false);
}

/* One message bubble (day separators are added by the caller, using data-day). */
function chat_render_message(array $m, array $me, string $base = ''): string {
    $mine = (int)$m['sender_id'] === (int)$me['id'];
    $who  = e($m['full_name']) . ($m['role'] === 'admin' ? ' <span class="who-role">' . e(t('administration')) . '</span>' : '');
    $html = '<div class="msg ' . ($mine ? 'mine' : 'theirs') . '" data-id="' . (int)$m['id'] . '" data-day="' . e(substr($m['created_at'], 0, 10)) . '">';
    if (!$mine) $html .= '<div class="msg-who">' . $who . '</div>';
    $html .= '<div class="bubble">';
    foreach ($m['files'] as $f) {
        $ext = strtolower(pathinfo($f['stored_name'], PATHINFO_EXTENSION));
        $url = $base . 'arquivo.php?id=' . (int)$f['id'];
        if (chat_is_image($ext)) {
            $html .= '<a class="msg-img" href="' . e($url) . '" target="_blank"><img src="' . e($url) . '" alt="' . e($f['original_name']) . '" loading="lazy"></a>';
        } else {
            $html .= '<a class="msg-file" href="' . e($url . '&dl=1') . '"><span class="file-ext">' . e(strtoupper($ext)) . '</span>'
                   . '<span class="file-name">' . e($f['original_name']) . '<small>' . e(fmt_bytes((int)$f['size_bytes'])) . '</small></span></a>';
        }
    }
    if ($m['body'] !== '') $html .= '<div class="msg-text">' . chat_text($m['body']) . '</div>';
    $html .= '<span class="msg-time">' . e(date('H:i', strtotime($m['created_at']))) . '</span></div></div>';
    return $html;
}

function chat_day_label(string $date): string {
    if ($date === date('Y-m-d')) return t('today');
    if ($date === date('Y-m-d', strtotime('-1 day'))) return t('yesterday');
    return date('d/m/Y', strtotime($date));
}
