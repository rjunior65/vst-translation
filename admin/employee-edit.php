<?php
/* Add or edit a team member (with photo upload). */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$id     = (int)($_GET['id'] ?? 0);
$errors = [];

/* Defaults / load existing */
$emp = [
    'name' => '', 'position_en' => '', 'position_pt' => '', 'position_es' => '',
    'department' => 'Other', 'phone' => '', 'email' => '',
    'description_en' => '', 'description_pt' => '', 'description_es' => '',
    'image' => '', 'sort_order' => 0, 'is_active' => 1,
];
if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) { header('Location: employees.php'); exit; }
    $emp = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Collect + trim
    foreach (['name','position_en','position_pt','position_es','phone','email',
              'description_en','description_pt','description_es'] as $f) {
        $emp[$f] = trim($_POST[$f] ?? '');
    }
    $emp['department'] = in_array($_POST['department'] ?? '', departments(), true)
        ? $_POST['department'] : 'Other';
    $emp['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $emp['is_active']  = isset($_POST['is_active']) ? 1 : 0;

    // Validate
    if ($emp['name'] === '')             $errors[] = 'Name is required.';
    if ($emp['email'] !== '' && !filter_var($emp['email'], FILTER_VALIDATE_EMAIL))
        $errors[] = 'Email is not valid.';

    // Photo upload (optional)
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
                if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
                $newname = 'emp_' . bin2hex(random_bytes(8)) . '.' . $ext;
                if (@move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newname)) {
                    // remove previous uploaded photo (never delete bundled images/)
                    if (strpos((string)$emp['image'], 'uploads/team/') === 0) {
                        $old = __DIR__ . '/../' . $emp['image'];
                        if (is_file($old)) @unlink($old);
                    }
                    $emp['image'] = UPLOAD_URL . $newname;
                } else {
                    $errors[] = 'Could not save the uploaded photo (check folder permissions).';
                }
            }
        }
    }

    if (!$errors) {
        if ($id > 0) {
            $sql = "UPDATE employees SET name=?, position_en=?, position_pt=?, position_es=?,
                    department=?, phone=?, email=?, description_en=?, description_pt=?, description_es=?,
                    image=?, sort_order=?, is_active=? WHERE id=?";
            $params = [$emp['name'],$emp['position_en'],$emp['position_pt'],$emp['position_es'],
                       $emp['department'],$emp['phone'],$emp['email'],
                       $emp['description_en'],$emp['description_pt'],$emp['description_es'],
                       $emp['image'],$emp['sort_order'],$emp['is_active'],$id];
        } else {
            $sql = "INSERT INTO employees (name, position_en, position_pt, position_es,
                    department, phone, email, description_en, description_pt, description_es,
                    image, sort_order, is_active)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $params = [$emp['name'],$emp['position_en'],$emp['position_pt'],$emp['position_es'],
                       $emp['department'],$emp['phone'],$emp['email'],
                       $emp['description_en'],$emp['description_pt'],$emp['description_es'],
                       $emp['image'],$emp['sort_order'],$emp['is_active']];
        }
        db()->prepare($sql)->execute($params);
        header('Location: employees.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — <?= $id ? 'Edit' : 'Add' ?> member</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="wrap">
    <p><a href="employees.php" class="back">&larr; Back to team</a></p>
    <h1><?= $id ? 'Edit team member' : 'Add team member' ?></h1>

    <?php if ($errors): ?>
      <div class="alert alert-error"><ul><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?>

      <label>Full name *
        <input type="text" name="name" value="<?= e($emp['name']) ?>" required>
      </label>

      <div class="two">
        <label>Department
          <select name="department">
            <?php foreach (departments() as $d): ?>
              <option value="<?= e($d) ?>" <?= $emp['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Display order
          <input type="number" name="sort_order" value="<?= (int)$emp['sort_order'] ?>">
        </label>
      </div>

      <fieldset>
        <legend>Position / job title</legend>
        <label>English <input type="text" name="position_en" value="<?= e($emp['position_en']) ?>"></label>
        <label>Portuguese <input type="text" name="position_pt" value="<?= e($emp['position_pt']) ?>"></label>
        <label>Spanish <input type="text" name="position_es" value="<?= e($emp['position_es']) ?>"></label>
        <p class="hint">Only English is required. If PT/ES are empty, the site shows the English text.</p>
      </fieldset>

      <div class="two">
        <label>Phone <input type="text" name="phone" value="<?= e($emp['phone']) ?>" placeholder="+592 ..."></label>
        <label>Email <input type="email" name="email" value="<?= e($emp['email']) ?>"></label>
      </div>

      <fieldset>
        <legend>Short description (optional)</legend>
        <label>English <textarea name="description_en" rows="2"><?= e($emp['description_en']) ?></textarea></label>
        <label>Portuguese <textarea name="description_pt" rows="2"><?= e($emp['description_pt']) ?></textarea></label>
        <label>Spanish <textarea name="description_es" rows="2"><?= e($emp['description_es']) ?></textarea></label>
      </fieldset>

      <label>Photo
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        <span class="hint">JPG/PNG/WEBP, max <?= round(MAX_UPLOAD_BYTES / 1048576) ?> MB. Leave empty to keep the current photo.</span>
      </label>
      <?php if (!empty($emp['image'])): ?>
        <img class="preview" src="../<?= e($emp['image']) ?>" alt="current photo"
             onerror="this.onerror=null;this.src='../images/placeholder.svg';">
      <?php endif; ?>

      <label class="check">
        <input type="checkbox" name="is_active" value="1" <?= $emp['is_active'] ? 'checked' : '' ?>>
        Visible on the website
      </label>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="employees.php" class="btn">Cancel</a>
      </div>
    </form>
  </main>
</body>
</html>
