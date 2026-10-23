<?php
/* One conversation: messages + composer. Also creates threads/groups.
   ?admin=1      employee opens their thread with the administration
   ?user=ID      admin opens (or starts) the thread with an employee
   ?novo_grupo=1 admin creates a group
   ?id=ID        the conversation itself */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/layout.php';
require_once __DIR__ . '/inc/chat.php';

$user  = require_user();
$admin = $user['role'] === 'admin';

if (isset($_GET['admin']) && !$admin) {
    redirect('conversa.php?id=' . chat_direct_for((int)$user['id'], (int)$user['id']));
}
if (isset($_GET['user']) && $admin) {
    $st = pt_db()->prepare("SELECT id FROM pt_users WHERE id = ? AND role = 'employee'");
    $st->execute([(int)$_GET['user']]);
    if (!$st->fetchColumn()) { flash('error', t('not_found')); redirect('mensagens.php'); }
    redirect('conversa.php?id=' . chat_direct_for((int)$_GET['user'], (int)$user['id']));
}

$people = $admin ? pt_db()->query("SELECT id, full_name FROM pt_users WHERE is_active = 1 AND role = 'employee' ORDER BY full_name")->fetchAll() : [];
$peopleIds = array_map('intval', array_column($people, 'id'));
$pickMembers = fn() => array_values(array_intersect($peopleIds, array_map('intval', (array)($_POST['members'] ?? []))));

// ---- Create group (admin) ----
if (isset($_GET['novo_grupo']) && $admin) {
    $title = '';
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_verify();
        $title = trim((string)($_POST['title'] ?? ''));
        $members = $pickMembers();
        if ($title === '' || mb_strlen($title) > 120) $error = t('err_name');
        elseif (!$members) $error = t('chat_err_members');
        else {
            pt_db()->prepare("INSERT INTO pt_chat_conversations (type, title, created_by) VALUES ('group', ?, ?)")->execute([$title, $user['id']]);
            $cid = (int)pt_db()->lastInsertId();
            $ins = pt_db()->prepare("INSERT INTO pt_chat_members (conversation_id, user_id) VALUES (?, ?)");
            foreach ($members as $m) $ins->execute([$cid, $m]);
            audit('create_group', 'chat', $cid, null, ['title' => $title, 'members' => $members]);
            redirect('conversa.php?id=' . $cid);
        }
    }
    page_head(t('new_group'), $user);
    ?>
      <p><a href="mensagens.php">← <?= e(t('back')) ?></a></p>
      <h1><?= e(t('new_group')) ?></h1>
      <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
      <form class="form" method="post">
        <?= csrf_field() ?>
        <label for="title"><?= e(t('group_name')) ?></label>
        <input type="text" id="title" name="title" value="<?= e($title) ?>" maxlength="120" required placeholder="Equipe do escritório">
        <label><?= e(t('members')) ?></label>
        <div class="member-picks">
          <?php foreach ($people as $p): ?>
            <label class="check"><input type="checkbox" name="members[]" value="<?= (int)$p['id'] ?>"
              <?= in_array((int)$p['id'], array_map('intval', (array)($_POST['members'] ?? [])), true) ? 'checked' : '' ?>> <?= e($p['full_name']) ?></label>
          <?php endforeach; ?>
        </div>
        <span class="hint"><?= e(t('group_admins_hint')) ?></span>
        <div class="form-actions"><button class="btn btn-primary"><?= e(t('save')) ?></button></div>
      </form>
    <?php
    page_foot();
    exit;
}

$conv = chat_get((int)($_GET['id'] ?? 0), $user);
if (!$conv) { flash('error', t('chat_not_found')); redirect('mensagens.php'); }
$cid = (int)$conv['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? 'send';
    if ($action === 'members' && $admin && $conv['type'] === 'group') {
        $title = trim((string)($_POST['title'] ?? ''));
        $members = $pickMembers();
        if ($title !== '' && mb_strlen($title) <= 120 && $members) {
            $old = ['title' => $conv['title'], 'members' => array_map('intval', array_column(chat_members($cid), 'id'))];
            pt_db()->prepare("UPDATE pt_chat_conversations SET title = ? WHERE id = ?")->execute([$title, $cid]);
            pt_db()->prepare("DELETE FROM pt_chat_members WHERE conversation_id = ?")->execute([$cid]);
            $ins = pt_db()->prepare("INSERT INTO pt_chat_members (conversation_id, user_id) VALUES (?, ?)");
            foreach ($members as $m) $ins->execute([$cid, $m]);
            audit('update_group', 'chat', $cid, $old, ['title' => $title, 'members' => $members]);
            flash('ok', t('user_saved'));
        } else {
            flash('error', t('chat_err_members'));
        }
        redirect('conversa.php?id=' . $cid);
    }
    // Plain form post (no JavaScript): send and come back.
    [$id, $err] = chat_send($conv, $user, (string)($_POST['body'] ?? ''), chat_uploaded_files());
    if ($err) flash('error', t($err, ['mb' => PT_CHAT_MAX_BYTES / 1048576, 'n' => PT_CHAT_MAX_FILES]));
    redirect('conversa.php?id=' . $cid . '#end');
}

