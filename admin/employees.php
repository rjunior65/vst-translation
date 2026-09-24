<?php
/* List team members + delete. */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$flash = '';

// Handle delete (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        // remove the photo file if it lives in uploads/ (never touch bundled images/)
        $img = db()->prepare("SELECT image FROM employees WHERE id = ?");
        $img->execute([$id]);
        $path = (string)$img->fetchColumn();
        db()->prepare("DELETE FROM employees WHERE id = ?")->execute([$id]);
        if ($path && strpos($path, 'uploads/team/') === 0) {
            $abs = __DIR__ . '/../' . $path;
            if (is_file($abs)) @unlink($abs);
        }
        $flash = 'Team member removed.';
    }
}

$rows = db()->query(
    "SELECT id, name, position_en, department, phone, image, is_active
     FROM employees ORDER BY sort_order ASC, id ASC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — Team</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="wrap">
    <div class="page-head">
      <h1>Team members</h1>
      <a href="employee-edit.php" class="btn btn-primary">+ Add member</a>
    </div>

    <?php if ($flash): ?><p class="alert alert-ok"><?= e($flash) ?></p><?php endif; ?>

    <div class="table-scroll">
      <table class="grid">
        <thead>
          <tr><th></th><th>Name</th><th>Position</th><th>Department</th><th>Phone</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <img class="thumb" src="../<?= e($r['image']) ?>" alt=""
                   onerror="this.onerror=null;this.src='../images/placeholder.svg';">
            </td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['position_en']) ?></td>
            <td><span class="tag"><?= e($r['department']) ?></span></td>
            <td><?= e($r['phone']) ?></td>
            <td><?= $r['is_active'] ? '<span class="dot on"></span>Visible' : '<span class="dot off"></span>Hidden' ?></td>
            <td class="row-actions">
              <a href="employee-edit.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm">Edit</a>
              <form method="post" onsubmit="return confirm('Remove this team member?');" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="muted" style="text-align:center;padding:24px">No team members yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
