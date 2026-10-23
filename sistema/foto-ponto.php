<?php
/* Serves a punch selfie: to the person who took it, or to an admin. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/media.php';

$user = current_user();
if (!$user || (int)$user['must_change_password'] === 1) { http_response_code(403); exit('Forbidden'); }

$st = pt_db()->prepare("SELECT user_id, photo_path FROM pt_punches WHERE id = ?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$p = $st->fetch();
$allowed = $p && ($user['role'] === 'admin' || (int)$p['user_id'] === (int)$user['id']);
$path = $p && $p['photo_path'] ? PT_PHOTO_DIR . basename($p['photo_path']) : '';
if (!$allowed || !is_file($path)) { http_response_code(404); exit('Not found'); }
image_send($path);
