<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$total  = (int) db()->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$active = (int) db()->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — Dashboard</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>

  <main class="wrap">
    <h1>Dashboard</h1>
    <p class="muted">Welcome, <?= e(current_admin_name()) ?>.</p>

    <div class="cards">
      <a class="tile" href="employees.php">
        <span class="tile-num"><?= $active ?></span>
        <span class="tile-label">Active team members</span>
      </a>
      <div class="tile">
        <span class="tile-num"><?= $total ?></span>
        <span class="tile-label">Total records</span>
      </div>
    </div>

    <h2>Manage</h2>
    <ul class="link-list">
      <li><a href="employees.php">👥 Team members — add, edit, remove</a></li>
      <li><a href="destinations.php">🌴 Tourism destinations &amp; gallery</a></li>
      <li><a href="settings.php">☎️ Contacts &amp; social links</a></li>
    </ul>

    <p class="muted"><a href="../index.html" target="_blank" rel="noopener">↗ View the public website</a></p>
  </main>
</body>
</html>
