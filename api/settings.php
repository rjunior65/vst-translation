<?php
/* Public read-only endpoint: contacts + social links as JSON. */
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $rows = db()->query("SELECT skey, svalue FROM settings")->fetchAll();
    $out = [];
    foreach ($rows as $r) { $out[$r['skey']] = $r['svalue']; }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('settings.php: ' . $e->getMessage());
    echo json_encode(['error' => 'unavailable']);
}
