<?php
/* Entry point: send each person to the right place. */
require_once __DIR__ . '/inc/bootstrap.php';

$u = current_user();
if (!$u) redirect('login.php');
if ((int)$u['must_change_password'] === 1) redirect('senha.php');
redirect(home_for($u));
