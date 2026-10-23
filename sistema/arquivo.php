<?php
/* Serves a chat attachment to people who can see the conversation.
   Files live outside public_html, so this is the only way to reach them. */
require_once __DIR__ . '/inc/bootstrap.php';
require_once __DIR__ . '/inc/chat.php';

$user = current_user();
if (!$user || (int)$user['must_change_password'] === 1) { http_response_code(403); exit('Forbidden'); }

$st = pt_db()->prepare("SELECT * FROM pt_chat_files WHERE id = ?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$f = $st->fetch();
$conv = $f ? chat_get((int)$f['conversation_id'], $user) : null;
$path = $f ? PT_CHAT_DIR . basename($f['stored_name']) : '';
if (!$conv || !is_file($path)) { http_response_code(404); exit('Not found'); }

$ext = strtolower(pathinfo($f['stored_name'], PATHINFO_EXTENSION));
$inline = empty($_GET['dl']) && (chat_is_image($ext) || $ext === 'pdf');
$name = str_replace(['"', "\r", "\n"], '', $f['original_name']);

header_remove('Cache-Control');
header('Cache-Control: private, max-age=86400');
header('Content-Type: ' . chat_serve_type($ext));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . preg_replace('/[^\x20-\x7E]/', '_', $name)
     . '"; filename*=UTF-8\'\'' . rawurlencode($name));
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
