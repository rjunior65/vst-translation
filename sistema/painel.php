<?php
/* Employee home: clock in/out with GPS + today's punches. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/punch.php';
require_once __DIR__ . '/inc/hours.php';

$user  = require_user();
$today = today_punches((int)$user['id']);
$sched = schedules_all()[(int)$user['schedule_id']] ?? null;
$sum   = day_summary($today, $sched, date('Y-m-d'), time());
$last  = $today ? end($today) : null;
$next  = next_actions($last['type'] ?? null);

$state = 'off';
$photoMode = setting('punch_photo', 'off');   // off | optional | required

// The four times of the day: first clock-in, first break out, return from it, last clock-out.
$times = ['in' => null, 'break_start' => null, 'break_end' => null, 'out' => null];
$breaks = 0;
foreach ($today as $p) {
    $ts = strtotime($p['punched_at']);
    if ($p['type'] === 'break_start') $breaks++;
    if ($p['type'] === 'out') $times['out'] = $ts;                       // last one wins
    elseif ($times[$p['type']] === null) $times[$p['type']] = $ts;      // first one wins
}
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
    'photo'     => $photoMode,
    'counters'  => [
        'state'    => $state,
        'worked'   => $sum['worked'],
        'break'    => $sum['break'],
        'expected' => $sum['expected'],
        'otAfter'  => $sched ? (int)$sched['overtime_after_min'] * 60 : null,
    ],
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
        'photo_needed'=> t('photo_needed'),
    ],
];

page_head(t('my_punch'), $user);
?>
<div class="punch-wrap">
  <section class="card punch-card state-<?= e($state) ?>">
    <p class="muted punch-hello"><?= e(t('hello', ['name' => explode(' ', $user['full_name'])[0]])) ?></p>
    <div class="clock" id="clock"><?= e(date('H:i:s')) ?></div>
    <p class="muted clock-date"><?= e(date('d/m/Y')) ?></p>

    <p class="state-badge"><?= e(t('state_' . $state)) ?></p>

    <div class="punch-actions">
      <?php foreach ($next as $type): ?>
        <button type="button" class="btn-punch punch-<?= e($type) ?>" data-type="<?= e($type) ?>">
          <?php if ($photoMode === 'required'): ?><?= icon('camera') ?><?php endif; ?><?= e(t('btn_' . $type)) ?></button>
      <?php endforeach; ?>
    </div>
    <?php if ($photoMode !== 'off'): ?>
      <input type="file" id="punch-photo" accept="image/*" capture="user" hidden>
      <?php if ($photoMode === 'optional'): ?>
        <label class="photo-note"><input type="checkbox" id="photo-opt"> <?= icon('camera') ?> <?= e(t('photo_optional')) ?></label>
      <?php else: ?>
        <p class="photo-note"><?= icon('camera') ?> <?= e(t('photo_required_note')) ?></p>
      <?php endif; ?>
    <?php endif; ?>

    <p class="gps-line" id="gps"><?= e(t('gps_locating')) ?></p>
    <p class="alert alert-error" id="punch-error" hidden></p>
  </section>

  <section class="day-times" aria-label="<?= e(t('today')) ?>">
    <?php foreach ($times as $type => $ts): ?>
      <div class="dt <?= $ts ? 'done' : '' ?>">
        <span class="dt-label"><?= e(t('slot_' . $type)) ?></span>
        <span class="dt-time"><?= $ts ? e(date('H:i', $ts)) : '--:--' ?></span>
        <?php if ($type === 'break_start' && $breaks > 1): ?><span class="dt-extra"><?= e(t('more_breaks', ['n' => $breaks - 1])) ?></span><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="counters">
    <div class="counter"><span class="counter-num" id="c-worked"><?= e(fmt_hm($sum['worked'])) ?></span><span class="counter-label"><?= e(t('h_worked')) ?></span></div>
    <div class="counter"><span class="counter-num" id="c-break"><?= e(fmt_hm($sum['break'])) ?></span><span class="counter-label"><?= e(t('h_break')) ?></span></div>
    <?php if ($sched): ?>
      <div class="counter"><span class="counter-num ot" id="c-ot"><?= e(fmt_hm($sum['overtime'])) ?></span><span class="counter-label"><?= e(t('h_overtime')) ?></span></div>
    <?php endif; ?>
  </section>
  <?php if ($sched && $sum['expected'] > 0): ?>
    <p class="muted expected-line"><?= e(t('expected_today', ['h' => fmt_hm($sum['expected']),
        'from' => substr($sched['start_time'], 0, 5), 'to' => substr($sched['end_time'], 0, 5)])) ?>
      <?php if ($sum['late_min'] > 0): ?> · <span class="tag warn"><?= e(t('tag_late', ['m' => $sum['late_min']])) ?></span><?php endif; ?></p>
  <?php endif; ?>

  <h2 class="section-title"><?= e(t('today')) ?> <a href="pontos.php" class="see-all"><?= e(t('see_all')) ?> ›</a></h2>
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
          <?php if ($p['photo_path']): ?><img class="punch-thumb" src="foto-ponto.php?id=<?= (int)$p['id'] ?>" alt=""><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <section class="card app-card" id="install-card" hidden>
    <button type="button" class="app-card-close" id="install-close" aria-label="<?= e(t('close')) ?>">×</button>
    <strong><?= e(t('app_install_title')) ?></strong>
    <p class="muted"><?= e(t('app_install_text')) ?></p>
    <button type="button" class="btn btn-primary" id="install-btn"><?= e(t('app_install_btn')) ?></button>
    <p class="muted small" id="install-ios" hidden><?= e(t('app_install_ios')) ?></p>
  </section>

  <section class="card app-card" id="push-card" hidden>
    <strong><?= e(t('notif_title')) ?></strong>
    <div id="push-off" hidden>
      <p class="muted"><?= e(t('notif_off_text')) ?></p>
      <button type="button" class="btn btn-primary" id="push-enable"><?= e(t('notif_enable')) ?></button>
    </div>
    <div id="push-on" hidden>
      <p class="muted">✅ <?= e(t('notif_on_text')) ?></p>
      <button type="button" class="btn btn-sm" id="push-test"><?= e(t('notif_test')) ?></button>
      <button type="button" class="btn btn-sm btn-danger" id="push-disable"><?= e(t('notif_disable')) ?></button>
    </div>
    <p class="muted" id="push-denied" hidden><?= e(t('notif_denied')) ?></p>
    <p class="small" id="push-msg"></p>
  </section>

</div>

  <script>window.PONTO = <?= json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
  <script src="assets/ponto.js"></script>
<?php page_foot();
