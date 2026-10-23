<?php
/* Admin: list of system users (search + filters). */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';

$me = require_admin();

$q      = trim((string)($_GET['q'] ?? ''));
$role   = in_array($_GET['role'] ?? '', roles(), true) ? $_GET['role'] : '';
$status = in_array($_GET['status'] ?? '', ['1', '0'], true) ? $_GET['status'] : '';

$where = [];
$args  = [];
if ($q !== '') {
    $where[] = '(full_name LIKE ? OR username LIKE ?)';
    $args[] = "%$q%";
    $args[] = "%$q%";
}
if ($role !== '')   { $where[] = 'role = ?';      $args[] = $role; }
if ($status !== '') { $where[] = 'is_active = ?'; $args[] = (int)$status; }

$sql = 'SELECT * FROM pt_users' . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY is_active DESC, role = "admin" DESC, full_name';
$st = pt_db()->prepare($sql);
$st->execute($args);
$users = $st->fetchAll();

page_head(t('users'), $me);
?>
  <div class="page-head">
    <h1><?= e(t('users')) ?> <span class="muted">(<?= count($users) ?>)</span></h1>
    <div>
      <a href="importar.php" class="btn"><?= e(t('import')) ?></a>
      <a href="usuario.php" class="btn btn-primary">+ <?= e(t('new_user')) ?></a>
    </div>
  </div>

  <form class="filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('search')) ?>">
    <select name="role" onchange="this.form.submit()">
      <option value=""><?= e(t('role')) ?>: <?= e(t('all')) ?></option>
      <?php foreach (roles() as $r): ?>
        <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= e(t('role_' . $r)) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" onchange="this.form.submit()">
      <option value=""><?= e(t('status')) ?>: <?= e(t('all')) ?></option>
      <option value="1" <?= $status === '1' ? 'selected' : '' ?>><?= e(t('active')) ?></option>
      <option value="0" <?= $status === '0' ? 'selected' : '' ?>><?= e(t('inactive')) ?></option>
    </select>
  </form>

  <div class="table-scroll">
    <table class="grid">
      <thead><tr>
        <th><?= e(t('full_name')) ?></th>
        <th><?= e(t('username')) ?></th>
        <th><?= e(t('role')) ?></th>
        <th><?= e(t('work_mode')) ?></th>
        <th><?= e(t('last_login')) ?></th>
        <th></th>
      </tr></thead>
      <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="6" class="muted"><?= e(t('no_users')) ?></td></tr>
      <?php endif; ?>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><span class="dot <?= $u['is_active'] ? 'on' : 'off' ?>" title="<?= e(t($u['is_active'] ? 'active' : 'inactive')) ?>"></span><?= e($u['full_name']) ?></td>
          <td><code class="user"><?= e($u['username']) ?></code>
            <?php if ((int)$u['must_change_password'] === 1): ?> <span class="tag warn"><?= e(t('temp_password')) ?></span><?php endif; ?></td>
          <td><span class="tag <?= $u['role'] === 'admin' ? 'admin' : '' ?>"><?= e(t('role_' . $u['role'])) ?></span></td>
          <td><?= e(t('wm_' . $u['work_mode'])) ?></td>
          <td class="muted"><?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime($u['last_login_at']))) : e(t('never')) ?></td>
          <td><a href="usuario.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm"><?= e(t('edit')) ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php page_foot();