$msgs = chat_messages($cid);
$lastId = $msgs ? (int)end($msgs)['id'] : 0;
chat_mark_read($cid, (int)$user['id'], $lastId);
$members = $conv['type'] === 'group' ? chat_members($cid) : [];
$title = chat_title($conv, $user);
$accept = '.' . implode(',.', array_keys(chat_allowed_types())) . ',image/*';

page_head($title, $user, 'chat-page');
?>
  <div class="chat-head">
    <a href="mensagens.php" class="chat-back" aria-label="<?= e(t('back')) ?>">←</a>
    <div>
      <h1><?= e($title) ?></h1>
      <?php if ($conv['type'] === 'group'): ?>
        <p class="muted small"><?= e(implode(', ', array_column($members, 'full_name'))) ?> · <?= e(t('administration')) ?></p>
      <?php elseif ($admin): ?>
        <p class="muted small"><?= e(t('chat_direct_hint_admin')) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($admin && $conv['type'] === 'group'): $ids = array_map('intval', array_column($members, 'id')); ?>
    <details class="group-edit">
      <summary class="btn btn-sm"><?= e(t('edit_group')) ?></summary>
      <form class="form" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="members">
        <label><?= e(t('group_name')) ?></label>
        <input type="text" name="title" value="<?= e($conv['title']) ?>" maxlength="120" required>
        <label><?= e(t('members')) ?></label>
        <div class="member-picks">
          <?php foreach ($people as $p): ?>
            <label class="check"><input type="checkbox" name="members[]" value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $ids, true) ? 'checked' : '' ?>> <?= e($p['full_name']) ?></label>
          <?php endforeach; ?>
        </div>
        <div class="form-actions"><button class="btn btn-primary"><?= e(t('save')) ?></button></div>
      </form>
    </details>
  <?php endif; ?>

  <div class="chat-box" id="chat">
    <?php if (!$msgs): ?><p class="chat-empty muted" id="chat-empty"><?= e(t('chat_start')) ?></p><?php endif; ?>
    <?php $day = null;
    foreach ($msgs as $m) {
        $d = substr($m['created_at'], 0, 10);
        if ($d !== $day) { echo '<div class="day-sep" data-day="' . e($d) . '"><span>' . e(chat_day_label($d)) . '</span></div>'; $day = $d; }
        echo chat_render_message($m, $user);
    } ?>
    <span id="end"></span>
  </div>

  <form class="composer" id="composer" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="send">
    <div class="composer-files" id="picked" hidden></div>
    <p class="alert alert-error" id="chat-error" hidden></p>
    <div class="composer-row">
      <label class="attach" title="<?= e(t('attach')) ?>">
        📎<input type="file" name="files[]" id="files" multiple accept="<?= e($accept) ?>">
      </label>
      <textarea name="body" id="body" rows="1" maxlength="<?= PT_CHAT_MAX_BODY ?>" placeholder="<?= e(t('chat_placeholder')) ?>"></textarea>
      <button class="btn btn-primary send" id="send"><?= e(t('send')) ?></button>
    </div>
    <p class="hint"><?= e(t('chat_files_hint', ['mb' => PT_CHAT_MAX_BYTES / 1048576, 'n' => PT_CHAT_MAX_FILES])) ?></p>
  </form>

  <script>window.CHAT = <?= json_encode([
      'id' => $cid, 'last' => $lastId, 'csrf' => csrf_token(), 'api' => 'api/chat.php',
      'maxBytes' => PT_CHAT_MAX_BYTES, 'maxFiles' => PT_CHAT_MAX_FILES,
      'i18n' => ['size' => t('chat_err_size', ['mb' => PT_CHAT_MAX_BYTES / 1048576]), 'count' => t('chat_err_count', ['n' => PT_CHAT_MAX_FILES]),
                 'failed' => t('err_server'), 'sending' => t('punch_sending'), 'send' => t('send'),
                 'today' => t('today'), 'yesterday' => t('yesterday')],
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
  <script src="assets/chat.js"></script>
<?php page_foot();
