<?php
/* Admin: CVs sent through the website's "Work With Us" form (same table and
   files as the site admin: job_applications + /home/<user>/vst-private/cv/). */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/layout.php';
require_once __DIR__ . '/../../includes/careers-config.php';

$me = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $st = pt_db()->prepare("SELECT * FROM job_applications WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row && $action === 'toggle_read') {
        pt_db()->prepare("UPDATE job_applications SET is_read = 1 - is_read WHERE id = ?")->execute([$id]);
    } elseif ($row && $action === 'delete') {
        pt_db()->prepare("DELETE FROM job_applications WHERE id = ?")->execute([$id]);
        $file = basename((string)$row['cv_file']);
        if ($file !== '' && is_file(UPLOAD_DIR_CV . $file)) @unlink(UPLOAD_DIR_CV . $file);
        audit('delete_cv', 'cv', $id, ['name' => $row['name'], 'email' => $row['email'], 'job' => $row['job_title']], null);
        flash('ok', t('cv_deleted'));
    }
    redirect('curriculos.php?' . http_build_query(array_filter(['filtro' => $_GET['filtro'] ?? null, 'vaga' => $_GET['vaga'] ?? null])));
}

$filter = ($_GET['filtro'] ?? '') === 'novos' ? 'novos' : '';
$job    = trim((string)($_GET['vaga'] ?? ''));
$where = [];
$args = [];
if ($filter === 'novos') $where[] = 'is_read = 0';
if ($job !== '') { $where[] = 'job_title = ?'; $args[] = $job; }
$st = pt_db()->prepare("SELECT * FROM job_applications" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY created_at DESC, id DESC");
$st->execute($args);
$rows = $st->fetchAll();
$jobs = pt_db()->query("SELECT DISTINCT job_title FROM job_applications WHERE job_title <> '' ORDER BY job_title")->fetchAll(PDO::FETCH_COLUMN);
$unread = (int)pt_db()->query("SELECT COUNT(*) FROM job_applications WHERE is_read = 0")->fetchColumn();

page_head(t('cvs'), $me);
?>
  <div class="page-head">
    <h1><?= e(t('cvs')) ?> <span class="muted">(<?= count($rows) ?>)</span></h1>
    <?php if ($unread): ?><span class="tag admin"><?= e(t('cv_unread', ['n' => $unread])) ?></span><?php endif; ?>
  </div>
  <p class="alert alert-info"><?= e(t('cv_where', ['email' => CAREERS_NOTIFY_EMAIL ?: '—'])) ?></p>

  <form class="filters" method="get">
    <select name="filtro" onchange="this.form.submit()">
      <option value=""><?= e(t('all')) ?></option>
      <option value="novos" <?= $filter ? 'selected' : '' ?>><?= e(t('cv_only_new')) ?></option>
    </select>
    <select name="vaga" onchange="this.form.submit()">
      <option value=""><?= e(t('cv_all_jobs')) ?></option>
      <?php foreach ($jobs as $j): ?><option <?= $job === $j ? 'selected' : '' ?>><?= e($j) ?></option><?php endforeach; ?>
    </select>
  </form>

  <?php if (!$rows): ?>
    <p class="muted"><?= e(t('cv_none')) ?></p>
  <?php else: ?>
    <div class="table-scroll">
      <table class="grid">
        <thead><tr><th><?= e(t('date')) ?></th><th><?= e(t('full_name')) ?></th><th><?= e(t('cv_job')) ?></th><th><?= e(t('cv_contact')) ?></th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr style="<?= $r['is_read'] ? '' : 'font-weight:700' ?>">
            <td class="nowrap"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?><?php if (!$r['is_read']): ?> <span class="tag admin"><?= e(t('cv_new')) ?></span><?php endif; ?></td>
            <td><?= e($r['name']) ?>
              <?php if (trim((string)$r['message']) !== ''): ?>
                <details><summary class="muted small"><?= e(t('cv_message')) ?></summary><p class="small" style="font-weight:400;white-space:pre-line"><?= e($r['message']) ?></p></details>
              <?php endif; ?></td>
            <td><?= e($r['job_title'] ?: '—') ?></td>
            <td class="small"><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?php if ($r['phone']): ?><br><?= e($r['phone']) ?><?php endif; ?></td>
            <td class="row-actions">
              <a class="btn btn-sm btn-blue" href="curriculo.php?id=<?= (int)$r['id'] ?>"><?= icon('download') ?> CV</a>
              <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="toggle_read">
                <button class="btn btn-sm"><?= e(t($r['is_read'] ? 'cv_mark_unread' : 'cv_mark_read')) ?></button></form>
              <form method="post" class="inline" onsubmit="return confirm(<?= e(json_encode(t('cv_confirm_delete'))) ?>)"><?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete">
                <button class="btn btn-sm btn-danger"><?= e(t('delete')) ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php page_foot();
