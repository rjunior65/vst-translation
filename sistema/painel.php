<?php
/* Employee home: clock in/out with GPS + today's punches. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/punch.php';

$user  = require_user();
$today = today_punches((int)$user['id']);
$last  = $today ? end($today) : null;
$next  = next_actions($last['type'] ?? null);

$state = 'off';
if ($last && in_array($last['type'], ['in', 'break_end'], true)) $state = 'working';
if ($last && $last['type'] === 'break_start') $state = 'break';
if ($last && $last['type'] === 'out') $state = 'done';

// Locations the phone measures distance to (assigned one, else all active).
$locs = active_locations();
$mine = array_values(array_filter($locs, fn($l) => (int)$l['id'] === (int)$user['location_id']));
$jsLocs = array_map(fn($l) => ['name' => $l['name'], 'lat' => (float)$l['lat'], 'lng' => (float)$l['lng'],
                               'radius' => (int)$l['radius_m']], $mine ?: $locs);

$config = [
    'csrf'      => csrf_token(),
    'api'       => 'api/ponto.php',
    'serverNow' => time() * 1000,
    'mode'      => $user['work_mode'],
    'locations' => $jsLocs,
    'maxAcc'    => (int)setting('min_gps_accuracy', '100'),
    'i18n'      => [
        'locating'    => t('gps_locating'),
        'denied'      => t('gps_denied'),
        'unavailable' => t('gps_unavailable'),
        'distance'    => t('gps_distance'),
        'inside'      => t('gps_inside'),
        'outside'     => t('gps_outside'),
        'here'        => t('gps_here'),
        'weak'        => t('gps_weak'),
        'sending'     => t('punch_sending'),
        'failed'      => t('err_server'),
        'confirm_out' => t('confirm_out'),
    ],
];

page_head(t('my_punch'), $user);
?>
  <section class="card punch-card state-<?= e($state) ?>">
    <p class="muted punch-hello"><?= e(t('hello', ['name' => explode(' ', $user['full_name'])[0]])) ?></p>
    <div class="clock" id="clock"><?= e(date('H:i:s')) ?></div>
    <p class="muted clock-date"><?= e(date('d/m/Y')) ?></p>

    <p class="state-badge"><?= e(t('state_' . $state)) ?></p>

    <div class="punch-actions">
      <?php foreach ($next as $type): ?>
        <button type="button" class="btn-punch punch-<?= e($type) ?>" data-type="<?= e($type) ?>"><?= e(t('btn_' . $type)) ?></button>
      <?php endforeach; ?>
    </div>

    <p class="gps-line" id="gps"><?= e(t('gps_locating')) ?></p>
    <p class="alert alert-error" id="punch-error" hidden></p>
  </section>

  <h2 class="section-title"><?= e(t('today')) ?></h2>
  <?php if (!$today): ?>
    <p class="muted"><?= e(t('no_punches_today')) ?></p>
  <?php else: ?>
    <ul class="punch-list">
      <?php foreach (array_reverse($today) as $p): ?>
        <li>
          <span class="punch-time"><?= e(date('H:i', strtotime($p['punched_at']))) ?></span>
          <span class="punch-type"><?= e(t('type_' . $p['type'])) ?></span>
          <?php if ($p['status'] !== 'ok'): ?><span class="tag warn"><?= e(t('st_' . $p['status'])) ?></span><?php endif; ?>
          <?php if ($p['distance_m'] !== null): ?><span class="muted punch-dist"><?= e(fmt_distance((int)$p['distance_m'])) ?></span><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <script>window.PONTO = <?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
  <script src="assets/ponto.js"></script>
<?php page_foot();
