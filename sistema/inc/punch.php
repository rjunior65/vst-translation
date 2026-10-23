<?php
/* ============================================================
   VST Ponto — clock-in rules (sequence, geofence, settings).
   Shared by the punch API and the screens.
   ============================================================ */

function punch_types(): array { return ['in', 'break_start', 'break_end', 'out']; }

/* What can come after the last punch of the day.
   Several breaks are allowed; after "out" a new shift can start. */
function next_actions(?string $lastType): array {
    switch ($lastType) {
        case 'in':
        case 'break_end':   return ['break_start', 'out'];
        case 'break_start': return ['break_end'];
        default:            return ['in'];          // nothing yet, or after "out"
    }
}

/* Today's valid (non-voided) punches, oldest first. "Today" = server date in Guyana. */
function today_punches(int $userId): array {
    $st = pt_db()->prepare("SELECT * FROM pt_punches
                            WHERE user_id = ? AND is_void = 0 AND punched_at >= CURDATE() AND punched_at < CURDATE() + INTERVAL 1 DAY
                            ORDER BY punched_at, id");
    $st->execute([$userId]);
    return $st->fetchAll();
}

/* ---- Settings (pt_settings key/value) ---- */
function setting(string $key, string $default = ''): string {
    static $all = null;
    if ($all === null) {
        $all = pt_db()->query("SELECT skey, svalue FROM pt_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return array_key_exists($key, $all) ? (string)$all[$key] : $default;
}

function active_locations(): array {
    return pt_db()->query("SELECT * FROM pt_locations WHERE is_active = 1 ORDER BY name")->fetchAll();
}

/* Great-circle distance in metres. */
function distance_m(float $lat1, float $lng1, float $lat2, float $lng2): int {
    $r = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return (int)round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
}

/* The location a user is checked against: their assigned one, or else the
   nearest active one. Null when no location is registered yet. */
function location_for(array $user, ?float $lat, ?float $lng): ?array {
    $locs = active_locations();
    foreach ($locs as $l) {
        if ((int)$l['id'] === (int)$user['location_id']) return $l;
    }
    if (!$locs) return null;
    if ($lat === null || $lng === null) return $locs[0];
    usort($locs, fn($a, $b) => distance_m($lat, $lng, (float)$a['lat'], (float)$a['lng'])
                           <=> distance_m($lat, $lng, (float)$b['lat'], (float)$b['lng']));
    return $locs[0];
}

/* Decide how a punch is classified.
   Returns ['status' => ok|outside|no_gps, 'distance' => ?int, 'location' => ?array, 'blocked' => bool]
   - office:   must be inside the radius (outside → flagged, or refused when policy = block)
   - field:    anywhere is fine, GPS is just recorded
   - flexible: GPS optional
   No registered location yet → nobody is blocked, GPS is just recorded. */
function classify_punch(array $user, ?float $lat, ?float $lng, ?int $accuracy): array {
    $hasGps   = $lat !== null && $lng !== null;
    $mode     = $user['work_mode'];
    $loc      = location_for($user, $lat, $lng);
    $distance = ($hasGps && $loc) ? distance_m($lat, $lng, (float)$loc['lat'], (float)$loc['lng']) : null;
    $block    = setting('outside_policy', 'flag') === 'block';

    if (!$hasGps) {
        if ($mode === 'flexible') return ['status' => 'ok', 'distance' => null, 'location' => $loc, 'blocked' => false];
        return ['status' => 'no_gps', 'distance' => null, 'location' => $loc,
                'blocked' => $mode === 'office' && $loc !== null && $block];
    }
    if ($mode !== 'office' || $loc === null) {
        return ['status' => 'ok', 'distance' => $distance, 'location' => $loc, 'blocked' => false];
    }
    // Poor accuracy can't prove the person is outside: give them the benefit of the reading's margin,
    // capped by the configured limit so a 5 km "accuracy" doesn't let anyone in.
    $margin = min((int)$accuracy, (int)setting('min_gps_accuracy', '100'));
    $inside = $distance - max(0, $margin) <= (int)$loc['radius_m'];
    return ['status' => $inside ? 'ok' : 'outside', 'distance' => $distance, 'location' => $loc,
            'blocked' => !$inside && $block];
}

function fmt_distance(?int $m): string {
    if ($m === null) return '—';
    return $m >= 1000 ? number_format($m / 1000, 1, ',', '.') . ' km' : $m . ' m';
}
