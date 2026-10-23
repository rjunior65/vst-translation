<?php
/* Admin: punch list with filters + map, and manual add / correct / void.
   Nothing is ever deleted: a correction voids the original row and inserts a
   new "manual" row; both stay, and the reason goes to the audit log. */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/punch.php';

$me = require_admin();

$users = pt_db()->query("SELECT id, full_name, is_active FROM pt_users ORDER BY is_active DESC, full_name")->fetchAll();
$userIds = array_map('intval', array_column($users, 'id'));

$date = fn($s) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$s) ? (string)$s : null;
$from   = $date($_GET['de'] ?? '') ?? date('Y-m-d');
$to     = $date($_GET['ate'] ?? '') ?? $from;
if ($to < $from) [$from, $to] = [$to, $from];
$uid    = in_array((int)($_GET['usuario'] ?? 0), $userIds, true) ? (int)$_GET['usuario'] : 0;
$status = in_array($_GET['status'] ?? '', ['flagged', 'manual', 'void'], true) ? $_GET['status'] : '';
$back   = 'pontos.php?' . http_build_query(['de' => $from, 'ate' => $to, 'usuario' => $uid ?: null, 'status' => $status ?: null]);

/* "2026-10-23T08:05" → "2026-10-23 08:05:00", or null if invalid / in the future. */
function parse_dt(string $s): ?string {
    $ts = strtotime(str_replace('T', ' ', $s));
    if ($ts === false || $ts > time() + 60) return null;
    return date('Y-m-d H:i:00', $ts);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $reason = trim((string)($_POST['reason'] ?? ''));
    $type   = in_array($_POST['type'] ?? '', punch_types(), true) ? $_POST['type'] : null;
    $when   = parse_dt((string)($_POST['when'] ?? ''));
    $err    = in_array($action, ['add', 'edit', 'void'], true) ? null : 'bad action';

    $orig = null;
    if (in_array($action, ['edit', 'void'], true)) {
        $st = pt_db()->prepare("SELECT * FROM pt_punches WHERE id = ? AND is_void = 0");
        $st->execute([(int)($_POST['punch_id'] ?? 0)]);
        $orig = $st->fetch() ?: null;
        if (!$orig) $err = t('err_punch_not_found');
    }
    if ($reason === '') $err = t('err_reason');
    if ($action !== 'void' && (!$type || !$when)) $err = t('err_when');
    $target = $action === 'add' ? (int)($_POST['user_id'] ?? 0) : (int)($orig['user_id'] ?? 0);
    if ($action === 'add' && !in_array($target, $userIds, true)) $err = t('not_found');

    if ($err) {
        flash('error', $err);
        redirect($back);
    }

    $pdo = pt_db();
    $pdo->beginTransaction();
    $newId = null;
    if ($action !== 'void') {
        $pdo->prepare("INSERT INTO pt_punches (user_id, type, punched_at, status, source, ip, user_agent)
                       VALUES (?, ?, ?, 'manual', 'admin', ?, ?)")
            ->execute([$target, $type, $when, client_ip(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
        $newId = (int)$pdo->lastInsertId();
    }
    if ($orig) {
        $pdo->prepare("UPDATE pt_punches SET is_void = 1, voided_by = ? WHERE id = ?")->execute([$newId, $orig['id']]);
    }
    $pdo->commit();

    $newRow = $newId ? ['id' => $newId, 'user_id' => $target, 'type' => $type, 'punched_at' => $when, 'status' => 'manual'] : null;
    audit($action . '_punch', 'punch', $newId ?? (int)$orig['id'], $orig, $newRow, $reason);
    flash('ok', t('punch_saved_' . $action));
    redirect($back);
}

// ---- List ----
$where = ['p.punched_at >= ?', 'p.punched_at < ? + INTERVAL 1 DAY'];
$args  = [$from, $to];
if ($uid)                   { $where[] = 'p.user_id = ?'; $args[] = $uid; }
if ($status === 'flagged')  $where[] = "p.status IN ('outside','no_gps') AND p.is_void = 0";
if ($status === 'manual')   $where[] = "p.status = 'manual' AND p.is_void = 0";
if ($status === '')         $where[] = 'p.is_void = 0';
// status = void → show everything, voided rows included
$st = pt_db()->prepare("SELECT p.*, u.full_name, u.photo, u.employee_id FROM pt_punches p JOIN pt_users u ON u.id = p.user_id
                        WHERE " . implode(' AND ', $where) . " ORDER BY p.punched_at DESC, p.id DESC LIMIT 1000");
$st->execute($args);
$punches = $st->fetchAll();

$mapPoints = [];
foreach ($punches as $p) {
    if ($p['lat'] !== null && !$p['is_void']) {
        $mapPoints[] = ['lat' => (float)$p['lat'], 'lng' => (float)$p['lng'], 'status' => $p['status'],
                        'label' => $p['full_name'] . ' · ' . t('type_' . $p['type']) . ' · ' . date('d/m H:i', strtotime($p['punched_at']))];
    }
}
$mapLocs = array_map(fn($l) => ['lat' => (float)$l['lat'], 'lng' => (float)$l['lng'], 'r' => (int)$l['radius_m'], 'name' => $l['name']],
                     active_locations());

page_head(t('punches'), $me);
?>
  <div class="page-head">
    <h1><?= e(t('punches')) ?> <span class="muted">(<?= count($punches) ?>)</span></h1>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('add-form').toggleAttribute('hidden')">+ <?= e(t('add_punch')) ?></button>
  </div>

  <form class="form form-wide" id="add-form" method="post" hidden>
    <h2 class="form-title"><?= e(t('add_punch')) ?></h2>
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="grid-3">
      <div><label><?= e(t('full_name')) ?></label>
        <select name="user_id" required>
          <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $uid === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?>
        </select></div>
      <div><label><?= e(t('type')) ?></label>
        <select name="type"><?php foreach (punch_types() as $pt): ?><option value="<?= $pt ?>"><?= e(t('type_' . $pt)) ?></option><?php endforeach; ?></select></div>
      <div><label><?= e(t('date_time')) ?></label>
        <input type="datetime-local" name="when" required max="<?= e(date('Y-m-d\TH:i')) ?>" value="<?= e($from . 'T' . date('H:i')) ?>"></div>
    </div>
    <label><?= e(t('reason')) ?></label>
    <input type="text" name="reason" maxlength="255" required placeholder="<?= e(t('reason_example')) ?>">
    <div class="form-actions"><button class="btn btn-primary"><?= e(t('save')) ?></button></div>
  </form>

  <form class="filters" method="get">
    <input type="date" name="de" value="<?= e($from) ?>" max="<?= e(date('Y-m-d')) ?>" aria-label="<?= e(t('from')) ?>">
    <input type="date" name="ate" value="<?= e($to) ?>" max="<?= e(date('Y-m-d')) ?>" aria-label="<?= e(t('to')) ?>">
    <select name="usuario">
      <option value=""><?= e(t('all')) ?></option>
      <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $uid === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?>
    </select>
    <select name="status">
      <option value=""><?= e(t('f_valid')) ?></option>
      <option value="flagged" <?= $status === 'flagged' ? 'selected' : '' ?>><?= e(t('f_flagged')) ?></option>
      <option value="manual" <?= $status === 'manual' ? 'selected' : '' ?>><?= e(t('f_manual')) ?></option>
      <option value="void" <?= $status === 'void' ? 'selected' : '' ?>><?= e(t('f_with_void')) ?></option>
    </select>
    <button class="btn"><?= e(t('filter')) ?></button>
  </form>

  <?php if ($mapPoints): ?>
    <div id="map" class="map"></div>
  <?php endif; ?>

  <div class="table-scroll">
    <table class="grid">
      <thead><tr><th><?= e(t('date_time')) ?></th><th><?= e(t('full_name')) ?></th><th><?= e(t('type')) ?></th>
        <th><?= e(t('status')) ?></th><th><?= e(t('distance')) ?></th><th></th></tr></thead>
      <tbody>
      <?php if (!$punches): ?><tr><td colspan="6" class="muted"><?= e(t('no_punches')) ?></td></tr><?php endif; ?>
      <?php foreach ($punches as $p): $void = (int)$p['is_void'] === 1; ?>
        <tr class="<?= $void ? 'voided' : '' ?>">
          <td class="nowrap"><?= e(date('d/m H:i', strtotime($p['punched_at']))) ?></td>
          <td><span class="person"><?= avatar_html(['id' => $p['user_id']] + $p, 'xs') ?><?= e($p['full_name']) ?></span></td>
          <td><?= e(t('type_' . $p['type'])) ?></td>
          <td class="tags">
            <?php if ($void): ?><span class="tag"><?= e(t('st_void')) ?></span>
            <?php elseif ($p['status'] !== 'ok'): ?><span class="tag <?= $p['status'] === 'manual' ? '' : 'warn' ?>"><?= e(t('st_' . $p['status'])) ?></span><?php endif; ?>
            <?php if ($p['source'] === 'app'): ?><span class="tag">app</span><?php endif; ?>
          </td>
          <td class="nowrap">
            <?php if ($p['lat'] !== null): ?>
              <a href="https://www.google.com/maps?q=<?= e($p['lat'] . ',' . $p['lng']) ?>" target="_blank" rel="noopener"><?= e(fmt_distance($p['distance_m'] === null ? null : (int)$p['distance_m'])) ?></a>
              <?php if ($p['accuracy_m'] !== null): ?><span class="muted small">±<?= (int)$p['accuracy_m'] ?> m</span><?php endif; ?>
            <?php else: ?>—<?php endif; ?>
            <?php if ($p['photo_path']): ?><a href="../foto-ponto.php?id=<?= (int)$p['id'] ?>" target="_blank"><img class="punch-thumb" src="../foto-ponto.php?id=<?= (int)$p['id'] ?>" alt="" loading="lazy"></a><?php endif; ?>
          </td>
          <td>
            <?php if (!$void): ?>
            <details class="row-edit">
              <summary class="btn btn-sm"><?= e(t('fix')) ?></summary>
              <div class="row-edit-box">
                <form method="post">
                  <?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="punch_id" value="<?= (int)$p['id'] ?>">
                  <select name="type"><?php foreach (punch_types() as $pt): ?><option value="<?= $pt ?>" <?= $pt === $p['type'] ? 'selected' : '' ?>><?= e(t('type_' . $pt)) ?></option><?php endforeach; ?></select>
                  <input type="datetime-local" name="when" required value="<?= e(date('Y-m-d\TH:i', strtotime($p['punched_at']))) ?>" max="<?= e(date('Y-m-d\TH:i')) ?>">
                  <input type="text" name="reason" required maxlength="255" placeholder="<?= e(t('reason')) ?>">
                  <button class="btn btn-sm btn-primary"><?= e(t('save')) ?></button>
                </form>
                <form method="post">
                  <?= csrf_field() ?><input type="hidden" name="action" value="void"><input type="hidden" name="punch_id" value="<?= (int)$p['id'] ?>">
                  <input type="text" name="reason" required maxlength="255" placeholder="<?= e(t('reason')) ?>">
                  <button class="btn btn-sm btn-danger"><?= e(t('void')) ?></button>
                </form>
              </div>
            </details>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($mapPoints): ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
  (function () {
    var pts = <?= json_encode($mapPoints, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var locs = <?= json_encode($mapLocs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    if (!window.L) return;
    var map = L.map('map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    var bounds = [];
    locs.forEach(function (l) {
      L.circle([l.lat, l.lng], { radius: l.r, color: '#123b6d', weight: 1, fillOpacity: .08 }).addTo(map).bindTooltip(l.name);
      bounds.push([l.lat, l.lng]);
    });
    pts.forEach(function (p) {
      var c = p.status === 'ok' ? '#188a4a' : '#c62828';
      L.circleMarker([p.lat, p.lng], { radius: 7, color: '#fff', weight: 2, fillColor: c, fillOpacity: .95 }).addTo(map).bindPopup(p.label);
      bounds.push([p.lat, p.lng]);
    });
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 17 });
  })();
  </script>
  <?php endif; ?>
<?php page_foot();
