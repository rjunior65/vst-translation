<?php
/* Admin: work locations (geofence) + GPS rules. */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../inc/punch.php';

$me = require_admin();

/* Accepts "6.80, -58.15" or a Google Maps link (pin !3d..!4d.. or @lat,lng). */
function parse_coords(string $s): ?array {
    foreach (['/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/', '/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/',
              '/(-?\d{1,3}\.\d+)\s*[,;\s]\s*(-?\d{1,3}\.\d+)/'] as $re) {
        if (preg_match($re, $s, $m)) {
            $lat = (float)$m[1];
            $lng = (float)$m[2];
            if (abs($lat) <= 90 && abs($lng) <= 180) return [$lat, $lng];
        }
    }
    return null;
}

$id = (int)($_GET['id'] ?? 0);
$loc = null;
if ($id) {
    $st = pt_db()->prepare("SELECT * FROM pt_locations WHERE id = ?");
    $st->execute([$id]);
    $loc = $st->fetch() ?: null;
}
$form = $loc ?: ['name' => '', 'address' => '', 'lat' => '', 'lng' => '', 'radius_m' => 150, 'is_active' => 1];
$coordsText = $loc ? $loc['lat'] . ', ' . $loc['lng'] : '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (($_POST['action'] ?? '') === 'rules') {
        $old = ['outside_policy' => setting('outside_policy', 'flag'), 'min_gps_accuracy' => setting('min_gps_accuracy', '100')];
        $new = [
            'outside_policy'   => ($_POST['outside_policy'] ?? '') === 'block' ? 'block' : 'flag',
            'min_gps_accuracy' => (string)max(0, min(1000, (int)($_POST['min_gps_accuracy'] ?? 100))),
        ];
        $up = pt_db()->prepare("INSERT INTO pt_settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)");
        foreach ($new as $k => $v) $up->execute([$k, $v]);
        audit('update_settings', 'setting', null, $old, $new);
        flash('ok', t('rules_saved'));
        redirect('locais.php');
    }

    $form['name']      = trim((string)($_POST['name'] ?? ''));
    $form['address']   = trim((string)($_POST['address'] ?? ''));
    $form['radius_m']  = (int)($_POST['radius_m'] ?? 0);
    $form['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $coordsText        = trim((string)($_POST['coords'] ?? ''));
    $coords            = parse_coords($coordsText);

    if ($form['name'] === '' || mb_strlen($form['name']) > 120) $errors[] = t('err_name');
    if (!$coords) $errors[] = t('err_coords');
    if ($form['radius_m'] < 20 || $form['radius_m'] > 5000) $errors[] = t('err_radius');

    if (!$errors) {
        [$form['lat'], $form['lng']] = $coords;
        $vals = [$form['name'], mb_substr($form['address'], 0, 255), $form['lat'], $form['lng'], $form['radius_m'], $form['is_active']];
        if ($loc) {
            pt_db()->prepare("UPDATE pt_locations SET name = ?, address = ?, lat = ?, lng = ?, radius_m = ?, is_active = ? WHERE id = ?")
                ->execute(array_merge($vals, [$loc['id']]));
            audit('update_location', 'location', (int)$loc['id'], $loc, array_merge($loc, $form));
        } else {
            pt_db()->prepare("INSERT INTO pt_locations (name, address, lat, lng, radius_m, is_active) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute($vals);
            audit('create_location', 'location', (int)pt_db()->lastInsertId(), null, $form);
        }
        flash('ok', t('loc_saved'));
        redirect('locais.php');
    }
}

$all = pt_db()->query("SELECT l.*, (SELECT COUNT(*) FROM pt_users u WHERE u.location_id = l.id) AS people
                       FROM pt_locations l ORDER BY l.is_active DESC, l.name")->fetchAll();
$showForm = $loc || isset($_GET['new']) || $errors;

page_head(t('locations'), $me);
?>
  <div class="page-head">
    <h1><?= e(t('locations_title')) ?></h1>
    <?php if (!$showForm): ?><a href="locais.php?new=1" class="btn btn-primary">+ <?= e(t('new_location')) ?></a><?php endif; ?>
  </div>

  <?php if ($showForm): ?>
    <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $err) echo e($err) . '<br>'; ?></div><?php endif; ?>
    <form class="form" method="post" autocomplete="off">
      <h2 class="form-title"><?= e($loc ? t('edit_location') : t('new_location')) ?></h2>
      <?= csrf_field() ?>
      <label for="name"><?= e(t('loc_name')) ?></label>
      <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" maxlength="120" required placeholder="Escritório VST">
      <label for="address"><?= e(t('loc_address')) ?></label>
      <input type="text" id="address" name="address" value="<?= e($form['address']) ?>" maxlength="255">
      <label for="coords"><?= e(t('loc_coords')) ?></label>
      <input type="text" id="coords" name="coords" value="<?= e($coordsText) ?>" required placeholder="6.8045, -58.1553">
      <span class="hint"><?= e(t('loc_coords_hint')) ?></span>
      <div class="form-actions">
        <button type="button" class="btn btn-sm" id="use-here"><?= e(t('loc_use_here')) ?></button>
        <a class="btn btn-sm" id="view-map" target="_blank" rel="noopener" href="#"><?= e(t('loc_view_map')) ?></a>
      </div>
      <label for="radius_m"><?= e(t('loc_radius')) ?></label>
      <input type="number" id="radius_m" name="radius_m" value="<?= (int)$form['radius_m'] ?>" min="20" max="5000" step="10" required>
      <label class="check"><input type="checkbox" name="is_active" value="1" <?= $form['is_active'] ? 'checked' : '' ?>> <?= e(t('active')) ?></label>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button>
        <a href="locais.php" class="btn"><?= e(t('cancel')) ?></a>
      </div>
    </form>
    <script>
    (function () {
      var input = document.getElementById('coords'), map = document.getElementById('view-map');
      function upd() {
        var m = input.value.match(/(-?\d{1,3}\.\d+)\s*[,;\s]\s*(-?\d{1,3}\.\d+)/);
        map.href = m ? 'https://www.google.com/maps?q=' + m[1] + ',' + m[2] : 'https://www.google.com/maps';
      }
      input.addEventListener('input', upd); upd();
      document.getElementById('use-here').addEventListener('click', function (ev) {
        var b = ev.target; b.disabled = true;
        navigator.geolocation.getCurrentPosition(function (p) {
          input.value = p.coords.latitude.toFixed(7) + ', ' + p.coords.longitude.toFixed(7);
          upd(); b.disabled = false;
        }, function (e) { alert(e.message); b.disabled = false; }, { enableHighAccuracy: true, timeout: 20000 });
      });
    })();
    </script>
  <?php else: ?>
    <?php if (!$all): ?>
      <p class="alert alert-info"><?= e(t('locations_none')) ?></p>
    <?php else: ?>
      <div class="table-scroll">
        <table class="grid">
          <thead><tr><th><?= e(t('loc_name')) ?></th><th><?= e(t('loc_address')) ?></th><th><?= e(t('loc_radius')) ?></th>
            <th><?= e(t('users')) ?></th><th></th></tr></thead>
          <tbody>
          <?php foreach ($all as $l): ?>
            <tr>
              <td><span class="dot <?= $l['is_active'] ? 'on' : 'off' ?>"></span><?= e($l['name']) ?></td>
              <td class="muted"><?= e($l['address']) ?></td>
              <td><?= (int)$l['radius_m'] ?> m</td>
              <td><?= (int)$l['people'] ?></td>
              <td class="row-actions">
                <a class="btn btn-sm" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= e($l['lat'] . ',' . $l['lng']) ?>"><?= e(t('loc_view_map')) ?></a>
                <a class="btn btn-sm" href="locais.php?id=<?= (int)$l['id'] ?>"><?= e(t('edit')) ?></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <form class="form rules-form" method="post">
      <h2 class="form-title"><?= e(t('gps_rules')) ?></h2>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="rules">
      <label for="outside_policy"><?= e(t('policy')) ?></label>
      <select id="outside_policy" name="outside_policy">
        <option value="flag" <?= setting('outside_policy', 'flag') === 'flag' ? 'selected' : '' ?>><?= e(t('policy_flag')) ?></option>
        <option value="block" <?= setting('outside_policy', 'flag') === 'block' ? 'selected' : '' ?>><?= e(t('policy_block')) ?></option>
      </select>
      <label for="min_gps_accuracy"><?= e(t('max_accuracy')) ?></label>
      <input type="number" id="min_gps_accuracy" name="min_gps_accuracy" value="<?= (int)setting('min_gps_accuracy', '100') ?>" min="0" max="1000" step="10">
      <span class="hint"><?= e(t('max_accuracy_hint')) ?></span>
      <div class="form-actions"><button type="submit" class="btn btn-primary"><?= e(t('save')) ?></button></div>
    </form>
  <?php endif; ?>
<?php page_foot();
