<?php
/* ============================================================
   One-time setup: creates the pt_ tables and the first administrator.
   Proof of ownership = the MySQL password from includes/config.php.
   Locks itself as soon as an administrator exists.
   ============================================================ */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

function admin_exists(): bool {
    try {
        return (int)pt_db()->query("SELECT COUNT(*) FROM pt_users WHERE role = 'admin'")->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false; // table not created yet
    }
}

function run_schema(): void {
    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (array_filter(array_map('trim', explode(";\n", str_replace("\r\n", "\n", $sql)))) as $stmt) {
        pt_db()->exec($stmt);
    }
}

$done   = admin_exists();
$errors = [];
$name   = '';

if (!$done && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name    = trim(preg_replace('/\s+/', ' ', (string)($_POST['full_name'] ?? '')));
    $dbPass  = (string)($_POST['db_pass'] ?? '');
    $pass    = (string)($_POST['new'] ?? '');
    $confirm = (string)($_POST['confirm'] ?? '');

    if (!hash_equals((string)DB_PASS, $dbPass)) {
        $errors[] = 'Senha do banco de dados incorreta.';
        sleep(2); // slow down guessing
    }
    if ($name === '') $errors[] = t('err_name');
    if ($p = password_problem($pass, $confirm)) $errors[] = t($p);

    if (!$errors) {
        run_schema();
        $username = generate_username($name);
        pt_db()->prepare("INSERT INTO pt_users (full_name, username, password_hash, must_change_password, role, work_mode)
                          VALUES (?, ?, ?, 0, 'admin', 'flexible')")
            ->execute([$name, $username, password_hash($pass, PASSWORD_DEFAULT)]);
        $_SESSION['pt_uid'] = (int)pt_db()->lastInsertId();
        audit('install', 'system', null, null, ['admin' => $username]);
        session_regenerate_id(true);
        flash('ok', "Instalação concluída. Seu usuário é {$username}.");
        redirect('admin/hoje.php');
    }
}

page_head(t('install_title'), null, 'auth-body');
?>
  <form class="auth-card" method="post" autocomplete="off">
    <img src="../images/logo/vst-logo.svg" alt="VST" class="auth-logo">
    <h1><?= e(t('install_title')) ?></h1>
    <?php if ($done): ?>
      <p class="alert alert-ok">O sistema já está instalado.</p>
      <a href="login.php" class="btn btn-primary btn-block"><?= e(t('login_btn')) ?></a>
    <?php else: ?>
      <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err) echo e($err) . '<br>'; ?></div><?php endif; ?>
      <p class="muted">Cria as tabelas do ponto no banco do site e o primeiro administrador.</p>
      <?= csrf_field() ?>
      <label for="db_pass">Senha do banco de dados (includes/config.php)</label>
      <input type="password" id="db_pass" name="db_pass" required>
      <label for="full_name">Nome completo do administrador</label>
      <input type="text" id="full_name" name="full_name" value="<?= e($name) ?>" required>
      <span class="hint">O usuário será gerado como nome.sobrenome.</span>
      <label for="new"><?= e(t('pw_new')) ?></label>
      <input type="password" id="new" name="new" required minlength="<?= PT_MIN_PASSWORD ?>" autocomplete="new-password">
      <span class="hint"><?= e(t('pw_rules', ['n' => PT_MIN_PASSWORD])) ?></span>
      <label for="confirm"><?= e(t('pw_confirm')) ?></label>
      <input type="password" id="confirm" name="confirm" required autocomplete="new-password">
      <button type="submit" class="btn btn-primary btn-block">Instalar</button>
    <?php endif; ?>
  </form>
<?php page_foot();
