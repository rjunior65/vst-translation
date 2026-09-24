<?php
/* Public read-only endpoint: tourism destinations + gallery as JSON. */
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $dest = db()->query(
        "SELECT name, location_en, location_pt, location_es,
                description_en, description_pt, description_es, image
         FROM destinations WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
    )->fetchAll();

    $destinations = [];
    foreach ($dest as $r) {
        $destinations[] = [
            'name'  => $r['name'],
            'image' => $r['image'],
            'locations'    => ['en' => $r['location_en'], 'pt' => $r['location_pt'], 'es' => $r['location_es']],
            'descriptions' => ['en' => $r['description_en'] ?? '', 'pt' => $r['description_pt'] ?? '', 'es' => $r['description_es'] ?? ''],
        ];
    }

    $gallery = db()->query(
        "SELECT image, caption FROM gallery WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
    )->fetchAll();

    echo json_encode(['destinations' => $destinations, 'gallery' => $gallery], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('tourism.php: ' . $e->getMessage());
    echo json_encode(['error' => 'unavailable']);
}
