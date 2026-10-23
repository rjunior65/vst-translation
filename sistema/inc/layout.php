<?php
/* Shared page frame. page_head() prints <head> + top bar, page_foot() closes it. */
require_once __DIR__ . '/chat.php';

function lang_links(): string {
    $out = [];
    foreach (['en' => 'EN', 'pt' => 'PT', 'es' => 'ES'] as $code => $label) {
        $q = array_merge($_GET, ['lang' => $code]);
        $cls = current_lang() === $code ? ' class="on"' : '';
        $out[] = '<a href="?' . e(http_build_query($q)) . '"' . $cls . '>' . $label . '</a>';
    }
    return '<span class="langs">' . implode('', $out) . '</span>';
}

function page_head(string $title, ?array $user = null, string $bodyClass = ''): void {
    $b = base();
    ?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#123b6d">
  <title><?= e($title) ?> — <?= e(t('app_name')) ?></title>
  <link rel="icon" href="<?= $b ?>../images/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="<?= $b ?>assets/app.css">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($user): ?>
<header class="topbar">
  <div class="wrap topbar-inner">
    <a href="<?= $b . e(home_for($user)) ?>" class="brand"><?= e(t('app_name')) ?></a>
    <nav class="topnav">
      <?php if ($user['role'] === 'admin'): ?>
        <a href="<?= $b ?>admin/hoje.php"><?= e(t('today')) ?></a>
        <a href="<?= $b ?>admin/pontos.php"><?= e(t('punches')) ?></a>
        <a href="<?= $b ?>admin/relatorios.php"><?= e(t('reports')) ?></a>
        <a href="<?= $b ?>admin/"><?= e(t('users')) ?></a>
        <a href="<?= $b ?>admin/jornadas.php"><?= e(t('schedules')) ?></a>
        <a href="<?= $b ?>admin/locais.php"><?= e(t('locations')) ?></a>
      <?php endif; ?>
      <a href="<?= $b ?>painel.php"><?= e(t('my_punch')) ?></a>
      <a href="<?= $b ?>historico.php"><?= e(t('history')) ?></a>
      <?php $unread = chat_unread_total($user); ?>
      <a href="<?= $b ?>mensagens.php" class="nav-msgs"><?= e(t('messages')) ?> <span class="badge" id="unread-badge" <?= $unread ? '' : 'hidden' ?>><?= $unread ?></span></a>
      <a href="<?= $b ?>senha.php"><?= e(t('my_password')) ?></a>
      <form method="post" action="<?= $b ?>sair.php" class="inline"><?= csrf_field() ?><button class="linklike logout"><?= e(t('logout')) ?></button></form>
      <?= lang_links() ?>
    </nav>
  </div>
</header>
<?php endif; ?>
<main class="<?= $user ? 'wrap page' : '' ?>">
<?php foreach (take_flashes() as [$type, $msg]): ?>
  <p class="alert alert-<?= e($type) ?>"><?= e($msg) ?></p>
<?php endforeach;
}

function page_foot(): void {
    $user = current_user();
    ?>
</main>
<?php if ($user && (int)$user['must_change_password'] === 0): ?>
<script>
/* New-message badge + tab title, checked every 30 s on every page. */
(function () {
  var badge = document.getElementById('unread-badge'), base = document.title;
  window.PT_setUnread = function (n) {
    if (!badge) return;
    badge.textContent = n; badge.hidden = !n;
    document.title = (n ? '(' + n + ') ' : '') + base;
  };
  if (badge) window.PT_setUnread(parseInt(badge.textContent, 10) || 0);
  if (window.CHAT) return; // the conversation page polls on its own
  setInterval(function () {
    if (document.hidden) return;
    fetch('<?= base() ?>api/chat.php?action=unread', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j && j.ok) window.PT_setUnread(j.unread); })
      .catch(function () {});
  }, 30000);
})();
</script>
<?php endif; ?>
</body>
</html>
<?php
}
