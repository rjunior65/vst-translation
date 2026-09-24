<?php
/* ============================================================
   VST — Configuration
   Fill in your HostGator MySQL details below.
   (cPanel > MySQL Databases: create a database + user, then
    add the user to the database with ALL PRIVILEGES.)

   LOCAL TESTING: if a file includes/config.local.php exists, its
   DB_* values are used instead (handy for XAMPP). That file is
   for your computer only — do NOT upload it to HostGator.
   ============================================================ */

// ---- Local override (XAMPP / your machine), if present ----
if (file_exists(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

// ---- Database (HostGator) — used unless overridden above ----
if (!defined('DB_HOST'))    define('DB_HOST', 'localhost');            // On HostGator this is almost always 'localhost'
if (!defined('DB_NAME'))    define('DB_NAME', 'CPANELUSER_vst');       // e.g. myaccount_vst
if (!defined('DB_USER'))    define('DB_USER', 'CPANELUSER_vstadmin');  // the MySQL user you created
if (!defined('DB_PASS'))    define('DB_PASS', 'CHANGE_THIS_PASSWORD'); // the MySQL user's password
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ---- Photo uploads ----
define('UPLOAD_DIR', __DIR__ . '/../uploads/team/');            // team photos (physical, writable)
define('UPLOAD_URL', 'uploads/team/');                          // team photos (public URL)
define('UPLOAD_DIR_TOURISM', __DIR__ . '/../uploads/tourism/'); // tourism photos (physical, writable)
define('UPLOAD_URL_TOURISM', 'uploads/tourism/');               // tourism photos (public URL)
define('MAX_UPLOAD_BYTES', 6 * 1024 * 1024);                    // 6 MB max per photo

// ---- Session cookie name ----
define('SESSION_NAME', 'vst_admin');
