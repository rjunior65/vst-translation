<?php
/* Log out (POST only, with CSRF, so a link can't log someone out). */
require_once __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    logout_user();
}
redirect('login.php');
