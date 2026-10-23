<?php
/* Change password. Mandatory while must_change_password = 1:
   every other page redirects here until a personal password is set. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

$user = current_user();
if (!$user) redirect('login.php');
$forced = (int)$user['must_change_password'] === 1;

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = (string)($_POST['current'] ?? '');
    $new     = (string)($_POST['new'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    if (!password_verify($current, $user['password_hash'])) {
        $error = t('pw_wrong_current');
    } elseif ($problem = password_problem($new, $confirm)) {
        $error = t($problem);
    } elseif ($new === $current) {
        $error = t('pw_same_as_old');
    } else {
        pt_db()->prepare("UPDATE pt_users SET password_hash = ?, must_change_password = 0 WHERE id = ?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        flash('ok', t('pw_changed'));
        redirect(home_for($user));
    }
}

if ($forced) {
    page_head(t('pw_title'), null, 'auth-body');
} else {
    page_head(t('pw_title'), $user);
}
?>
  <form class="<?= $forced ? 'auth-card' : 'form' ?>" method="post" autocomplete="off">
    <?php if ($forced): ?>
      <img src="../images/logo/vst-logo.svg" alt="VST" class="auth-logo">
    <?php endif; ?>
    <h1><?= e(t('pw_title')) ?></h1>
    <?php if ($forced): ?><p class="alert alert-info"><?= e(t('pw_must_change')) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <input type="text" name="username" value="<?= e($user['username']) ?>" autocomplete="username" hidden>
    <label for="current"><?= e(t('pw_current')) ?></label>
    <input type="password" id="current" name="current" required autocomplete="current-password" <?= $forced ? 'autofocus' : '' ?>>
    <label for="new"><?= e(t('pw_new')) ?></label>
    <input type="password" id="new" name="new" required minlength="<?= PT_MIN_PASSWORD ?>" autocomplete="new-password">
    <span class="hint"><?= e(t('pw_rules', ['n' => PT_MIN_PASSWORD])) ?></span>
    <label for="confirm"><?= e(t('pw_confirm')) ?></label>
    <input type="password" id="confirm" name="confirm" required minlength="<?= PT_MIN_PASSWORD ?>" autocomplete="new-password">
    <button type="submit" class="btn btn-primary btn-block"><?= e(t('save')) ?></button>
    <?php if ($forced): ?>
      <p class="auth-foot"><?= e($user['full_name']) ?> · <code><?= e($user['username']) ?></code></p>
      <div class="auth-langs"><?= lang_links() ?></div>
    <?php endif; ?>
  </form>
  <?php if ($forced): ?>
  <form method="post" action="sair.php" style="position:fixed;top:12px;right:16px">
    <?= csrf_field() ?><button class="btn btn-sm"><?= e(t('logout')) ?></button>
  </form>
  <?php endif; ?>
<?php page_foot();
