<?php
/* Shared page frame: left sidebar (drawer + bottom tabs on phones).
   page_head() prints <head> + navigation, page_foot() closes it.
   Employees only see their own pages; the Administration group is admin-only. */
require_once __DIR__ . '/chat.php';
require_once __DIR__ . '/media.php';

/* Small line icons (24×24, stroke = currentColor). */
function icon(string $name): string {
    $p = [
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'chat'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.4A8 8 0 1 1 21 12z"/>',
        'folder'   => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'pulse'    => '<path d="M3 12h4l3-7 4 14 3-7h4"/>',
        'pin'      => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.5 3-5.5 6.5-5.5s6.5 2 6.5 5.5M16 4.5a3.5 3.5 0 0 1 0 7M18 14.6c2 .7 3.5 2.4 3.5 5.4"/>',
        'file'     => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
        'logout'   => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 17l-5-5 5-5M5 12h11"/>',
        'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'camera'   => '<path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13.5" r="3.5"/>',
        'download' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        'image'    => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="M21 17l-5-5-9 8"/>',
    ][$name] ?? '';
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function lang_links(): string {
    $out = [];
    foreach (['en' => 'EN', 'pt' => 'PT', 'es' => 'ES'] as $code => $label) {
        $q = array_merge($_GET, ['lang' => $code]);
        $cls = current_lang() === $code ? ' class="on"' : '';
        $out[] = '<a href="?' . e(http_build_query($q)) . '"' . $cls . '>' . $label . '</a>';
    }
    return '<span class="langs">' . implode('', $out) . '</span>';
}

/* Menu for this person. Paths are relative to /sistema/. */
function nav_items(array $user): array {
    $mine = [
        ['painel.php',    'clock',  t('my_punch')],
        ['historico.php', 'chart',  t('reports')],
        ['pontos.php',    'list',   t('punches')],
        ['mensagens.php', 'chat',   t('messages'), 'msgs'],
        ['arquivos.php',  'folder', t('files')],
        ['perfil.php',    'user',   t('profile')],
    ];
    $admin = $user['role'] !== 'admin' ? [] : [
        ['admin/hoje.php',       'pulse',    t('team_today')],
        ['admin/pontos.php',     'list',     t('team_punches')],
        ['admin/relatorios.php', 'chart',    t('team_reports')],
        ['admin/curriculos.php', 'file',     t('cvs'), 'cvs'],
        ['admin/',               'users',    t('users')],
        ['admin/jornadas.php',   'calendar', t('schedules')],
        ['admin/locais.php',     'pin',      t('locations')],
    ];
    return [$mine, $admin];
}
function nav_current(): string {
    $dir = basename(dirname($_SERVER['SCRIPT_NAME']));
    $file = basename($_SERVER['SCRIPT_NAME']);
    $path = ($dir === 'admin' ? 'admin/' : '') . $file;
    $alias = ['admin/index.php' => 'admin/', 'admin/usuario.php' => 'admin/', 'admin/importar.php' => 'admin/',
              'conversa.php' => 'mensagens.php', 'senha.php' => 'perfil.php'];
    return $alias[$path] ?? $path;
}
function nav_link(array $item, string $b, string $current, array $badges, string $cls = 'nav-item'): string {
    [$href, $ic, $label] = $item;
    $badge = isset($item[3]) ? (int)($badges[$item[3]] ?? 0) : 0;
    $on = $current === $href ? ' active' : '';
    $id = isset($item[3]) ? ' data-badge="' . $item[3] . '"' : '';
    return '<a href="' . e($b . $href) . '" class="' . $cls . $on . '"' . $id . '>' . icon($ic) . '<span class="nav-label">' . e($label) . '</span>'
         . (isset($item[3]) ? '<span class="badge"' . ($badge ? '' : ' hidden') . '>' . $badge . '</span>' : '') . '</a>';
}

function page_head(string $title, ?array $user = null, string $bodyClass = ''): void {
    $b = base();
    $badges = [];
    if ($user) {
        $badges['msgs'] = chat_unread_total($user);
        if ($user['role'] === 'admin') {
            try { $badges['cvs'] = (int)pt_db()->query("SELECT COUNT(*) FROM job_applications WHERE is_read = 0")->fetchColumn(); }
            catch (PDOException $e) { $badges['cvs'] = 0; }
        }
    }
    ?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#0c2748">
  <title><?= e($title) ?> — <?= e(t('app_name')) ?></title>
  <link rel="icon" href="<?= $b ?>../images/favicon.svg" type="image/svg+xml">
  <link rel="manifest" href="<?= $b ?>manifest.webmanifest">
  <link rel="apple-touch-icon" href="<?= $b ?>../images/apple-touch-icon.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="VST Ponto">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <?php if ($user): ?><meta name="csrf" content="<?= e(csrf_token()) ?>"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= $b ?>assets/app.css">
  <script src="<?= $b ?>assets/app.js" defer></script>
</head>
<body class="<?= $user ? 'app ' : '' ?><?= e($bodyClass) ?>">
<?php if ($user):
    [$mine, $admin] = nav_items($user);
    $cur = nav_current(); ?>
<aside class="sidebar" id="sidebar" aria-label="Menu">
  <a href="<?= $b ?>painel.php" class="side-brand">
    <img src="<?= $b ?>../images/logo/vst-logo.svg" alt="VST"><span><?= e(t('brand_sub')) ?></span>
  </a>
  <nav class="side-nav">
    <?php foreach ($mine as $it) echo nav_link($it, $b, $cur, $badges); ?>
    <?php if ($admin): ?>
      <p class="side-group"><?= e(t('administration')) ?></p>
      <?php foreach ($admin as $it) echo nav_link($it, $b, $cur, $badges); ?>
    <?php endif; ?>
  </nav>
  <div class="side-foot">
    <a href="<?= $b ?>perfil.php" class="side-user">
      <?= avatar_html($user, 'sm') ?>
      <span><strong><?= e($user['full_name']) ?></strong><small><?= e(t('role_' . $user['role'])) ?></small></span>
    </a>
    <div class="side-actions">
      <?= lang_links() ?>
      <form method="post" action="<?= $b ?>sair.php"><?= csrf_field() ?><button class="side-logout" title="<?= e(t('logout')) ?>"><?= icon('logout') ?></button></form>
    </div>
  </div>
</aside>
<div class="side-backdrop" id="side-backdrop"></div>
<div class="app-main">
  <header class="mobile-bar">
    <button type="button" class="icon-btn" id="menu-btn" aria-label="Menu" aria-controls="sidebar"><?= icon('menu') ?></button>
    <span class="mobile-title"><?= e($title) ?></span>
    <a href="<?= $b ?>perfil.php" aria-label="<?= e(t('profile')) ?>"><?= avatar_html($user, 'sm') ?></a>
  </header>
<?php endif; ?>
<main class="<?= $user ? 'page' : '' ?>">
<?php foreach (take_flashes() as [$type, $msg]): ?>
  <p class="alert alert-<?= e($type) ?>"><?= e($msg) ?></p>
<?php endforeach;
}

function page_foot(): void {
    $user = current_user();
    ?>
</main>
<?php if ($user && (int)$user['must_change_password'] === 0):
    $b = base();
    [$mine] = nav_items($user);
    $cur = nav_current();
    $badges = ['msgs' => chat_unread_total($user)];
    $tabs = [$mine[0], $mine[1], $mine[2], $mine[3], $mine[5]]; // punch, reports, punches, messages, profile ?>
</div>
<nav class="tabbar" aria-label="Menu">
  <?php foreach ($tabs as $it) echo nav_link($it, $b, $cur, $badges, 'tab'); ?>
</nav>
<script>
/* Sidebar drawer (phones) + new-message badge and tab title, checked every 30 s. */
(function () {
  var side = document.getElementById('sidebar'), back = document.getElementById('side-backdrop'), btn = document.getElementById('menu-btn');
  function toggle(open) { document.body.classList.toggle('side-open', open); }
  if (btn) btn.addEventListener('click', function () { toggle(!document.body.classList.contains('side-open')); });
  if (back) back.addEventListener('click', function () { toggle(false); });

  var badges = document.querySelectorAll('[data-badge="msgs"] .badge'), base = document.title;
  window.PT_setUnread = function (n) {
    badges.forEach(function (b) { b.textContent = n; b.hidden = !n; });
    document.title = (n ? '(' + n + ') ' : '') + base;
  };
  window.PT_setUnread(<?= (int)$badges['msgs'] ?>);
  if (window.CHAT) return; // the conversation page polls on its own
  setInterval(function () {
    if (document.hidden) return;
    fetch('<?= $b ?>api/chat.php?action=unread', { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j && j.ok) window.PT_setUnread(j.unread); })
      .catch(function () {});
  }, 30000);
})();
</script>
<?php elseif ($user): ?>
</div>
<?php endif; ?>
</body>
</html>
<?php
}
