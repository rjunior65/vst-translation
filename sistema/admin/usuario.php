<?php
/* Admin: create / edit a user, reset password. Every change goes to the audit log. */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';

$me = require_admin();

$id = (int)($_GET['id'] ?? 0);
$user = null;
if ($id) {
    $st = pt_db()->prepare("SELECT * FROM pt_users WHERE id = ?");
    $st->execute([$id]);
    $user = $st->fetch();
    if (!$user) { flash('error', t('not_found')); redirect('./'); }
}
$isSelf = $user && (int)$user['id'] === (int)$me['id'];

// Website team members, to link an account to a photo/name (each can be linked once).
$team = pt_db()->query("SELECT e.id, e.name, u.id AS linked_user
                        FROM employees e LEFT JOIN pt_users u ON u.employee_id = e.id
                        ORDER BY e.sort_order, e.name")->fetchAll();

$errors = [];
$form = $user ?: ['full_name' => '', 'username' => '', 'role' => 'employee', 'work_mode' => 'office',
                  'employee_id' => null, 'is_active' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? 'save';

    // ---- Reset password back to the temporary one ----
    if ($action === 'reset' && $user) {
        pt_db()->prepare("UPDATE pt_users SET password_hash = ?, must_change_password = 1 WHERE id = ?")
            ->execute([password_hash(PT_DEFAULT_PASSWORD, PASSWORD_DEFAULT), $user['id']]);
        pt_db()->prepare("DELETE FROM pt_login_attempts WHERE username = ?")->execute([$user['username']]);
        audit('reset_password', 'user', (int)$user['id'], null, null, trim((string)($_POST['reason'] ?? '')));
        flash('ok', t('reset_pw_done', ['u' => $user['username'], 'p' => PT_DEFAULT_PASSWORD]));
        redirect('usuario.php?id=' . (int)$user['id']);
    }

    // ---- Save ----
    $form['full_name']   = trim(preg_replace('/\s+/', ' ', (string)($_POST['full_name'] ?? '')));
    $form['username']    = strtolower(trim((string)($_POST['username'] ?? '')));
    $form['role']        = in_array($_POST['role'] ?? '', roles(), true) ? $_POST['role'] : 'employee';
    $form['work_mode']   = in_array($_POST['work_mode'] ?? '', work_modes(), true) ? $_POST['work_mode'] : 'office';
    $form['employee_id'] = ($_POST['employee_id'] ?? '') !== '' ? (int)$_POST['employee_id'] : null;
    $form['is_active']   = isset($_POST['is_active']) ? 1 : 0;
    $reason              = trim((string)($_POST['reason'] ?? ''));

    if ($form['full_name'] === '' || mb_strlen($form['full_name']) > 160) $errors[] = t('err_name');

    if ($form['username'] === '' && $form['full_name'] !== '') {
        $form['username'] = generate_username($form['full_name'], $id);
    }
    if (!valid_username($form['username'])) {
        $errors[] = t('err_username');
    } elseif (username_taken($form['username'], $id)) {
        $errors[] = t('err_username_taken');
    }

    if ($form['employee_id'] !== null) {
        foreach ($team as $m) {
            if ((int)$m['id'] === $form['employee_id'] && $m['linked_user'] && (int)$m['linked_user'] !== $id) {
                $errors[] = t('err_employee_taken');
            }
        }
    }

    if ($isSelf && ($form['role'] !== 'admin' || !$form['is_active'])) {
        $errors[] = t('err_self_admin');
        $form['role'] = 'admin';
        $form['is_active'] = 1;
    }

    if (!$errors) {
        $fields = [$form['full_name'], $form['username'], $form['role'], $form['work_mode'],
                   $form['employee_id'], $form['is_active']];
        if ($user) {
            pt_db()->prepare("UPDATE pt_users SET full_name = ?, username = ?, role = ?, work_mode = ?,
                              employee_id = ?, is_active = ? WHERE id = ?")
                ->execute(array_merge($fields, [$user['id']]));
            $new = array_merge($user, array_combine(
                ['full_name', 'username', 'role', 'work_mode', 'employee_id', 'is_active'], $fields));
            audit('update_user', 'user', (int)$user['id'], $user, $new, $reason);
            flash('ok', t('user_saved'));
            redirect('usuario.php?id=' . (int)$user['id']);
        } else {
            pt_db()->prepare("INSERT INTO pt_users (full_name, username, role, work_mode, employee_id, is_active,
                              password_hash, must_change_password) VALUES (?, ?, ?, ?, ?, ?, ?, 1)")
                ->execute(array_merge($fields, [password_hash(PT_DEFAULT_PASSWORD, PASSWORD_DEFAULT)]));
            $newId = (int)pt_db()->lastInsertId();
            audit('create_user', 'user', $newId, null, $form, $reason);
            flash('ok', t('user_created', ['u' => $form['username'], 'p' => PT_DEFAULT_PASSWORD]));
            redirect('./');
        }
    }
}

page_head($user ? t('edit_user') : t('new_user'), $me);
?>
  <p><a href="./">← <?= e(t('back')) ?></a></p>
  <h1><?= e($user ? t('edit_user') : t('new_user')) ?></h1>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $err) echo e($err) . '<br>'; ?></div>
  <?php endif; ?>

  <form class="form" method="post" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">

    <label for="full_name"><?= e(t('full_name')) ?></label>
    <input type="text" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" maxlength="160" required>

    <label for="username"><?= e(t('username')) ?></label>
    <input type="text" id="username" name="username" value="<?= e($form['username']) ?>" maxlength="80"
           autocapitalize="none" spellcheck="false" placeholder="nome.sobrenome">
    <span class="hint"><?= e(t('username_hint')) ?></span>

    <label for="role"><?= e(t('role')) ?></label>
    <select id="role" name="role" <?= $isSelf ? 'disabled' : '' ?>>
      <?php foreach (roles() as $r): ?>
        <option value="<?= $r ?>" <?= $form['role'] === $r ? 'selected' : '' ?>><?= e(t('role_' . $r)) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($isSelf): ?><input type="hidden" name="role" value="admin"><?php endif; ?>

    <label for="work_mode"><?= e(t('work_mode')) ?></label>
    <select id="work_mode" name="work_mode">
      <?php foreach (work_modes() as $w): ?>
        <option value="<?= $w ?>" <?= $form['work_mode'] === $w ? 'selected' : '' ?>><?= e(t('wm_' . $w)) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="employee_id"><?= e(t('linked_employee')) ?></label>
    <select id="employee_id" name="employee_id">
      <option value=""><?= e(t('none')) ?></option>
      <?php foreach ($team as $m):
        $takenByOther = $m['linked_user'] && (int)$m['linked_user'] !== $id; ?>
        <option value="<?= (int)$m['id'] ?>" <?= (int)$form['employee_id'] === (int)$m['id'] ? 'selected' : '' ?>
                <?= $takenByOther ? 'disabled' : '' ?>><?= e($m['name']) ?><?= $takenByOther ? ' ✓' : '' ?></option>
      <?php endforeach; ?>
    </select>

    <label class="check">
      <input type="checkbox" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
      <?= e(t('active')) ?>
    </label>
    <?php if ($isSelf): ?><input type="hidden" name="is_active" value="1"><?php endif; ?>

    <?php if ($user): ?>
      <label for="reason"><?= e(t('reason')) ?></label>
      <input type="text" id="reason" name="reason" maxlength="255">
      <span class="hint"><?= e(t('reason_hint')) ?></span>
    <?php endif; ?>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
      <a href="./" class="btn"><?= e(t('cancel')) ?></a>
    </div>
  </form>

  <?php if ($user): ?>
  <form class="form danger-zone" method="post" onsubmit="return confirm(<?= e(json_encode(t('confirm_reset'))) ?>)">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="reset">
    <h2><?= e(t('reset_pw')) ?></h2>
    <p class="muted"><?= e(t('reset_pw_help', ['p' => PT_DEFAULT_PASSWORD])) ?></p>
    <label for="reset_reason"><?= e(t('reason')) ?></label>
    <input type="text" id="reset_reason" name="reason" maxlength="255">
    <div class="form-actions"><button type="submit" class="btn btn-danger"><?= e(t('reset_pw')) ?></button></div>
  </form>
  <?php endif; ?>
<?php page_foot();
