<?php
/* ============================================================
   Public endpoint: receives a "Work With Us" application + CV.
   - POST multipart/form-data only
   - Validates fields, CV type (real file content) and size
   - Anti-spam: hidden honeypot field + per-IP hourly limit
   - Stores the CV with a random name in uploads/cv/ (not public)
   Responds with JSON: { ok: true } or { ok: false, error: "code" }
   ============================================================ */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/careers-config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail(string $code, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['ok' => false, 'error' => $code]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('method', 405);

// Honeypot: real people never fill this hidden field
if (trim($_POST['website'] ?? '') !== '') {
    echo json_encode(['ok' => true]);
    exit;
}

$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$phone   = trim((string)($_POST['phone'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$jobId   = (int)($_POST['job_id'] ?? 0);

if ($name === '' || mb_strlen($name) > 160)                           fail('name');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) fail('email');
if (mb_strlen($phone) > 60)                                            fail('phone');
if (mb_strlen($message) > 3000)                                        fail('message');

$ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

try {
    $pdo = db();

    // Rate limit per IP
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)");
    $cnt->execute([$ip]);
    if ((int)$cnt->fetchColumn() >= MAX_APPLICATIONS_PER_HOUR) fail('limit', 429);

    // Job (optional: 0 = general application)
    $jobTitle = 'General application';
    if ($jobId > 0) {
        $j = $pdo->prepare("SELECT title_en FROM jobs WHERE id = ? AND is_active = 1");
        $j->execute([$jobId]);
        $t = $j->fetchColumn();
        if ($t === false) { $jobId = 0; } else { $jobTitle = (string)$t; }
    }

    // CV file
    $f = $_FILES['cv'] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) fail('cv_missing');
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) fail('cv_size');
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name']))         fail('cv_upload');
    if ($f['size'] <= 0 || $f['size'] > MAX_CV_BYTES)                               fail('cv_size');

    $ext     = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    $allowed = cv_allowed_types();
    if (!isset($allowed[$ext])) fail('cv_type');
    if (function_exists('finfo_open')) {
        // If the real type can't be read, refuse: never fall back to trusting the extension.
        $mime = @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']);
        if (!$mime) fail('cv_upload');
        if (!in_array($mime, $allowed[$ext], true)) fail('cv_type');
    }

    if (!is_dir(UPLOAD_DIR_CV)) @mkdir(UPLOAD_DIR_CV, 0755, true);
    $stored = 'cv_' . date('Ymd') . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    if (!@move_uploaded_file($f['tmp_name'], UPLOAD_DIR_CV . $stored)) fail('cv_save', 500);

    // Keep only a safe display version of the original file name
    $original = preg_replace('/[^\w.\- ]+/u', '_', basename((string)$f['name']));
    $original = mb_substr($original, 0, 200);

    $pdo->prepare(
        "INSERT INTO job_applications (job_id, job_title, name, email, phone, message, cv_file, cv_original, ip)
         VALUES (?,?,?,?,?,?,?,?,?)"
    )->execute([$jobId ?: null, $jobTitle, $name, $email, $phone, $message, $stored, $original, $ip]);

    // Optional e-mail alert (no attachment, no user data in headers)
    if (CAREERS_NOTIFY_EMAIL !== '') {
        $subject = 'VST - New job application: ' . preg_replace('/[\r\n]+/', ' ', $jobTitle);
        $body    = "A new application was received on the website.\n\n"
                 . "Position: {$jobTitle}\nName: {$name}\nEmail: {$email}\nPhone: {$phone}\n\n"
                 . "Open the admin panel (Careers > Applications) to read it and download the CV.\n";
        $host    = preg_replace('/[^a-z0-9.\-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        @mail(CAREERS_NOTIFY_EMAIL, $subject, $body, "From: no-reply@{$host}\r\nContent-Type: text/plain; charset=UTF-8");
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $ex) {
    error_log('apply.php: ' . $ex->getMessage());
    fail('server', 500);
}
