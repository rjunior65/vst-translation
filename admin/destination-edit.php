<?php
/* Add or edit a tourism destination (with photo upload). */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$errors = [];

$d = [
    'name' => '', 'location_en' => '', 'location_pt' => '', 'location_es' => '',
    'description_en' => '', 'description_pt' => '', 'description_es' => '',
    'image' => '', 'sort_order' => 0, 'is_active' => 1,
];
if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { header('Location: destinations.php'); exit; }
    $d = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach (['name','location_en','location_pt','location_es',
              'description_en','description_pt','description_es'] as $f) {
        $d[$f] = trim($_POST[$f] ?? '');
    }
    $d['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $d['is_active']  = isset($_POST['is_active']) ? 1 : 0;

    if ($d['name'] === '') $errors[] = 'Name is required.';

    // Optional photo upload
    if (!empty($_FILES['photo']['name']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['photo'];
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Photo upload failed (code ' . (int)$file['error'] . ').';
        } elseif ($file['size'] > MAX_UPLOAD_BYTES) {
            $errors[] = 'Photo is too large (max ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB).';
        } else {
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $info = @getimagesize($file['tmp_name']);
            if (!isset($allowed[$ext]) || $info === false || !in_array($info['mime'], $allowed, true)) {
                $errors[] = 'Photo must be a real JPG, PNG or WEBP image.';
            } else {
                if (!is_dir(UPLOAD_DIR_TOURISM)) @mkdir(UPLOAD_DIR_TOURISM, 0755, true);
                $newname = 'dest_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (@move_uploaded_file($file['tmp_name'], UPLOAD_DIR_TOURISM . $newname)) {
                    if (strpos((string)$d['image'], 'uploads/tourism/') === 0) {
                        $old = __DIR__ . '/../' . $d['image'];
                        if (is_file($old)) @unlink($old);
                    }
                    $d['image'] = UPLOAD_URL_TOURISM . $newname;
                } else {
                    $errors[] = 'Could not save the uploaded photo (check folder permissions).';
                }
            }
        }
    }

    if (!$errors) {
        if ($id > 0) {
            $sql = "UPDATE destinations SET name=?, location_en=?, location_pt=?, location_es=?,
                    description_en=?, description_pt=?, description_es=?, image=?, sort_order=?, is_active=?
                    WHERE id=?";
            $params = [$d['name'],$d['location_en'],$d['location_pt'],$d['location_es'],
                       $d['description_en'],$d['description_pt'],$d['description_es'],
                       $d['image'],$d['sort_order'],$d['is_active'],$id];
        } else {
            $sql = "INSERT INTO destinations (name, location_en, location_pt, location_es,
                    description_en, description_pt, description_es, image, sort_order, is_active)
                    VALUES (?,?,?,?,?,?,?,?,?,?)";
            $params = [$d['name'],$d['location_en'],$d['location_pt'],$d['location_es'],
                       $d['description_en'],$d['description_pt'],$d['description_es'],
                       $d['image'],$d['sort_order'],$d['is_active']];
        }
        db()->prepare($sql)->execute($params);
        header('Location: destinations.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — <?= $id ? 'Edit' : 'Add' ?> destination</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="wrap">
    <p><a href="destinations.php" class="back">&larr; Back to destinations</a></p>
    <h1><?= $id ? 'Edit destination' : 'Add destination' ?></h1>
    <?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?>
      <div class="two">
        <label>Name *<input type="text" name="name" value="<?= e($d['name']) ?>" required></label>
        <label>Display order<input type="number" name="sort_order" value="<?= (int)$d['sort_order'] ?>"></label>
      </div>
      <fieldset>
        <legend>Location</legend>
        <label>English<input type="text" name="location_en" value="<?= e($d['location_en']) ?>"></label>
        <label>Portuguese<input type="text" name="location_pt" value="<?= e($d['location_pt']) ?>"></label>
        <label>Spanish<input type="text" name="location_es" value="<?= e($d['location_es']) ?>"></label>
      </fieldset>
      <fieldset>
        <legend>Description</legend>
        <label>English<textarea name="description_en" rows="2"><?= e($d['description_en']) ?></textarea></label>
        <label>Portuguese<textarea name="description_pt" rows="2"><?= e($d['description_pt']) ?></textarea></label>
        <label>Spanish<textarea name="description_es" rows="2"><?= e($d['description_es']) ?></textarea></label>
        <p class="hint">Only English is required; empty PT/ES fall back to English.</p>
      </fieldset>
      <label>Main photo
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        <span class="hint">JPG/PNG/WEBP, max <?= round(MAX_UPLOAD_BYTES/1048576) ?> MB. Leave empty to keep the current photo.</span>
      </label>
      <?php if (!empty($d['image'])): ?>
        <img class="preview" src="../<?= e($d['image']) ?>" alt="" onerror="this.onerror=null;this.src='../images/placeholder.svg';">
      <?php endif; ?>
      <label class="check"><input type="checkbox" name="is_active" value="1" <?= $d['is_active'] ? 'checked' : '' ?>> Visible on the website</label>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="destinations.php" class="btn">Cancel</a>
      </div>
    </form>
  </main>
</body>
</html>
