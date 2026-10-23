<?php
/* Sign in with username (firstname.lastname) + password.
   Lockout: too many failures from one IP OR against one username. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

if ($u = current_user()) redirect((int)$u['must_change_password'] === 1 ? 'senha.php' : home_for($u));

$LOCK_IP    = 10;  // failures per IP...
$LOCK_USER  = 5;   // ...or per username...
$WINDOW_MIN = 15;  // ...within this many minutes

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $ip       = client_ip();
    $username = strtolower(trim((string)($_POST['username'] ?? '')));
    $pass     = (string)($_POST['password'] ?? '');

    pt_db()->query("DELETE FROM pt_login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");

    $win = (int)$WINDOW_MIN; // safe int, interpolated (INTERVAL can't be a bound param)
    $st = pt_db()->prepare("SELECT
            SUM(ip = ?)       AS by_ip,
            SUM(username = ?) AS by_user
        FROM pt_login_attempts WHERE attempted_at > (NOW() - INTERVAL $win MINUTE)");
    $st->execute([$ip, $username]);
    $c = $st->fetch();

    if ((int)$c['by_ip'] >= $LOCK_IP || ($username !== '' && (int)$c['by_user'] >= $LOCK_USER)) {
        $error = t('login_locked', ['min' => $WINDOW_MIN]);
    } else {
        $st = pt_db()->prepare("SELECT * FROM pt_users WHERE username = ? AND is_active = 1 LIMIT 1");
        $st->execute([$username]);
        $user = $st->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            pt_db()->prepare("DELETE FROM pt_login_attempts WHERE ip = ? OR username = ?")->execute([$ip, $username]);
            login_user($user);
            if (!empty($_POST['remember'])) remember_issue((int)$user['id']);
            redirect((int)$user['must_change_password'] === 1 ? 'senha.php' : home_for($user));
        }

        pt_db()->prepare("INSERT INTO pt_login_attempts (ip, username, attempted_at) VALUES (?, ?, NOW())")
            ->execute([$ip, substr($username, 0, 80)]);
        $error = t('login_wrong');
    }
}

page_head(t('login_title'), null, 'auth-body');
?>
  <form class="auth-card" method="post" autocomplete="on">
    <img src="../images/logo/vst-logo.svg" alt="VST" class="auth-logo">
    <h1><?= e(t('app_name')) ?></h1>
    <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <label for="username"><?= e(t('username')) ?></label>
    <input type="text" id="username" name="username" value="<?= e($username) ?>" required autofocus
           autocapitalize="none" autocorrect="off" spellcheck="false" autocomplete="username" placeholder="nome.sobrenome">
    <span class="hint"><?= e(t('login_hint')) ?></span>
    <label for="password"><?= e(t('password')) ?></label>
    <input type="password" id="password" name="password" required autocomplete="current-password">
    <label class="remember"><input type="checkbox" name="remember" value="1" checked> <?= e(t('remember_me')) ?></label>
    <button type="submit" class="btn btn-primary btn-block"><?= e(t('login_btn')) ?></button>
    <p class="auth-foot"><?= e(t('login_forgot')) ?></p>
    <div class="auth-langs"><?= lang_links() ?></div>
  </form>
<?php page_foot();
