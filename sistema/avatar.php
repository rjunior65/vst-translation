<?php
/* Serves a profile photo to signed-in colleagues (chat, team list). */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/media.php';

if (!current_user()) { http_response_code(403); exit('Forbidden'); }

$st = pt_db()->prepare("SELECT photo FROM pt_users WHERE id = ?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$photo = (string)$st->fetchColumn();
$path = $photo !== '' ? PT_AVATAR_DIR . basename($photo) : '';
if ($path === '' || !is_file($path)) { http_response_code(404); exit('Not found'); }
image_send($path);
