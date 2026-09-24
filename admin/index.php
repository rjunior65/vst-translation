<?php
/* Admin login. Email + password. */
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$error = '';

// IP-based brute-force lockout (stored in the database, survives cookie clearing).
$LOCK_AFTER  = 8;   // max failed attempts per IP...
$WINDOW_MIN  = 15;  // ...within this many minutes
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // Housekeeping: drop attempts older than a day.
    db()->query("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");

    // How many recent failures from this IP?
    $win = (int)$WINDOW_MIN; // safe int, interpolated (INTERVAL can't be a bound param)
    $stmt = db()->prepare("SELECT COUNT(*) FROM login_attempts
                           WHERE ip = ? AND attempted_at > (NOW() - INTERVAL $win MINUTE)");
    $stmt->execute([$ip]);
    $recent = (int)$stmt->fetchColumn();

    if ($recent >= $LOCK_AFTER) {
        $error = 'Too many attempts. Please wait a few minutes and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        $stmt = db()->prepare("SELECT * FROM admin_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($pass, $admin['password_hash'])) {
            db()->prepare("DELETE FROM login_attempts WHERE ip = ?")->execute([$ip]); // clear on success
            login_admin($admin);
            header('Location: dashboard.php');
            exit;
        }

        // record the failed attempt
        db()->prepare("INSERT INTO login_attempts (ip, attempted_at) VALUES (?, NOW())")->execute([$ip]);
        $error = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — Sign in</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="login-body">
  <form class="login-card" method="post" autocomplete="off">
    <div class="login-logo">VST</div>
    <h1>Admin Sign in</h1>
    <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required autofocus>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <button type="submit" class="btn btn-primary btn-block">Sign in</button>
    <p class="login-hint">Authorized personnel only.</p>
  </form>
</body>
</html>
