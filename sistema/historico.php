<?php
/* Employee: own month history (read-only). */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/hours.php';
require_once __DIR__ . '/inc/report_view.php';

$user = require_user();
$ym = (string)($_GET['mes'] ?? date('Y-m'));
if (!valid_month($ym) || $ym > date('Y-m')) $ym = date('Y-m');

$rep = month_report([$user], $ym)[(int)$user['id']];

page_head(t('reports'), $user);
?>
  <div class="page-head">
    <h1><?= e(t('my_reports')) ?></h1>
    <?= month_nav($ym, 'historico.php') ?>
  </div>
  <?php if (!$rep['schedule']): ?><p class="muted"><?= e(t('no_schedule_note')) ?></p><?php endif; ?>
  <?php render_totals($rep['totals']); ?>
  <?php if ($rep['days']): render_days_table($rep['days'], $rep['schedule'] !== null); else: ?>
    <p class="muted"><?= e(t('no_data_month')) ?></p>
  <?php endif; ?>
<?php page_foot();
