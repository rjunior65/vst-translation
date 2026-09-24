<?php
/* Edit contacts + social links. */
require_once __DIR__ . '/../includes/auth.php';
require_login();

$fields = [
    'address'  => 'Address',
    'phone'    => 'Main phone',
    'whatsapp' => 'WhatsApp number',
    'email'    => 'Main email',
    'hours'    => 'Business hours',
    'social_instagram' => 'Instagram URL',
    'social_facebook'  => 'Facebook URL',
    'social_tiktok'    => 'TikTok URL',
    'social_youtube'   => 'YouTube URL',
    'social_linkedin'  => 'LinkedIn URL',
    'social_whatsapp'  => 'WhatsApp link (optional)',
];

$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = db()->prepare("INSERT INTO settings (skey, svalue) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)");
    foreach ($fields as $key => $label) {
        $stmt->execute([$key, trim($_POST[$key] ?? '')]);
    }
    $flash = 'Contacts saved.';
}

// Load current values
$vals = [];
foreach (db()->query("SELECT skey, svalue FROM settings")->fetchAll() as $r) {
    $vals[$r['skey']] = $r['svalue'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>VST Admin — Contacts</title>
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <?php include __DIR__ . '/_header.php'; ?>
  <main class="wrap">
    <h1>Contacts &amp; social links</h1>
    <p class="muted">Empty fields are hidden automatically on the public site.</p>
    <?php if ($flash): ?><p class="alert alert-ok"><?= e($flash) ?></p><?php endif; ?>

    <form method="post" class="form">
      <?= csrf_field() ?>
      <fieldset>
        <legend>Contact</legend>
        <?php foreach (['address','phone','whatsapp','email','hours'] as $k): ?>
          <label><?= e($fields[$k]) ?>
            <input type="text" name="<?= e($k) ?>" value="<?= e($vals[$k] ?? '') ?>">
          </label>
        <?php endforeach; ?>
      </fieldset>
      <fieldset>
        <legend>Social media (full URLs — leave empty to hide the icon)</legend>
        <?php foreach (['social_instagram','social_facebook','social_tiktok','social_youtube','social_linkedin','social_whatsapp'] as $k): ?>
          <label><?= e($fields[$k]) ?>
            <input type="text" name="<?= e($k) ?>" value="<?= e($vals[$k] ?? '') ?>" placeholder="https://...">
          </label>
        <?php endforeach; ?>
      </fieldset>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="../index.html#contact" target="_blank" rel="noopener" class="btn">View on site</a>
      </div>
    </form>
  </main>
</body>
</html>
