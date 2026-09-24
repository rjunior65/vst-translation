<?php
/* ============================================================
   ONE-TIME admin creation.
   Open this in the browser once (https://yoursite.com/tools/create-admin.php),
   create your admin login, then DELETE THIS FILE.
   For safety it only works while there is NO admin yet.
   ============================================================ */
require_once __DIR__ . '/../includes/db.php';

$done = false;
$error = '';

$count = (int) db()->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();

if ($count > 0) {
    $error = 'An admin already exists. For security, delete this file (tools/create-admin.php).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
        $error = 'Enter a name, a valid email, and a password of at least 8 characters.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = db()->prepare("INSERT INTO admin_users (name, email, password_hash) VALUES (?,?,?)");
        $stmt->execute([$name, $email, $hash]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>Create VST admin</title>
  <link rel="stylesheet" href="../admin/assets/admin.css">
</head>
<body class="login-body">
  <?php if ($done): ?>
    <div class="login-card">
      <h1>✅ Admin created</h1>
      <p>You can now <a href="../admin/index.php">sign in</a>.</p>
      <p class="alert alert-error"><strong>Important:</strong> delete the file
        <code>tools/create-admin.php</code> now.</p>
    </div>
  <?php else: ?>
    <form class="login-card" method="post" autocomplete="off">
      <div class="login-logo">VST</div>
      <h1>Create admin</h1>
      <?php if ($error): ?><p class="alert alert-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <?php if ($count === 0): ?>
        <label>Name</label>
        <input type="text" name="name" required>
        <label>Email (your login)</label>
        <input type="email" name="email" required>
        <label>Password (min 8 chars)</label>
        <input type="password" name="password" minlength="8" required>
        <button class="btn btn-primary btn-block" type="submit">Create admin</button>
      <?php endif; ?>
    </form>
  <?php endif; ?>
</body>
</html>
