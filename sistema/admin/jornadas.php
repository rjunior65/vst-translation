<?php
/* Admin: work schedules (hours, break, tolerances, workdays). */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/hours.php';

$me = require_admin();

$id = (int)($_GET['id'] ?? 0);
$sch = null;
if ($id) {
    $st = pt_db()->prepare("SELECT * FROM pt_schedules WHERE id = ?");
    $st->execute([$id]);
    $sch = $st->fetch() ?: null;
}
$form = $sch ?: ['name' => '', 'start_time' => '08:00', 'end_time' => '17:00', 'break_minutes' => 60,
                 'late_tolerance_min' => 10, 'overtime_after_min' => 10, 'workdays' => 'mon,tue,wed,thu,fri', 'is_active' => 1];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (($_POST['action'] ?? '') === 'reminders') {
        $on = isset($_POST['reminders']) ? '1' : '0';
        pt_db()->prepare("INSERT INTO pt_settings (skey, svalue) VALUES ('reminders', ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)")->execute([$on]);
        audit('update_settings', 'setting', null, null, ['reminders' => $on]);
        flash('ok', t('rules_saved'));
        redirect('jornadas.php');
    }

    // Give this schedule to everyone who has none yet.
    if (($_POST['action'] ?? '') === 'assign' && $sch) {
        $ids = pt_db()->query("SELECT id FROM pt_users WHERE schedule_id IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        if ($ids) {
            pt_db()->prepare("UPDATE pt_users SET schedule_id = ? WHERE schedule_id IS NULL")->execute([$sch['id']]);
            audit('assign_schedule', 'schedule', (int)$sch['id'], null, ['user_ids' => array_map('intval', $ids)]);
        }
        flash('ok', t('sch_assigned', ['n' => count($ids)]));
        redirect('jornadas.php');
    }

    $form['name']               = trim((string)($_POST['name'] ?? ''));
    $form['start_time']         = (string)($_POST['start_time'] ?? '');
    $form['end_time']           = (string)($_POST['end_time'] ?? '');
    $form['break_minutes']      = (int)($_POST['break_minutes'] ?? 0);
    $form['late_tolerance_min'] = (int)($_POST['late_tolerance_min'] ?? 0);
    $form['overtime_after_min'] = (int)($_POST['overtime_after_min'] ?? 0);
    $days = array_values(array_intersect(WEEKDAYS, (array)($_POST['workdays'] ?? [])));
    $form['workdays']           = implode(',', $days);
    $form['is_active']          = isset($_POST['is_active']) ? 1 : 0;

    $time = '/^([01]\d|2[0-3]):[0-5]\d$/';
    if ($form['name'] === '' || mb_strlen($form['name']) > 120) $errors[] = t('err_name');
    if (!preg_match($time, substr($form['start_time'], 0, 5)) || !preg_match($time, substr($form['end_time'], 0, 5))) $errors[] = t('err_time');
    if ($form['break_minutes'] < 0 || $form['break_minutes'] > 600
        || $form['late_tolerance_min'] < 0 || $form['late_tolerance_min'] > 240
        || $form['overtime_after_min'] < 0 || $form['overtime_after_min'] > 240) $errors[] = t('err_minutes');
    if (!$days) $errors[] = t('err_workdays');

    if (!$errors) {
        $vals = [$form['name'], substr($form['start_time'], 0, 5), substr($form['end_time'], 0, 5), $form['break_minutes'],
                 $form['late_tolerance_min'], $form['overtime_after_min'], $form['workdays'], $form['is_active']];
        if ($sch) {
            pt_db()->prepare("UPDATE pt_schedules SET name = ?, start_time = ?, end_time = ?, break_minutes = ?,
                              late_tolerance_min = ?, overtime_after_min = ?, workdays = ?, is_active = ? WHERE id = ?")
                ->execute(array_merge($vals, [$sch['id']]));
            audit('update_schedule', 'schedule', (int)$sch['id'], $sch, array_merge($sch, $form));
        } else {
            pt_db()->prepare("INSERT INTO pt_schedules (name, start_time, end_time, break_minutes, late_tolerance_min,
                              overtime_after_min, workdays, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute($vals);
            audit('create_schedule', 'schedule', (int)pt_db()->lastInsertId(), null, $form);
        }
        flash('ok', t('sch_saved'));
        redirect('jornadas.php');
    }
}

$all = pt_db()->query("SELECT s.*, (SELECT COUNT(*) FROM pt_users u WHERE u.schedule_id = s.id) AS people
                       FROM pt_schedules s ORDER BY s.is_active DESC, s.name")->fetchAll();
$without = (int)pt_db()->query("SELECT COUNT(*) FROM pt_users WHERE schedule_id IS NULL")->fetchColumn();
$showForm = $sch || isset($_GET['new']) || $errors;
$picked = explode(',', $form['workdays']);

page_head(t('schedules'), $me);
?>
  <div class="page-head">
    <h1><?= e(t('schedules')) ?></h1>
    <?php if (!$showForm): ?><a href="jornadas.php?new=1" class="btn btn-primary">+ <?= e(t('new_schedule')) ?></a><?php endif; ?>
  </div>

  <?php if ($showForm): ?>
    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err) echo e($err) . '<br>'; ?></div><?php endif; ?>
    <form class="form" method="post">
      <h2 class="form-title"><?= e($sch ? t('edit_schedule') : t('new_schedule')) ?></h2>
      <?= csrf_field() ?>
      <label for="name"><?= e(t('loc_name')) ?></label>
      <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="120" required placeholder="Escritório 8h–17h">
      <div class="grid-2">
        <div><label for="start_time"><?= e(t('sch_start')) ?></label>
          <input type="time" id="start_time" name="start_time" value="<?= e(substr($form['start_time'], 0, 5)) ?>" required></div>
        <div><label for="end_time"><?= e(t('sch_end')) ?></label>
          <input type="time" id="end_time" name="end_time" value="<?= e(substr($form['end_time'], 0, 5)) ?>" required></div>
      </div>
      <label for="break_minutes"><?= e(t('sch_break')) ?></label>
      <input type="number" id="break_minutes" name="break_minutes" value="<?= (int)$form['break_minutes'] ?>" min="0" max="600" step="5">
      <div class="grid-2">
        <div><label for="late_tolerance_min"><?= e(t('sch_late_tol')) ?></label>
          <input type="number" id="late_tolerance_min" name="late_tolerance_min" value="<?= (int)$form['late_tolerance_min'] ?>" min="0" max="240"></div>
        <div><label for="overtime_after_min"><?= e(t('sch_ot_tol')) ?></label>
          <input type="number" id="overtime_after_min" name="overtime_after_min" value="<?= (int)$form['overtime_after_min'] ?>" min="0" max="240"></div>
      </div>
      <span class="hint"><?= e(t('sch_tol_hint')) ?></span>
      <label><?= e(t('sch_workdays')) ?></label>
      <div class="weekdays">
        <?php foreach (['mon','tue','wed','thu','fri','sat','sun'] as $wd): ?>
          <label class="check"><input type="checkbox" name="workdays[]" value="<?= $wd ?>" <?= in_array($wd, $picked, true) ? 'checked' : '' ?>>
            <?= e(t('wd_' . $wd)) ?></label>
        <?php endforeach; ?>
      </div>
      <label class="check"><input type="checkbox" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?>> <?= e(t('active')) ?></label>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="jornadas.php" class="btn"><?= e(t('cancel')) ?></a>
      </div>
    </form>
  <?php else: ?>
    <?php if ($without > 0): ?><p class="alert alert-info"><?= e(t('sch_without', ['n' => $without])) ?></p><?php endif; ?>
    <?php if (!$all): ?>
      <p class="muted"><?= e(t('sch_none')) ?></p>
    <?php else: ?>
      <div class="table-scroll">
        <table class="grid">
          <thead><tr><th><?= e(t('loc_name')) ?></th><th><?= e(t('sch_hours')) ?></th><th><?= e(t('sch_break')) ?></th>
            <th><?= e(t('sch_workdays')) ?></th><th><?= e(t('users')) ?></th><th></th></tr></thead>
          <tbody>
          <?php foreach ($all as $s): $exp = expected_seconds($s + ['workdays' => implode(',', WEEKDAYS)], date('Y-m-d')); ?>
            <tr>
              <td><span class="dot <?= $s['is_active'] ? 'on' : 'off' ?>"></span><?= e($s['name']) ?></td>
              <td><?= e(substr($s['start_time'], 0, 5)) ?>–<?= e(substr($s['end_time'], 0, 5)) ?> <span class="muted">(<?= e(fmt_hm($exp)) ?>)</span></td>
              <td><?= (int)$s['break_minutes'] ?> min</td>
              <td class="muted"><?= e(implode(' ', array_map(fn($d) => t('wd_' . $d), array_intersect(['mon','tue','wed','thu','fri','sat','sun'], explode(',', $s['workdays']))))) ?></td>
              <td><?= (int)$s['people'] ?></td>
              <td class="row-actions">
                <a class="btn btn-sm" href="jornadas.php?id=<?= (int)$s['id'] ?>"><?= e(t('edit')) ?></a>
                <?php if ($without > 0 && $s['is_active']): ?>
                  <form method="post" action="jornadas.php?id=<?= (int)$s['id'] ?>" class="inline"
                        onsubmit="return confirm(<?= e(json_encode(t('sch_assign_confirm', ['n' => $without]))) ?>)">
                    <?= csrf_field() ?><input type="hidden" name="action" value="assign">
                    <button class="btn btn-sm"><?= e(t('sch_assign')) ?></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <?php $remOn = pt_db()->query("SELECT svalue FROM pt_settings WHERE skey = 'reminders'")->fetchColumn() !== '0'; ?>
    <form class="form rules-form" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="reminders">
      <label class="check"><input type="checkbox" name="reminders" value="1" <?= $remOn ? 'checked' : '' ?>> <?= e(t('reminders')) ?></label>
      <span class="hint"><?= e(t('reminders_hint')) ?></span>
      <div class="form-actions"><button class="btn btn-primary"><?= e(t('save')) ?></button></div>
    </form>
  <?php endif; ?>
<?php page_foot();
