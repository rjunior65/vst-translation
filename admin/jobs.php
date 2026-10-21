<?php
/* Careers: list / add / edit / delete job openings (shown on the public site). */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$flash  = '';
$errors = [];
$editId = (int)($_GET['edit'] ?? 0);
$job = ['title_en' => '', 'title_pt' => '', 'title_es' => '',
        'description_en' => '', 'description_pt' => '', 'description_es' => '',
        'sort_order' => 0, 'is_active' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        db()->prepare("DELETE FROM jobs WHERE id = ?")->execute([$id]);
        $flash = 'Job opening removed.';
    } elseif ($action === 'save') {
        foreach (['title_en','title_pt','title_es','description_en','description_pt','description_es'] as $f) {
            $job[$f] = trim($_POST[$f] ?? '');
        }
        $job['sort_order'] = (int)($_POST['sort_order'] ?? 0);
        $job['is_active']  = isset($_POST['is_active']) ? 1 : 0;
        if ($job['title_en'] === '') $errors[] = 'The English title is required.';

        if (!$errors) {
            $p = [$job['title_en'],$job['title_pt'],$job['title_es'],
                  $job['description_en'],$job['description_pt'],$job['description_es'],
                  $job['sort_order'],$job['is_active']];
            if ($id > 0) {
                $p[] = $id;
                db()->prepare("UPDATE jobs SET title_en=?, title_pt=?, title_es=?, description_en=?, description_pt=?,
                               description_es=?, sort_order=?, is_active=? WHERE id=?")->execute($p);
                $flash = 'Job opening updated.';
            } else {
                db()->prepare("INSERT INTO jobs (title_en, title_pt, title_es, description_en, description_pt,
                               description_es, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?)")->execute($p);
                $flash = 'Job opening added.';
            }
            $editId = 0;
            $job = array_map(fn($v) => is_int($v) ? 0 : '', $job);
            $job['is_active'] = 1;
        } else {
            $editId = $id;
        }
    }
} elseif ($editId > 0) {
    $s = db()->prepare("SELECT * FROM jobs WHERE id = ?");
    $s->execute([$editId]);
    $job = $s->fetch() ?: $job;
}

$rows = db()->query("SELECT id, title_en, title_pt, is_active, sort_order FROM jobs ORDER BY sort_order ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — Job openings</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="wrap">
    <div class="page-head">
      <h1>Job openings</h1>
      <a href="applications.php" class="btn">📄 View applications</a>
    </div>

    <?php if ($flash): ?><p class="alert alert-ok"><?= e($flash) ?></p><?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div>
    <?php endif; ?>

    <div class="table-scroll">
      <table class="grid">
        <thead><tr><th>Title (EN)</th><th>Título (PT)</th><th>Order</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e($r['title_en']) ?></td>
            <td><?= e($r['title_pt']) ?></td>
            <td><?= (int)$r['sort_order'] ?></td>
            <td><?= $r['is_active'] ? '<span class="dot on"></span>Open' : '<span class="dot off"></span>Hidden' ?></td>
            <td class="row-actions">
              <a href="jobs.php?edit=<?= (int)$r['id'] ?>#form" class="btn btn-sm">Edit</a>
              <form method="post" onsubmit="return confirm('Remove this job opening?');" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="5" class="muted" style="text-align:center;padding:24px">No job openings. Add one below.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <h2 id="form"><?= $editId ? 'Edit job opening' : 'Add job opening' ?></h2>
    <form method="post" class="form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= (int)$editId ?>">

      <fieldset>
        <legend>Title</legend>
        <label>English * <input type="text" name="title_en" value="<?= e($job['title_en']) ?>" required></label>
        <label>Portuguese <input type="text" name="title_pt" value="<?= e($job['title_pt']) ?>"></label>
        <label>Spanish <input type="text" name="title_es" value="<?= e($job['title_es']) ?>"></label>
      </fieldset>
      <fieldset>
        <legend>Short description (optional)</legend>
        <label>English <textarea name="description_en" rows="2"><?= e($job['description_en']) ?></textarea></label>
        <label>Portuguese <textarea name="description_pt" rows="2"><?= e($job['description_pt']) ?></textarea></label>
        <label>Spanish <textarea name="description_es" rows="2"><?= e($job['description_es']) ?></textarea></label>
      </fieldset>
      <div class="two">
        <label>Display order <input type="number" name="sort_order" value="<?= (int)$job['sort_order'] ?>"></label>
        <label class="check">
          <input type="checkbox" name="is_active" value="1" <?= $job['is_active'] ? 'checked' : '' ?>> Open (visible on the website)
        </label>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <?php if ($editId): ?><a href="jobs.php" class="btn">Cancel</a><?php endif; ?>
      </div>
    </form>
  </main>
</body>
</html>
