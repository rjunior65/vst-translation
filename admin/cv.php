<?php
/* Secure CV download (admin only). CV files are never reachable by URL. */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/careers-config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$s  = db()->prepare("SELECT cv_file, cv_original, name FROM job_applications WHERE id = ?");
$s->execute([$id]);
$row = $s->fetch();

$file = $row ? basename((string)$row['cv_file']) : '';
$path = UPLOAD_DIR_CV . $file;
if ($file === '' || !is_file($path)) {
    http_response_code(404);
    die('CV not found.');
}

db()->prepare("UPDATE job_applications SET is_read = 1 WHERE id = ?")->execute([$id]);

$ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'doc' => 'application/msword',
          'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$nice  = 'CV - ' . preg_replace('/[^\w\- ]+/u', '', (string)$row['name']) . '.' . $ext;

header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nice) . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
