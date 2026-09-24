<?php
/* List tourism destinations + delete. */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $img = db()->prepare("SELECT image FROM destinations WHERE id = ?");
        $img->execute([$id]);
        $path = (string)$img->fetchColumn();
        db()->prepare("DELETE FROM destinations WHERE id = ?")->execute([$id]);
        if ($path && strpos($path, 'uploads/tourism/') === 0) {
            $abs = __DIR__ . '/../' . $path;
            if (is_file($abs)) @unlink($abs);
        }
        $flash = 'Destination removed.';
    }
}

$rows = db()->query("SELECT id, name, location_en, image, is_active
                     FROM destinations ORDER BY sort_order ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — Destinations</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="wrap">
    <div class="page-head">
      <h1>Tourism destinations</h1>
      <a href="destination-edit.php" class="btn btn-primary">+ Add destination</a>
    </div>
    <?php if ($flash): ?><p class="alert alert-ok"><?= e($flash) ?></p><?php endif; ?>

    <div class="table-scroll">
      <table class="grid">
        <thead><tr><th></th><th>Name</th><th>Location</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><img class="thumb" src="../<?= e($r['image']) ?>" alt="" onerror="this.onerror=null;this.src='../images/placeholder.svg';"></td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['location_en']) ?></td>
            <td><?= $r['is_active'] ? '<span class="dot on"></span>Visible' : '<span class="dot off"></span>Hidden' ?></td>
            <td class="row-actions">
              <a href="destination-edit.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm">Edit</a>
              <form method="post" onsubmit="return confirm('Remove this destination?');" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:24px">No destinations yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <p class="muted"><a href="gallery.php">→ Manage the tourism photo gallery</a></p>
  </main>
</body>
</html>
