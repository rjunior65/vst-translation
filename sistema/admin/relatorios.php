<?php
/* Admin: monthly report (team summary or one person day by day).
   Excel export (.xlsx) and print / save as PDF from the browser. */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/hours.php';
require_once __DIR__ . '/../inc/report_view.php';

$me = require_admin();

$ym = (string)($_GET['mes'] ?? date('Y-m'));
if (!valid_month($ym) || $ym > date('Y-m')) $ym = date('Y-m');

$all = pt_db()->query("SELECT * FROM pt_users ORDER BY is_active DESC, full_name")->fetchAll();
$uid = (int)($_GET['usuario'] ?? 0);
$one = null;
foreach ($all as $u) if ((int)$u['id'] === $uid) $one = $u;
// Team view: active people, plus inactive ones who punched this month.
$users = $one ? [$one] : $all;
$rep = month_report($users, $ym);
if (!$one) {
    $rep = array_filter($rep, fn($r) => $r['user']['is_active'] || $r['totals']['days_worked'] > 0);
}

// ---- Excel ----
if (($_GET['export'] ?? '') === 'xlsx') {
    require_once __DIR__ . '/../inc/xlsx.php';
    $h = fn(?int $s) => ['h' => $s];
    $summary = [[t('full_name'), t('username'), t('schedule'), t('h_days'), t('h_worked'), t('h_break'), t('h_expected'),
                 t('h_overtime'), t('h_missing'), t('h_late_days'), t('h_late_min'), t('h_absences'), t('tag_incomplete'), t('k_flagged')]];
    $sheets = [];
    foreach ($rep as $r) {
        $tt = $r['totals'];
        $summary[] = [$r['user']['full_name'], $r['user']['username'], $r['schedule']['name'] ?? '', $tt['days_worked'],
                      $h($tt['worked']), $h($tt['break']), $h($tt['has_schedule'] ? $tt['expected'] : null),
                      $h($tt['has_schedule'] ? $tt['overtime'] : null), $h($tt['has_schedule'] ? $tt['missing'] : null),
                      $tt['late_count'], $tt['late_min'], $tt['absent'], $tt['incomplete'], $tt['flagged']];
        $rows = [[t('date'), t('weekday'), t('h_first_in'), t('h_last_out'), t('h_worked'), t('h_break'), t('h_expected'),
                  t('h_overtime'), t('h_missing'), t('h_late_min'), t('notes')]];
        foreach ($r['days'] as $d => $s) {
            $notes = array_filter([$s['absent'] ? t('tag_absent') : '', $s['incomplete'] ? t('tag_incomplete') : '',
                                   $s['flagged'] ? t('tag_flagged', ['n' => $s['flagged']]) : '']);
            $rows[] = [date('d/m/Y', strtotime($d)), weekday_short($d), $s['first_in'] ? date('H:i', $s['first_in']) : '',
                       $s['last_out'] ? date('H:i', $s['last_out']) : '', $h($s['count'] ? $s['worked'] : null),
                       $h($s['count'] ? $s['break'] : null), $h($s['expected'] ?: null), $h($s['overtime'] ?: null),
                       $h($s['missing'] ?: null), $s['late_min'] ?: '', implode(', ', $notes)];
        }
        $sheets[$r['user']['username']] = $rows;   // username is unique; full names may repeat
    }
    $book = $one ? $sheets : array_merge([t('summary') => $summary], $sheets);
    xlsx_send('ponto-' . ($one ? $one['username'] . '-' : '') . $ym . '.xlsx', $book);
}

$q = fn(array $extra) => 'relatorios.php?' . http_build_query(array_merge(['mes' => $ym, 'usuario' => $uid ?: null], $extra));

page_head(t('reports'), $me);
?>
  <div class="page-head no-print">
    <h1><?= e(t('reports')) ?></h1>
    <div class="row-actions">
      <a class="btn" href="<?= e($q(['export' => 'xlsx'])) ?>">⬇ Excel</a>
      <button type="button" class="btn" onclick="window.print()">🖨 <?= e(t('print_pdf')) ?></button>
    </div>
  </div>

  <form class="filters no-print" method="get">
    <input type="month" name="mes" value="<?= e($ym) ?>" max="<?= e(date('Y-m')) ?>">
    <select name="usuario">
      <option value=""><?= e(t('whole_team')) ?></option>
      <?php foreach ($all as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $uid === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?>
    </select>
    <button class="btn"><?= e(t('filter')) ?></button>
  </form>

  <h2 class="print-title"><?= e($one ? $one['full_name'] : t('whole_team')) ?> — <?= e(month_label($ym)) ?></h2>

  <?php if ($one):
    $r = $rep[(int)$one['id']]; ?>
    <p class="muted"><?= e(t('schedule')) ?>: <?= e($r['schedule']['name'] ?? t('no_schedule')) ?></p>
    <?php render_totals($r['totals']); ?>
    <?php if ($r['days']):
      render_days_table($r['days'], $r['schedule'] !== null,
          fn($d) => 'pontos.php?' . http_build_query(['usuario' => $one['id'], 'de' => $d, 'ate' => $d]));
    else: ?><p class="muted"><?= e(t('no_data_month')) ?></p><?php endif; ?>
  <?php else: ?>
    <div class="table-scroll">
      <table class="grid">
        <thead><tr>
          <th><?= e(t('full_name')) ?></th><th><?= e(t('h_days')) ?></th><th><?= e(t('h_worked')) ?></th><th><?= e(t('h_break')) ?></th>
          <th><?= e(t('h_expected')) ?></th><th><?= e(t('h_overtime')) ?></th><th><?= e(t('h_missing')) ?></th>
          <th><?= e(t('h_late_days')) ?></th><th><?= e(t('h_absences')) ?></th><th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rep as $r): $tt = $r['totals']; $hs = $tt['has_schedule']; ?>
          <tr>
            <td><a href="<?= e($q(['usuario' => $r['user']['id']])) ?>"><?= e($r['user']['full_name']) ?></a></td>
            <td><?= (int)$tt['days_worked'] ?></td>
            <td><strong><?= e(fmt_hm($tt['worked'])) ?></strong></td>
            <td><?= e(fmt_hm($tt['break'])) ?></td>
            <td><?= $hs ? e(fmt_hm($tt['expected'])) : '<span class="muted">' . e(t('no_schedule_short')) . '</span>' ?></td>
            <td class="ot"><?= $hs && $tt['overtime'] ? e(fmt_hm($tt['overtime'])) : '' ?></td>
            <td><?= $hs && $tt['missing'] ? e(fmt_hm($tt['missing'])) : '' ?></td>
            <td><?= $tt['late_count'] ? (int)$tt['late_count'] . ' <span class="muted small">(' . (int)$tt['late_min'] . ' min)</span>' : '' ?></td>
            <td><?= $tt['absent'] ?: '' ?></td>
            <td class="tags">
              <?php if ($tt['incomplete']): ?><span class="tag warn"><?= e(t('tag_incomplete')) ?> ×<?= (int)$tt['incomplete'] ?></span><?php endif; ?>
              <?php if ($tt['flagged']): ?><span class="tag warn"><?= e(t('tag_flagged', ['n' => $tt['flagged']])) ?></span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php page_foot();
