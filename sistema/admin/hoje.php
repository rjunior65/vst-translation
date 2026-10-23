<?php
/* Admin home: who is working right now. Refreshes itself every 20 s while visible. */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/hours.php';

$me = require_admin();
purge_photos_daily();   // fallback if the cron job isn't set up

$users = pt_db()->query("SELECT * FROM pt_users WHERE is_active = 1 ORDER BY full_name")->fetchAll();
$rep   = month_report($users, date('Y-m'));
$today = date('Y-m-d');
$now   = time();

$rows = [];
$count = ['working' => 0, 'break' => 0, 'done' => 0, 'not_in' => 0, 'absent' => 0, 'off' => 0];
foreach ($users as $u) {
    $s = $rep[(int)$u['id']]['days'][$today] ?? day_summary([], null, $today, $now);
    $sch = $rep[(int)$u['id']]['schedule'];
    if ($s['count'] > 0)            $key = $s['state'] === 'off' ? 'working' : $s['state'];
    elseif ($s['absent'])           $key = 'absent';
    elseif ($s['expected'] > 0)     $key = 'not_in';
    else                            $key = 'off';
    // Expected but not in yet, and already past start + tolerance → late.
    $lateNow = $key === 'not_in' && $sch
        && $now > strtotime("$today {$sch['start_time']}") + (int)$sch['late_tolerance_min'] * 60;
    $count[$key]++;
    $rows[] = ['u' => $u, 's' => $s, 'key' => $key, 'late_now' => $lateNow];
}
$order = ['working' => 0, 'break' => 1, 'not_in' => 2, 'absent' => 3, 'done' => 4, 'off' => 5];
usort($rows, fn($a, $b) => [$order[$a['key']], $a['u']['full_name']] <=> [$order[$b['key']], $b['u']['full_name']]);

$flaggedToday = (int)pt_db()->query("SELECT COUNT(*) FROM pt_punches WHERE is_void = 0 AND status IN ('outside','no_gps')
                                     AND punched_at >= CURDATE()")->fetchColumn();
$noSchedule = (int)pt_db()->query("SELECT COUNT(*) FROM pt_users WHERE is_active = 1 AND schedule_id IS NULL")->fetchColumn();

page_head(t('team_today'), $me);
?>
  <div class="page-head">
    <h1><?= e(t('team_today')) ?> <span class="muted"><?= e(date('d/m/Y')) ?> · <span id="now"><?= e(date('H:i')) ?></span></span></h1>
    <span class="muted small"><?= e(t('auto_refresh')) ?></span>
  </div>

  <?php if ($noSchedule): ?>
    <p class="alert alert-info"><?= e(t('sch_without', ['n' => $noSchedule])) ?> <a href="jornadas.php"><?= e(t('schedules')) ?> ›</a></p>
  <?php endif; ?>

  <div class="tiles">
    <div class="tile"><span class="tile-num ok"><?= $count['working'] ?></span><span class="tile-label"><?= e(t('state_working')) ?></span></div>
    <div class="tile"><span class="tile-num warn"><?= $count['break'] ?></span><span class="tile-label"><?= e(t('state_break')) ?></span></div>
    <div class="tile"><span class="tile-num"><?= $count['not_in'] ?></span><span class="tile-label"><?= e(t('k_not_in')) ?></span></div>
    <div class="tile"><span class="tile-num"><?= $count['absent'] ?></span><span class="tile-label"><?= e(t('h_absences')) ?></span></div>
    <div class="tile"><span class="tile-num"><?= $count['done'] ?></span><span class="tile-label"><?= e(t('state_done')) ?></span></div>
    <a class="tile" href="pontos.php?status=flagged&de=<?= e($today) ?>&ate=<?= e($today) ?>"><span class="tile-num <?= $flaggedToday ? 'bad' : '' ?>"><?= $flaggedToday ?></span><span class="tile-label"><?= e(t('k_flagged')) ?></span></a>
  </div>

  <div class="table-scroll">
    <table class="grid">
      <thead><tr>
        <th><?= e(t('full_name')) ?></th><th><?= e(t('status')) ?></th><th><?= e(t('h_first_in')) ?></th>
        <th><?= e(t('h_worked')) ?></th><th><?= e(t('h_break')) ?></th><th></th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $s = $r['s']; ?>
        <tr>
          <td><a class="person" href="pontos.php?usuario=<?= (int)$r['u']['id'] ?>&de=<?= e($today) ?>&ate=<?= e($today) ?>"><?= avatar_html($r['u'], 'sm') ?><?= e($r['u']['full_name']) ?></a></td>
          <td><span class="pill pill-<?= e($r['key']) ?>"><?= e(t('k_' . $r['key'])) ?></span></td>
          <td><?= $s['first_in'] ? e(date('H:i', $s['first_in'])) : '—' ?></td>
          <td><?= $s['count'] ? e(fmt_hm($s['worked'])) : '—' ?></td>
          <td><?= $s['count'] ? e(fmt_hm($s['break'])) : '—' ?></td>
          <td class="tags">
            <?php if ($s['late_min'] > 0): ?><span class="tag warn"><?= e(t('tag_late', ['m' => $s['late_min']])) ?></span><?php endif; ?>
            <?php if ($r['late_now']): ?><span class="tag warn"><?= e(t('tag_late_now')) ?></span><?php endif; ?>
            <?php if ($s['flagged']): ?><span class="tag warn"><?= e(t('tag_flagged', ['n' => $s['flagged']])) ?></span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <script>
  // Refresh every 20 s, only while the tab is visible.
  setInterval(function () { if (!document.hidden) location.reload(); }, 20000);
  </script>
<?php page_foot();
