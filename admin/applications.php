<?php
/* Careers: applications received from the website (read, download CV, delete). */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/careers-config.php';
require_login();

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id > 0 && $action === 'delete') {
        $s = db()->prepare("SELECT cv_file FROM job_applications WHERE id = ?");
        $s->execute([$id]);
        $file = basename((string)$s->fetchColumn());
        db()->prepare("DELETE FROM job_applications WHERE id = ?")->execute([$id]);
        if ($file !== '' && is_file(UPLOAD_DIR_CV . $file)) @unlink(UPLOAD_DIR_CV . $file);
        $flash = 'Application and CV deleted.';
    } elseif ($id > 0 && $action === 'toggle_read') {
        db()->prepare("UPDATE job_applications SET is_read = 1 - is_read WHERE id = ?")->execute([$id]);
    }
}

$rows = db()->query(
    "SELECT id, job_title, name, email, phone, message, cv_original, is_read, created_at
     FROM job_applications ORDER BY created_at DESC, id DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — Applications</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="wrap">
    <div class="page-head">
      <h1>Job applications</h1>
      <a href="jobs.php" class="btn">💼 Manage job openings</a>
    </div>

    <?php if ($flash): ?><p class="alert alert-ok"><?= e($flash) ?></p><?php endif; ?>

    <div class="table-scroll">
      <table class="grid">
        <thead><tr><th>Date</th><th>Position</th><th>Candidate</th><th>Message</th><th>CV</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr<?= $r['is_read'] ? '' : ' style="font-weight:600"' ?>>
            <td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
            <td><span class="tag"><?= e($r['job_title']) ?></span></td>
            <td>
              <?= e($r['name']) ?><br>
              <a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a>
              <?php if ($r['phone'] !== ''): ?><br><?= e($r['phone']) ?><?php endif; ?>
            </td>
            <td style="max-width:320px;white-space:pre-wrap;font-weight:400"><?= e($r['message']) ?></td>
            <td><a class="btn btn-sm btn-primary" href="cv.php?id=<?= (int)$r['id'] ?>">⬇ Download</a></td>
            <td class="row-actions">
              <form method="post" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_read">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-sm"><?= $r['is_read'] ? 'Mark unread' : 'Mark read' ?></button>
              </form>
              <form method="post" onsubmit="return confirm('Delete this application and its CV?');" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="muted" style="text-align:center;padding:24px">No applications yet.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</body>
</html>
