<?php
/* Tourism gallery: add + delete photos. */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$flash = ''; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $img = db()->prepare("SELECT image FROM gallery WHERE id = ?");
            $img->execute([$id]);
            $path = (string)$img->fetchColumn();
            db()->prepare("DELETE FROM gallery WHERE id = ?")->execute([$id]);
            if ($path && strpos($path, 'uploads/tourism/') === 0) {
                $abs = __DIR__ . '/../' . $path;
                if (is_file($abs)) @unlink($abs);
            }
            $flash = 'Photo removed.';
        }
    } elseif ($action === 'add') {
        $caption = trim($_POST['caption'] ?? '');
        $order   = (int)($_POST['sort_order'] ?? 0);
        if (empty($_FILES['photo']['name']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Please choose a photo to upload.';
        } else {
            $file = $_FILES['photo'];
            $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload failed (code ' . (int)$file['error'] . ').';
            } elseif ($file['size'] > MAX_UPLOAD_BYTES) {
                $errors[] = 'Photo too large (max ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB).';
            } else {
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $info = @getimagesize($file['tmp_name']);
                if (!isset($allowed[$ext]) || $info === false || !in_array($info['mime'], $allowed, true)) {
                    $errors[] = 'Photo must be a real JPG, PNG or WEBP image.';
                } else {
                    if (!is_dir(UPLOAD_DIR_TOURISM)) @mkdir(UPLOAD_DIR_TOURISM, 0755, true);
                    $newname = 'gal_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (@move_uploaded_file($file['tmp_name'], UPLOAD_DIR_TOURISM . $newname)) {
                        db()->prepare("INSERT INTO gallery (image, caption, sort_order, is_active) VALUES (?,?,?,1)")
                            ->execute([UPLOAD_URL_TOURISM . $newname, $caption, $order]);
                        $flash = 'Photo added.';
                    } else {
                        $errors[] = 'Could not save the photo (check folder permissions).';
                    }
                }
            }
        }
    }
}

$rows = db()->query("SELECT id, image, caption FROM gallery ORDER BY sort_order ASC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — Gallery</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="wrap">
    <p><a href="destinations.php" class="back">&larr; Back to destinations</a></p>
    <h1>Tourism gallery</h1>
    <?php if ($flash): ?><p class="alert alert-ok"><?= e($flash) ?></p><?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form" style="max-width:520px">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <h2 style="margin-top:0">Add a photo</h2>
      <label>Photo <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label>
      <label>Caption <input type="text" name="caption" placeholder="e.g. Sunset over Malali"></label>
      <label>Display order <input type="number" name="sort_order" value="0"></label>
      <div class="form-actions"><button class="btn btn-primary" type="submit">Upload photo</button></div>
    </form>

    <h2>Photos (<?= count($rows) ?>)</h2>
    <div class="gallery-admin">
      <?php foreach ($rows as $r): ?>
        <figure class="ga-item">
          <img src="../<?= e($r['image']) ?>" alt="" onerror="this.onerror=null;this.src='../images/placeholder.svg';">
          <figcaption><?= e($r['caption']) ?></figcaption>
          <form method="post" onsubmit="return confirm('Remove this photo?');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
          </form>
        </figure>
      <?php endforeach; ?>
      <?php if (!$rows): ?><p class="muted">No photos yet.</p><?php endif; ?>
    </div>
  </main>
</body>
</html>
