<?php
/* Shared report pieces: month totals tiles + day-by-day table.
   Used by the employee history and the admin reports. */

function month_nav(string $ym, string $baseUrl): string {
    $prev = date('Y-m', strtotime("$ym-01 -1 month"));
    $next = date('Y-m', strtotime("$ym-01 +1 month"));
    $sep  = strpos($baseUrl, '?') === false ? '?' : '&';
    $html = '<div class="month-nav"><a class="btn btn-sm" href="' . e($baseUrl . $sep . 'mes=' . $prev) . '">‹</a>'
          . '<strong>' . e(month_label($ym)) . '</strong>';
    if ($next <= date('Y-m')) {
        $html .= '<a class="btn btn-sm" href="' . e($baseUrl . $sep . 'mes=' . $next) . '">›</a>';
    } else {
        $html .= '<span class="btn btn-sm disabled">›</span>';
    }
    return $html . '</div>';
}

function render_totals(array $tot): void { ?>
  <div class="tiles">
    <div class="tile"><span class="tile-num"><?= e(fmt_hm($tot['worked'])) ?></span><span class="tile-label"><?= e(t('h_worked')) ?></span></div>
    <div class="tile"><span class="tile-num"><?= e(fmt_hm($tot['break'])) ?></span><span class="tile-label"><?= e(t('h_break')) ?></span></div>
    <?php if ($tot['has_schedule']): ?>
      <div class="tile"><span class="tile-num ot"><?= e(fmt_hm($tot['overtime'])) ?></span><span class="tile-label"><?= e(t('h_overtime')) ?></span></div>
      <div class="tile"><span class="tile-num"><?= e(fmt_hm($tot['missing'])) ?></span><span class="tile-label"><?= e(t('h_missing')) ?></span></div>
      <div class="tile"><span class="tile-num"><?= (int)$tot['late_count'] ?></span><span class="tile-label"><?= e(t('h_late_days')) ?></span></div>
      <div class="tile"><span class="tile-num"><?= (int)$tot['absent'] ?></span><span class="tile-label"><?= e(t('h_absences')) ?></span></div>
    <?php endif; ?>
    <div class="tile"><span class="tile-num"><?= (int)$tot['days_worked'] ?></span><span class="tile-label"><?= e(t('h_days')) ?></span></div>
  </div>
<?php }

/* $punchLink: optional fn(date) => url, shown as a link on each date (admin). */
function render_days_table(array $days, bool $hasSchedule, ?callable $punchLink = null): void { ?>
  <div class="table-scroll">
    <table class="grid days">
      <thead><tr>
        <th><?= e(t('date')) ?></th>
        <th><?= e(t('h_first_in')) ?></th>
        <th><?= e(t('h_last_out')) ?></th>
        <th><?= e(t('h_worked')) ?></th>
        <th><?= e(t('h_break')) ?></th>
        <?php if ($hasSchedule): ?><th><?= e(t('h_overtime')) ?></th><th><?= e(t('h_missing')) ?></th><?php endif; ?>
        <th></th>
      </tr></thead>
      <tbody>
      <?php foreach (array_reverse($days) as $d => $s):
        $off = $hasSchedule && !$s['workday'] && !$s['count']; ?>
        <tr class="<?= $off ? 'dayoff' : '' ?>">
          <td class="nowrap"><?php $label = e(date('d/m', strtotime($d))) . ' <span class="muted">' . e(weekday_short($d)) . '</span>';
            echo $punchLink ? '<a href="' . e($punchLink($d)) . '">' . $label . '</a>' : $label; ?></td>
          <td><?= $s['first_in'] ? e(date('H:i', $s['first_in'])) : '—' ?></td>
          <td><?= $s['last_out'] ? e(date('H:i', $s['last_out'])) : '—' ?></td>
          <td><strong><?= $s['count'] ? e(fmt_hm($s['worked'])) : '—' ?></strong></td>
          <td><?= $s['count'] ? e(fmt_hm($s['break'])) : '—' ?></td>
          <?php if ($hasSchedule): ?>
            <td class="ot"><?= $s['overtime'] ? e(fmt_hm($s['overtime'])) : '' ?></td>
            <td><?= $s['missing'] ? e(fmt_hm($s['missing'])) : '' ?></td>
          <?php endif; ?>
          <td class="tags">
            <?php if ($s['absent']): ?><span class="tag admin"><?= e(t('tag_absent')) ?></span><?php endif; ?>
            <?php if ($s['late_min'] > 0): ?><span class="tag warn"><?= e(t('tag_late', ['m' => $s['late_min']])) ?></span><?php endif; ?>
            <?php if ($s['incomplete']): ?><span class="tag warn"><?= e(t('tag_incomplete')) ?></span><?php endif; ?>
            <?php if ($s['flagged']): ?><span class="tag warn"><?= e(t('tag_flagged', ['n' => $s['flagged']])) ?></span><?php endif; ?>
            <?php if ($off): ?><span class="muted"><?= e(t('tag_dayoff')) ?></span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php }
