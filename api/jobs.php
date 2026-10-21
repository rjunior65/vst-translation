<?php
/* ============================================================
   Public read-only endpoint: returns active job openings as JSON.
   If unavailable, the site falls back to the list in js/script.js.
   ============================================================ */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $rows = db()->query(
        "SELECT id, title_en, title_pt, title_es, description_en, description_pt, description_es
         FROM jobs WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
    )->fetchAll();

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'     => (int)$r['id'],
            'titles' => ['en' => $r['title_en'], 'pt' => $r['title_pt'] ?? '', 'es' => $r['title_es'] ?? ''],
            'descriptions' => [
                'en' => $r['description_en'] ?? '',
                'pt' => $r['description_pt'] ?? '',
                'es' => $r['description_es'] ?? '',
            ],
        ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $ex) {
    http_response_code(500);
    error_log('jobs.php: ' . $ex->getMessage());
    echo json_encode(['error' => 'unavailable']);
}
