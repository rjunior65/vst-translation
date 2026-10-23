<?php
/* Files: every photo and document sent in the conversations this person can see
   (admins: all of them). Downloads go through arquivo.php, which checks access again. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';

$user  = require_user();
$admin = $user['role'] === 'admin';
$kind  = in_array($_GET['tipo'] ?? '', ['img', 'doc'], true) ? $_GET['tipo'] : '';
$q     = trim((string)($_GET['q'] ?? ''));

$where = [];
$args  = [];
if (!$admin) {
    $where[] = "(c.employee_user_id = ? OR c.id IN (SELECT conversation_id FROM pt_chat_members WHERE user_id = ?))";
    $args[] = $user['id'];
    $args[] = $user['id'];
}
$images = "('image/jpeg','image/png','image/webp','image/gif')";
if ($kind === 'img') $where[] = "f.mime IN $images";
if ($kind === 'doc') $where[] = "f.mime NOT IN $images";
if ($q !== '') { $where[] = 'f.original_name LIKE ?'; $args[] = '%' . $q . '%'; }

$st = pt_db()->prepare("SELECT f.*, m.created_at, m.sender_id, u.full_name, c.type AS conv_type, c.title AS conv_title, c.employee_user_id,
                               e.full_name AS employee_name
                        FROM pt_chat_files f
                        JOIN pt_chat_messages m ON m.id = f.message_id
                        JOIN pt_chat_conversations c ON c.id = f.conversation_id
                        JOIN pt_users u ON u.id = m.sender_id
                        LEFT JOIN pt_users e ON e.id = c.employee_user_id
                        " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
                        ORDER BY f.id DESC LIMIT 300");
$st->execute($args);
$files = $st->fetchAll();

$convName = function (array $f) use ($admin): string {
    if ($f['conv_type'] === 'group') return (string)$f['conv_title'];
    return $admin ? (string)$f['employee_name'] : t('administration');
};

page_head(t('files'), $user);
?>
  <div class="page-head">
    <h1><?= e(t('files')) ?> <span class="muted">(<?= count($files) ?>)</span></h1>
  </div>
  <p class="muted"><?= e(t($admin ? 'files_intro_admin' : 'files_intro')) ?></p>

  <form class="filters" method="get">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('files_search')) ?>">
    <select name="tipo" onchange="this.form.submit()">
      <option value=""><?= e(t('all')) ?></option>
      <option value="img" <?= $kind === 'img' ? 'selected' : '' ?>><?= e(t('files_images')) ?></option>
      <option value="doc" <?= $kind === 'doc' ? 'selected' : '' ?>><?= e(t('files_docs')) ?></option>
    </select>
  </form>

  <?php if (!$files): ?>
    <p class="muted"><?= e(t('files_none')) ?></p>
  <?php else: ?>
    <div class="file-grid">
      <?php foreach ($files as $f):
        $ext = strtolower(pathinfo($f['stored_name'], PATHINFO_EXTENSION));
        $img = chat_is_image($ext); ?>
        <a class="file-card" href="arquivo.php?id=<?= (int)$f['id'] ?><?= $img ? '' : '&dl=1' ?>" <?= $img ? 'target="_blank"' : '' ?>>
          <span class="file-thumb">
            <?php if ($img): ?><img src="arquivo.php?id=<?= (int)$f['id'] ?>" alt="" loading="lazy">
            <?php else: ?><span class="file-ext"><?= e(strtoupper($ext)) ?></span><?php endif; ?>
          </span>
          <span class="file-meta">
            <strong title="<?= e($f['original_name']) ?>"><?= e($f['original_name']) ?></strong>
            <span class="muted"><?= e(fmt_bytes((int)$f['size_bytes'])) ?> · <?= e(date('d/m/Y', strtotime($f['created_at']))) ?></span>
            <span class="muted"><?= e(explode(' ', $f['full_name'])[0]) ?> → <?= e($convName($f)) ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php page_foot();
