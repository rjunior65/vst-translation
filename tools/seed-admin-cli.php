<?php
/* CLI helper to create the first admin (used for local testing).
   Usage:  php seed-admin-cli.php <email> <password> [name]
   Does nothing (safe) if an admin already exists.
   You can delete this file when you're done. */
require_once __DIR__ . '/../includes/db.php';

if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only.'); }

$email = $argv[1] ?? '';
$pass  = $argv[2] ?? '';
$name  = $argv[3] ?? 'Admin';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
    fwrite(STDERR, "usage: php seed-admin-cli.php <email> <password(min 8)> [name]\n");
    exit(1);
}

$n = (int) db()->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
if ($n > 0) { echo "ADMIN_EXISTS\n"; exit(0); }

$hash = password_hash($pass, PASSWORD_DEFAULT);
db()->prepare("INSERT INTO admin_users (name, email, password_hash) VALUES (?,?,?)")
    ->execute([$name, $email, $hash]);
echo "ADMIN_CREATED\n";
