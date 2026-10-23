<?php
/* Admin: download one CV (the file is outside public_html) and mark it as read. */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../../includes/careers-config.php';

require_admin();

$st = pt_db()->prepare("SELECT cv_file, cv_original, name FROM job_applications WHERE id = ?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$row = $st->fetch();
$file = $row ? basename((string)$row['cv_file']) : '';
$path = UPLOAD_DIR_CV . $file;
if ($file === '' || !is_file($path)) { http_response_code(404); exit('CV not found.'); }

pt_db()->prepare("UPDATE job_applications SET is_read = 1 WHERE id = ?")->execute([(int)$_GET['id']]);

$ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'doc' => 'application/msword',
          'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$nice  = 'CV - ' . preg_replace('/[^\w\- ]+/u', '', (string)$row['name']) . '.' . $ext;

header_remove('Cache-Control');
header('Cache-Control: private, no-store');
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . preg_replace('/[^\x20-\x7E]/', '_', $nice) . '"; filename*=UTF-8\'\'' . rawurlencode($nice));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
