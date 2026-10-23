<?php
/* Conversation list. Employees: their thread with the administration + their groups.
   Admins: every thread with messages + every group, and can start new ones. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/chat.php';

$user  = require_user();
$admin = $user['role'] === 'admin';
$list  = chat_list($user);
$hasDirect = false;
foreach ($list as $c) if ($c['type'] === 'direct') $hasDirect = true;

$people = $admin ? pt_db()->query("SELECT id, full_name FROM pt_users WHERE is_active = 1 AND role = 'employee' ORDER BY full_name")->fetchAll() : [];

page_head(t('messages'), $user);
?>
  <div class="page-head">
    <h1><?= e(t('messages')) ?></h1>
    <?php if ($admin): ?>
      <a href="conversa.php?novo_grupo=1" class="btn">+ <?= e(t('new_group')) ?></a>
    <?php endif; ?>
  </div>

  <?php if ($admin): ?>
    <form class="filters" method="get" action="conversa.php">
      <select name="user" required>
        <option value=""><?= e(t('chat_pick_person')) ?></option>
        <?php foreach ($people as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['full_name']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-primary"><?= e(t('chat_open')) ?></button>
    </form>
  <?php elseif (!$hasDirect): ?>
    <a href="conversa.php?admin=1" class="btn btn-primary"><?= e(t('chat_talk_admin')) ?></a>
  <?php endif; ?>

  <?php if (!$list): ?>
    <p class="muted"><?= e(t('chat_none')) ?></p>
  <?php else: ?>
    <ul class="conv-list">
      <?php foreach ($list as $c): $l = $c['last']; ?>
        <li>
          <a href="conversa.php?id=<?= (int)$c['id'] ?>" class="<?= $c['unread'] ? 'has-unread' : '' ?>">
            <span class="conv-avatar <?= $c['type'] ?>"><?= e(mb_strtoupper(mb_substr($c['title'], 0, 1))) ?></span>
            <span class="conv-main">
              <span class="conv-title"><?= e($c['title']) ?><?php if ($c['type'] === 'group'): ?> <span class="tag"><?= e(t('group')) ?></span><?php endif; ?></span>
              <span class="conv-preview muted"><?php
                if ($l) {
                    $txt = $l['body'] !== '' ? $l['body'] : '📎 ' . t('attachment');
                    echo e(explode(' ', $l['full_name'])[0] . ': ' . mb_strimwidth(str_replace("\n", ' ', $txt), 0, 70, '…'));
                } else {
                    echo e(t('chat_empty'));
                } ?></span>
            </span>
            <span class="conv-side">
              <?php if ($l): ?><span class="muted small"><?= e(substr($l['created_at'], 0, 10) === date('Y-m-d') ? date('H:i', strtotime($l['created_at'])) : date('d/m', strtotime($l['created_at']))) ?></span><?php endif; ?>
              <?php if ($c['unread']): ?><span class="badge"><?= (int)$c['unread'] ?></span><?php endif; ?>
            </span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
<?php page_foot();
