<?php
/* My punches (read-only): every clock-in/out of a period, day by day. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/punch.php';
require_once __DIR__ . '/inc/hours.php';

$user = require_user();

$date = fn($s) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$s) ? (string)$s : null;
$from = $date($_GET['de'] ?? '') ?? date('Y-m-01');
$to   = $date($_GET['ate'] ?? '') ?? date('Y-m-d');
if ($to < $from) [$from, $to] = [$to, $from];

$st = pt_db()->prepare("SELECT * FROM pt_punches WHERE user_id = ? AND is_void = 0
                        AND punched_at >= ? AND punched_at < ? + INTERVAL 1 DAY ORDER BY punched_at DESC, id DESC LIMIT 1000");
$st->execute([$user['id'], $from, $to]);
$days = [];
foreach ($st->fetchAll() as $p) $days[substr($p['punched_at'], 0, 10)][] = $p;

page_head(t('punches'), $user);
?>
  <div class="page-head">
    <h1><?= e(t('my_punches')) ?></h1>
  </div>
  <form class="filters" method="get">
    <input type="date" name="de" value="<?= e($from) ?>" max="<?= e(date('Y-m-d')) ?>" aria-label="<?= e(t('from')) ?>">
    <input type="date" name="ate" value="<?= e($to) ?>" max="<?= e(date('Y-m-d')) ?>" aria-label="<?= e(t('to')) ?>">
    <button class="btn"><?= e(t('filter')) ?></button>
  </form>

  <?php if (!$days): ?>
    <p class="muted"><?= e(t('no_punches')) ?></p>
  <?php endif; ?>
  <?php foreach ($days as $d => $list): ?>
    <h2 class="section-title"><?= e(date('d/m/Y', strtotime($d))) ?> <span class="muted small"><?= e(weekday_short($d)) ?></span></h2>
    <ul class="punch-list">
      <?php foreach (array_reverse($list) as $p): ?>
        <li>
          <span class="punch-time"><?= e(date('H:i', strtotime($p['punched_at']))) ?></span>
          <span class="punch-type"><?= e(t('type_' . $p['type'])) ?></span>
          <?php if ($p['status'] === 'manual'): ?><span class="tag"><?= e(t('st_manual')) ?></span>
          <?php elseif ($p['status'] !== 'ok'): ?><span class="tag warn"><?= e(t('st_' . $p['status'])) ?></span><?php endif; ?>
          <?php if ($p['distance_m'] !== null): ?><span class="muted punch-dist"><?= e(fmt_distance((int)$p['distance_m'])) ?></span><?php endif; ?>
          <?php if ($p['photo_path']): ?><a href="foto-ponto.php?id=<?= (int)$p['id'] ?>" target="_blank"><img class="punch-thumb" src="foto-ponto.php?id=<?= (int)$p['id'] ?>" alt="" loading="lazy"></a><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endforeach; ?>
<?php page_foot();
