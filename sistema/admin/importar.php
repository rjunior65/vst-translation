<?php
/* Admin: create accounts for website team members (table `employees`) in one go. */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';

$me = require_admin();

$pending = pt_db()->query("SELECT e.id, e.name, e.position_en, e.position_pt, e.position_es, e.department
                           FROM employees e LEFT JOIN pt_users u ON u.employee_id = e.id
                           WHERE u.id IS NULL AND e.is_active = 1
                           ORDER BY e.sort_order, e.name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $chosen = array_map('intval', (array)($_POST['ids'] ?? []));
    $created = 0;
    $ins = pt_db()->prepare("INSERT INTO pt_users (employee_id, full_name, username, password_hash,
                             must_change_password, role, work_mode) VALUES (?, ?, ?, ?, 1, 'employee', 'office')");
    foreach ($pending as $m) {
        if (!in_array((int)$m['id'], $chosen, true)) continue;
        $username = generate_username($m['name']);   // checks clashes, including ones created in this loop
        if ($username === '') continue;
        $ins->execute([(int)$m['id'], $m['name'], $username, password_hash(PT_DEFAULT_PASSWORD, PASSWORD_DEFAULT)]);
        $newId = (int)pt_db()->lastInsertId();
        audit('create_user', 'user', $newId, null,
              ['employee_id' => (int)$m['id'], 'full_name' => $m['name'], 'username' => $username], 'import');
        $created++;
    }
    flash('ok', t('import_done', ['n' => $created]));
    redirect('./');
}

// Preview the usernames in order, accounting for clashes inside this batch too.
$preview = [];
$seen = [];
foreach ($pending as $m) {
    $u = generate_username($m['name'], 0, $seen);
    $seen[$u] = true;
    $preview[$m['id']] = $u;
}

$posCol = 'position_' . current_lang();
page_head(t('import'), $me);
?>
  <p><a href="./">← <?= e(t('back')) ?></a></p>
  <h1><?= e(t('import')) ?></h1>

  <?php if (!$pending): ?>
    <p class="alert alert-ok"><?= e(t('import_none')) ?></p>
  <?php else: ?>
    <p class="muted"><?= e(t('import_intro', ['p' => PT_DEFAULT_PASSWORD])) ?></p>
    <form method="post">
      <?= csrf_field() ?>
      <div class="table-scroll">
        <table class="grid">
          <thead><tr>
            <th><input type="checkbox" checked onclick="document.querySelectorAll('.pick').forEach(c => c.checked = this.checked)"></th>
            <th><?= e(t('full_name')) ?></th>
            <th><?= e(t('position')) ?></th>
            <th><?= e(t('will_be')) ?></th>
          </tr></thead>
          <tbody>
          <?php foreach ($pending as $m): ?>
            <tr>
              <td><input type="checkbox" class="pick" name="ids[]" value="<?= (int)$m['id'] ?>" checked></td>
              <td><?= e($m['name']) ?></td>
              <td class="muted"><?= e($m[$posCol] ?: $m['position_en']) ?></td>
              <td><code class="user"><?= e($preview[$m['id']]) ?></code></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn-primary"><?= e(t('import_btn')) ?></button></div>
    </form>
  <?php endif; ?>
<?php page_foot();
