<?php
/* ============================================================
   Public read-only endpoint: returns active employees as JSON.
   The public site (js/script.js) fetches this. No login needed.
   If this file/DB is unavailable, the site falls back to the
   built-in list in js/script.js, so it never breaks.
   ============================================================ */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $rows = db()->query(
        "SELECT name, position_en, position_pt, position_es, department,
                phone, email, description_en, description_pt, description_es, image
         FROM employees
         WHERE is_active = 1
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll();

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'name'         => $r['name'],
            'department'   => $r['department'],
            'phone'        => $r['phone'],
            'email'        => $r['email'],
            'image'        => $r['image'],
            'positions'    => [
                'en' => $r['position_en'] ?? '',
                'pt' => $r['position_pt'] ?? '',
                'es' => $r['position_es'] ?? '',
            ],
            'descriptions' => [
                'en' => $r['description_en'] ?? '',
                'pt' => $r['description_pt'] ?? '',
                'es' => $r['description_es'] ?? '',
            ],
        ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
} catch (Throwable $ex) {
    // Signal failure so the front-end keeps its static fallback.
    http_response_code(500);
    error_log('employees.php: ' . $ex->getMessage());
    echo json_encode(['error' => 'unavailable']);
}
