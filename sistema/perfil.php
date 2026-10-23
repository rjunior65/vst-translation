<?php
/* My profile: photo, name, e-mail and password. The username is kept as is. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

$user = require_user();
$uid  = (int)$user['id'];
$errors = ['info' => [], 'pw' => [], 'photo' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'info') {
        $name  = trim(preg_replace('/\s+/', ' ', (string)($_POST['full_name'] ?? '')));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if ($name === '' || mb_strlen($name) > 160) $errors['info'][] = t('err_name');
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) $errors['info'][] = t('err_email');
        if (!$errors['info']) {
            pt_db()->prepare("UPDATE pt_users SET full_name = ?, email = ? WHERE id = ?")->execute([$name, $email, $uid]);
            audit('update_profile', 'user', $uid, ['full_name' => $user['full_name'], 'email' => $user['email']],
                  ['full_name' => $name, 'email' => $email]);
            flash('ok', t('user_saved'));
            redirect('perfil.php');
        }
    }

    if ($action === 'password') {
        $current = (string)($_POST['current'] ?? '');
        $new     = (string)($_POST['new'] ?? '');
        $confirm = (string)($_POST['confirm'] ?? '');
        if (!password_verify($current, $user['password_hash'])) $errors['pw'][] = t('pw_wrong_current');
        elseif ($p = password_problem($new, $confirm))          $errors['pw'][] = t($p);
        elseif ($new === $current)                              $errors['pw'][] = t('pw_same_as_old');
        if (!$errors['pw']) {
            pt_db()->prepare("UPDATE pt_users SET password_hash = ?, must_change_password = 0 WHERE id = ?")
                ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            remember_forget_user($uid, true);   // other devices must sign in with the new password
            session_regenerate_id(true);
            flash('ok', t('pw_changed'));
            redirect('perfil.php');
        }
    }

    if ($action === 'photo') {
        [$name, $err] = image_save($_FILES['photo'] ?? ['error' => UPLOAD_ERR_NO_FILE], PT_AVATAR_DIR, 'u' . $uid . '_' . bin2hex(random_bytes(5)), 512);
        if ($err) {
            $errors['photo'][] = t($err);
        } else {
            if ($user['photo']) @unlink(PT_AVATAR_DIR . basename($user['photo']));
            pt_db()->prepare("UPDATE pt_users SET photo = ? WHERE id = ?")->execute([$name, $uid]);
            flash('ok', t('photo_saved'));
            redirect('perfil.php');
        }
    }

    if ($action === 'photo_remove' && $user['photo']) {
        @unlink(PT_AVATAR_DIR . basename($user['photo']));
        pt_db()->prepare("UPDATE pt_users SET photo = NULL WHERE id = ?")->execute([$uid]);
        flash('ok', t('photo_removed'));
        redirect('perfil.php');
    }
}

$show = fn(string $k) => $errors[$k] ? '<div class="alert alert-error">' . implode('<br>', array_map('e', $errors[$k])) . '</div>' : '';
$name  = $_POST['full_name'] ?? $user['full_name'];
$email = $_POST['email'] ?? $user['email'];

page_head(t('profile'), $user);
?>
  <h1><?= e(t('profile')) ?></h1>
  <div class="profile-grid">
    <section class="card profile-card">
      <?= avatar_html($user, 'xl') ?>
      <h2><?= e($user['full_name']) ?></h2>
      <p class="muted small"><code class="user"><?= e($user['username']) ?></code> · <?= e(t('role_' . $user['role'])) ?></p>
      <?= $show('photo') ?>
      <form method="post" enctype="multipart/form-data" id="photo-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="photo">
        <label class="btn btn-blue photo-pick"><?= icon('camera') ?> <?= e(t('photo_change')) ?>
          <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" onchange="this.form.submit()"></label>
      </form>
      <?php if ($user['photo']): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="photo_remove">
          <button class="btn btn-sm btn-danger" style="margin-top:.6rem"><?= e(t('photo_remove')) ?></button></form>
      <?php endif; ?>
      <p class="hint"><?= e(t('photo_hint')) ?></p>
    </section>

    <div>
      <form class="form" method="post" style="max-width:none">
        <h2 class="form-title"><?= e(t('my_info')) ?></h2>
        <?= $show('info') ?>
        <?= csrf_field() ?><input type="hidden" name="action" value="info">
        <label for="full_name"><?= e(t('full_name')) ?></label>
        <input type="text" id="full_name" name="full_name" value="<?= e($name) ?>" maxlength="160" required>
        <label for="email"><?= e(t('email')) ?></label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" maxlength="190" placeholder="nome@exemplo.com">
        <label><?= e(t('username')) ?></label>
        <input type="text" value="<?= e($user['username']) ?>" disabled>
        <span class="hint"><?= e(t('username_fixed')) ?></span>
        <div class="form-actions"><button class="btn btn-primary"><?= e(t('save')) ?></button></div>
      </form>

      <form class="form" method="post" id="senha" style="max-width:none;margin-top:16px" autocomplete="off">
        <h2 class="form-title"><?= e(t('pw_title')) ?></h2>
        <?= $show('pw') ?>
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <input type="text" name="username" value="<?= e($user['username']) ?>" autocomplete="username" hidden>
        <label for="current"><?= e(t('pw_current')) ?></label>
        <input type="password" id="current" name="current" required autocomplete="current-password">
        <div class="grid-2">
          <div><label for="new"><?= e(t('pw_new')) ?></label>
            <input type="password" id="new" name="new" required minlength="<?= PT_MIN_PASSWORD ?>" autocomplete="new-password"></div>
          <div><label for="confirm"><?= e(t('pw_confirm')) ?></label>
            <input type="password" id="confirm" name="confirm" required minlength="<?= PT_MIN_PASSWORD ?>" autocomplete="new-password"></div>
        </div>
        <span class="hint"><?= e(t('pw_rules', ['n' => PT_MIN_PASSWORD])) ?></span>
        <div class="form-actions"><button class="btn btn-blue"><?= e(t('pw_title')) ?></button></div>
      </form>
    </div>
  </div>
<?php page_foot();
