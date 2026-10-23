<?php
/* ============================================================
   VST Ponto — images: profile photos and punch selfies.
   Stored outside public_html (PT_PRIVATE_DIR). With PHP's GD the
   picture is re-encoded as JPEG: resized, turned upright, and every
   hidden metadata (EXIF, GPS of the phone) is dropped. Without GD the
   checked original is kept.
   ============================================================ */

define('PT_AVATAR_DIR', PT_PRIVATE_DIR . 'perfil/');
define('PT_PHOTO_DIR', PT_PRIVATE_DIR . 'ponto-fotos/');
define('PT_IMAGE_MAX_BYTES', 8 * 1024 * 1024);

/* Saves an uploaded image as $dir/$base.jpg (or the original extension without GD).
   Returns [storedName, null] or [null, errorKey]. */
function image_save(array $f, string $dir, string $base, int $maxSide): array {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, 'img_err_missing'];
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > PT_IMAGE_MAX_BYTES) return [null, 'img_err_size'];
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return [null, 'img_err_upload'];

    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = function_exists('finfo_open') ? @finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']) : null;
    if (!$mime || !isset($types[$mime])) return [null, 'img_err_type'];
    if (!is_dir($dir) && !@mkdir($dir, 0750, true)) return [null, 'img_err_upload'];

    if (!function_exists('imagecreatefromstring')) {
        $name = $base . '.' . $types[$mime];
        return move_uploaded_file($f['tmp_name'], $dir . $name) ? [$name, null] : [null, 'img_err_upload'];
    }

    $img = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
    if (!$img) return [null, 'img_err_type'];
    // Phone photos are often stored sideways with an EXIF "rotate me" flag.
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $o = (int)(@exif_read_data($f['tmp_name'])['Orientation'] ?? 1);
        $deg = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($deg) $img = imagerotate($img, $deg, 0);
    }
    $w = imagesx($img);
    $h = imagesy($img);
    $scale = min(1, $maxSide / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));   // transparent PNG → white
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $name = $base . '.jpg';
    $ok = imagejpeg($out, $dir . $name, 82);
    imagedestroy($img);
    imagedestroy($out);
    return $ok ? [$name, null] : [null, 'img_err_upload'];
}

function image_send(string $path): void {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    header_remove('Cache-Control');
    header('Cache-Control: private, max-age=86400');
    header('Content-Type: ' . (['png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? 'image/jpeg'));
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

/* ---- Avatars: own photo, else the website team photo, else initials ---- */
function avatar_url(array $u): ?string {
    static $team = null;
    if (!empty($u['photo'])) return base() . 'avatar.php?id=' . (int)$u['id'] . '&v=' . substr(md5($u['photo']), 0, 8);
    if (!empty($u['employee_id'])) {
        if ($team === null) $team = pt_db()->query("SELECT id, image FROM employees")->fetchAll(PDO::FETCH_KEY_PAIR);
        $img = $team[(int)$u['employee_id']] ?? '';
        if ($img !== '' && preg_match('~^[\w/.-]+$~', $img)) return base() . '../' . $img;
    }
    return null;
}
function initials(string $name): string {
    $p = preg_split('/\s+/', trim($name));
    $s = mb_substr($p[0] ?? '', 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : '');
    return mb_strtoupper($s ?: '?');
}
function avatar_html(array $u, string $size = ''): string {
    $url = avatar_url($u);
    $cls = 'avatar' . ($size ? " avatar-$size" : '');
    return $url
        ? '<span class="' . $cls . '"><img src="' . e($url) . '" alt=""' . ($size === 'xs' ? ' loading="lazy"' : '') . '></span>'
        : '<span class="' . $cls . '">' . e(initials($u['full_name'] ?? '')) . '</span>';
}

/* Punch selfies older than the configured number of days are deleted (files + references). */
function purge_old_punch_photos(): int {
    $days = max(1, (int)(pt_db()->query("SELECT svalue FROM pt_settings WHERE skey = 'photo_days'")->fetchColumn() ?: 31));
    $st = pt_db()->prepare("SELECT id, photo_path FROM pt_punches WHERE photo_path IS NOT NULL AND punched_at < NOW() - INTERVAL $days DAY");
    $st->execute();
    $n = 0;
    $clear = pt_db()->prepare("UPDATE pt_punches SET photo_path = NULL WHERE id = ?");
    foreach ($st->fetchAll() as $r) {
        $path = PT_PHOTO_DIR . basename($r['photo_path']);
        if (is_file($path)) @unlink($path);
        $clear->execute([$r['id']]);
        $n++;
    }
    return $n;
}
/* Runs the purge at most once a day (from the cron job, or the admin's Today page as a fallback). */
function purge_photos_daily(): void {
    $today = date('Y-m-d');
    $last = pt_db()->query("SELECT svalue FROM pt_settings WHERE skey = 'photo_purge_day'")->fetchColumn();
    if ($last === $today) return;
    pt_db()->prepare("INSERT INTO pt_settings (skey, svalue) VALUES ('photo_purge_day', ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)")->execute([$today]);
    $n = purge_old_punch_photos();
    if ($n) audit('purge_photos', 'punch', null, null, ['deleted' => $n]);
}
