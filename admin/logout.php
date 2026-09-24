<?php
require_once __DIR__ . '/../includes/auth.php';
logout_admin();
header('Location: index.php');
exit;
