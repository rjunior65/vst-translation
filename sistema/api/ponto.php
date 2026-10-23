<?php
/* ============================================================
   POST /sistema/api/ponto.php — register a punch.
   Body (form or JSON): csrf, type, lat?, lng?, accuracy?, source?, photo? (file)
   The time is ALWAYS the server's (NOW()), never the phone's.
   ============================================================ */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/punch.php';
require_once __DIR__ . '/../inc/media.php';

header('Content-Type: application/json; charset=utf-8');

function reply(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, ['ok' => false, 'error' => 'method']);

// The phone sends a form (multipart, so a selfie can go along); JSON is still accepted.
$in = $_POST ?: json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in) || !$in) {
    $big = (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;   // over post_max_size → PHP drops everything
    reply($big ? 413 : 400, ['ok' => false, 'error' => 'bad_request', 'message' => $big ? t('img_err_size') : '']);
}

if (!is_string($in['csrf'] ?? null) || !hash_equals($_SESSION['csrf'] ?? '', $in['csrf'])) {
    reply(400, ['ok' => false, 'error' => 'csrf', 'message' => t('err_csrf')]);
}

$user = current_user();
if (!$user) reply(401, ['ok' => false, 'error' => 'login', 'message' => t('session_expired')]);
if ((int)$user['must_change_password'] === 1) reply(403, ['ok' => false, 'error' => 'password']);

$type = (string)($in['type'] ?? '');
if (!in_array($type, punch_types(), true)) reply(400, ['ok' => false, 'error' => 'type']);

// Coordinates are optional (GPS denied / unavailable), but must be sane when sent.
$lat = is_numeric($in['lat'] ?? null) ? (float)$in['lat'] : null;
$lng = is_numeric($in['lng'] ?? null) ? (float)$in['lng'] : null;
if ($lat === null || $lng === null || abs($lat) > 90 || abs($lng) > 180) { $lat = $lng = null; }
$accuracy = ($lat !== null && is_numeric($in['accuracy'] ?? null)) ? max(0, min(100000, (int)round($in['accuracy']))) : null;
$source   = ($in['source'] ?? '') === 'app' ? 'app' : 'web';

// Selfie: required, optional or off (admin setting). Saved before the punch so a bad photo refuses it.
$photoMode = setting('punch_photo', 'off');
$photoFile = $_FILES['photo'] ?? null;
$hasPhoto  = $photoFile && ($photoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
if ($photoMode === 'required' && !$hasPhoto) reply(422, ['ok' => false, 'error' => 'photo', 'message' => t('photo_needed')]);
$photoName = null;
if ($photoMode !== 'off' && $hasPhoto) {
    [$photoName, $perr] = image_save($photoFile, PT_PHOTO_DIR, 'p' . date('Ymd') . '_u' . (int)$user['id'] . '_' . bin2hex(random_bytes(6)), 800);
    if ($perr) reply(422, ['ok' => false, 'error' => 'photo', 'message' => t($perr)]);
}
$dropPhoto = function () use ($photoName) { if ($photoName) @unlink(PT_PHOTO_DIR . $photoName); };

$pdo = pt_db();
$pdo->beginTransaction();
try {
    // Lock this user's row so two quick taps can't both pass the sequence check.
    $pdo->prepare("SELECT id FROM pt_users WHERE id = ? FOR UPDATE")->execute([$user['id']]);

    $today = today_punches((int)$user['id']);
    $last  = $today ? end($today) : null;
    $allowed = next_actions($last['type'] ?? null);
    if (!in_array($type, $allowed, true)) {
        $pdo->rollBack();
        $dropPhoto();
        reply(409, ['ok' => false, 'error' => 'sequence', 'message' => t('err_sequence'), 'next' => $allowed]);
    }

    $c = classify_punch($user, $lat, $lng, $accuracy);
    if ($c['blocked']) {
        $pdo->rollBack();
        $dropPhoto();
        $msg = $c['status'] === 'no_gps'
            ? t('err_need_gps')
            : t('err_outside', ['d' => fmt_distance($c['distance']), 'place' => $c['location']['name'] ?? '']);
        reply(422, ['ok' => false, 'error' => $c['status'], 'message' => $msg]);
    }

    $pdo->prepare("INSERT INTO pt_punches (user_id, type, punched_at, lat, lng, accuracy_m, distance_m, status, photo_path, source, ip, user_agent)
                   VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$user['id'], $type, $lat, $lng, $accuracy, $c['distance'], $c['status'], $photoName, $source,
                   client_ip(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
    $id = (int)$pdo->lastInsertId();
    $time = $pdo->query("SELECT punched_at FROM pt_punches WHERE id = $id")->fetchColumn();
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $dropPhoto();
    error_log('punch failed: ' . $e->getMessage());
    reply(500, ['ok' => false, 'error' => 'server', 'message' => t('err_server')]);
}

$hhmm = date('H:i', strtotime($time));
$msg = t('punch_ok_' . $type, ['t' => $hhmm]);
if ($c['status'] === 'outside') $msg .= ' ' . t('punch_flag_outside', ['d' => fmt_distance($c['distance'])]);
if ($c['status'] === 'no_gps')  $msg .= ' ' . t('punch_flag_no_gps');
flash($c['status'] === 'ok' ? 'ok' : 'info', $msg);

reply(200, ['ok' => true, 'id' => $id, 'type' => $type, 'time' => $hhmm, 'status' => $c['status'],
            'distance' => $c['distance'], 'message' => $msg, 'next' => next_actions($type)]);
