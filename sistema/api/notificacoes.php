<?php
/* ============================================================
   Notifications API (JSON)
   GET  ?action=chave       → {publicKey}           VAPID key for subscribing
   POST ?action=assinar     {csrf, endpoint, keys}  save this phone's subscription
   POST ?action=cancelar    {csrf, endpoint}        remove it
   POST ?action=teste       {csrf}                  send a test notification to myself
   GET  ?action=pendentes   → {items}               called by the service worker on push
   ============================================================ */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/push.php';

header('Content-Type: application/json; charset=utf-8');

function reply(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';
$user = current_user();

if ($action === 'pendentes') {
    // Signed out on this phone: the worker shows a generic notification instead.
    if (!$user) reply(200, ['ok' => true, 'items' => [], 'generic' => t('notif_generic')]);
    $st = pt_db()->prepare("SELECT id, kind, title, body, url FROM pt_notifications
                            WHERE user_id = ? AND delivered_at IS NULL AND created_at > NOW() - INTERVAL 1 DAY
                            ORDER BY id DESC LIMIT 5");
    $st->execute([$user['id']]);
    $items = $st->fetchAll();
    if ($items) {
        $ids = implode(',', array_map('intval', array_column($items, 'id')));
        pt_db()->exec("UPDATE pt_notifications SET delivered_at = NOW() WHERE id IN ($ids)");
    }
    // One notification per conversation / reminder (newer replaces older on the phone).
    foreach ($items as &$n) $n['tag'] = $n['kind'] === 'chat' ? 'chat-' . md5($n['url']) : $n['kind'];
    reply(200, ['ok' => true, 'items' => array_reverse($items), 'generic' => t('notif_generic')]);
}

if (!$user) reply(401, ['ok' => false, 'error' => 'login']);
if ((int)$user['must_change_password'] === 1) reply(403, ['ok' => false, 'error' => 'password']);

if ($action === 'chave') {
    $keys = vapid_keys();
    if (!$keys) reply(500, ['ok' => false, 'message' => t('notif_unavailable')]);
    reply(200, ['ok' => true, 'publicKey' => $keys[1]]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, ['ok' => false]);
$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in) || !is_string($in['csrf'] ?? null) || !hash_equals($_SESSION['csrf'] ?? '', $in['csrf'])) {
    reply(400, ['ok' => false, 'message' => t('err_csrf')]);
}

if ($action === 'assinar' || $action === 'cancelar') {
    $endpoint = (string)($in['endpoint'] ?? '');
    // The server will POST to this URL, so only the browsers' real push services are accepted.
    $host = strtolower((string)parse_url($endpoint, PHP_URL_HOST));
    $known = '/(^|\.)(fcm\.googleapis\.com|push\.services\.mozilla\.com|notify\.windows\.com|push\.apple\.com)$/';
    if (strpos($endpoint, 'https://') !== 0 || !preg_match($known, $host) || strlen($endpoint) > 1000) {
        reply(400, ['ok' => false, 'error' => 'endpoint']);
    }
    $hash = hash('sha256', $endpoint);
    if ($action === 'cancelar') {
        pt_db()->prepare("DELETE FROM pt_push_subscriptions WHERE endpoint_hash = ? AND user_id = ?")->execute([$hash, $user['id']]);
        reply(200, ['ok' => true]);
    }
    $p256 = substr((string)($in['keys']['p256dh'] ?? ''), 0, 200);
    $auth = substr((string)($in['keys']['auth'] ?? ''), 0, 100);
    // Same phone signing in as someone else: the subscription moves to the new person.
    pt_db()->prepare("INSERT INTO pt_push_subscriptions (user_id, endpoint_hash, endpoint, p256dh, auth, user_agent)
                      VALUES (?, ?, ?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth)")
        ->execute([$user['id'], $hash, $endpoint, $p256, $auth, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
    reply(200, ['ok' => true]);
}

if ($action === 'teste') {
    notify([(int)$user['id']], 'test', t('notif_test_title'), t('notif_test_body'), 'painel.php');
    reply(200, ['ok' => true, 'message' => t('notif_test_sent')]);
}

reply(400, ['ok' => false, 'error' => 'action']);
