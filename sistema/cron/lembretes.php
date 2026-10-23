<?php
// ============================================================
// Clock-in / clock-out reminders (phone notifications).
// Run by cron every 15 minutes — cPanel > Cron Jobs:
//   */15 * * * * php /home/<user>/public_html/sistema/cron/lembretes.php
// Command line only; the folder is also closed to the web (.htaccess).
// - no clock-in yet, after start + late tolerance + 5 min
// - still working (or on break), 30 min after the scheduled end
// Each reminder is sent at most once per person per day.
// Also deletes old punch selfies once a day (Locations > photo settings).
// ============================================================
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/hours.php';
require_once __DIR__ . '/../inc/punch.php';
require_once __DIR__ . '/../inc/push.php';
require_once __DIR__ . '/../inc/media.php';

purge_photos_daily();   // punch selfies older than the configured days (once a day)

if (setting_raw('reminders') === '0') exit(0);

$now   = time();
$today = date('Y-m-d', $now);
$schedules = schedules_all();

// Only people who can receive them (a phone subscribed) and have a schedule.
$users = pt_db()->query("SELECT u.* FROM pt_users u
                         WHERE u.is_active = 1 AND u.schedule_id IS NOT NULL
                           AND EXISTS (SELECT 1 FROM pt_push_subscriptions s WHERE s.user_id = u.id)")->fetchAll();
$sent = pt_db()->prepare("SELECT 1 FROM pt_notifications WHERE user_id = ? AND kind = ? AND ref_date = ?");
$count = 0;

foreach ($users as $u) {
    $sch = $schedules[(int)$u['schedule_id']] ?? null;
    if (!$sch || !$sch['is_active'] || !is_workday($sch, $today)) continue;

    $start = strtotime("$today {$sch['start_time']}");
    $end   = strtotime("$today {$sch['end_time']}");
    if ($end <= $start) continue;   // overnight schedules: not handled

    $punches = today_punches((int)$u['id']);
    $last = $punches ? end($punches)['type'] : null;

    $kind = null;
    if (!$punches && $now >= $start + ((int)$sch['late_tolerance_min'] + 5) * 60 && $now < $end) {
        $kind = 'remind_in';
        $vars = ['t' => substr($sch['start_time'], 0, 5)];
    } elseif (in_array($last, ['in', 'break_start', 'break_end'], true) && $now >= $end + 30 * 60) {
        $kind = 'remind_out';
        $vars = ['t' => substr($sch['end_time'], 0, 5)];
    }
    if (!$kind) continue;

    $sent->execute([$u['id'], $kind, $today]);
    if ($sent->fetchColumn()) continue;

    $GLOBALS['PT_LANG_OVERRIDE'] = $u['lang'];   // the person's own language
    notify([(int)$u['id']], $kind, t($kind . '_title'), t($kind . '_body', $vars), 'painel.php', $today);
    $count++;
}

echo date('Y-m-d H:i') . " reminders sent: $count\n";
