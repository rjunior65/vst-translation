<?php
/* ============================================================
   VST Ponto — hour calculations.
   Worked = time between "in"/"break_end" and the next "break_start"/"out".
   Break  = time between "break_start" and "break_end".
   Expected comes from the user's schedule; overtime only counts beyond
   the schedule's tolerance. All times are server (Guyana) local time.
   ============================================================ */

const WEEKDAYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

function schedules_all(): array {
    $rows = pt_db()->query("SELECT * FROM pt_schedules ORDER BY is_active DESC, name")->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[(int)$r['id']] = $r;
    return $out;
}

function is_workday(?array $schedule, string $date): bool {
    if (!$schedule) return false;
    $wd = WEEKDAYS[(int)date('w', strtotime($date))];
    return in_array($wd, explode(',', $schedule['workdays']), true);
}

/* Seconds of work expected on a date (0 on days off or without schedule). */
function expected_seconds(?array $schedule, string $date): int {
    if (!is_workday($schedule, $date)) return 0;
    $span = strtotime("$date {$schedule['end_time']}") - strtotime("$date {$schedule['start_time']}");
    if ($span <= 0) $span += 86400; // schedule crossing midnight
    return max(0, $span - (int)$schedule['break_minutes'] * 60);
}

/* One day for one person.
   $punches: that day's non-voided punches, oldest first.
   $now: current timestamp; an open segment on today's date runs until $now,
   on a past date it stops at the last punch and the day is marked incomplete. */
function day_summary(array $punches, ?array $schedule, string $date, int $now): array {
    $isToday  = $date === date('Y-m-d', $now);
    $worked   = 0;
    $break    = 0;
    $workFrom = null;
    $breakFrom = null;
    $firstIn  = null;
    $lastOut  = null;
    $flagged  = 0;
    $state    = 'off';

    foreach ($punches as $p) {
        $ts = strtotime($p['punched_at']);
        if ($p['status'] === 'outside' || $p['status'] === 'no_gps') $flagged++;
        switch ($p['type']) {
            case 'in':
                if ($firstIn === null) $firstIn = $ts;
                if ($workFrom === null) $workFrom = $ts;   // a repeated "in" doesn't restart the count
                if ($breakFrom !== null) { $break += $ts - $breakFrom; $breakFrom = null; }
                $state = 'working';
                break;
            case 'break_start':
                if ($workFrom !== null) { $worked += $ts - $workFrom; $workFrom = null; }
                if ($breakFrom === null) $breakFrom = $ts;
                $state = 'break';
                break;
            case 'break_end':
                if ($breakFrom !== null) { $break += $ts - $breakFrom; $breakFrom = null; }
                if ($workFrom === null) $workFrom = $ts;
                $state = 'working';
                break;
            case 'out':
                if ($workFrom !== null) { $worked += $ts - $workFrom; $workFrom = null; }
                if ($breakFrom !== null) { $break += $ts - $breakFrom; $breakFrom = null; }
                $lastOut = $ts;
                $state = 'done';
                break;
        }
    }

    $incomplete = false;
    if ($workFrom !== null || $breakFrom !== null) {
        if ($isToday) {
            if ($workFrom !== null)  $worked += max(0, $now - $workFrom);
            if ($breakFrom !== null) $break  += max(0, $now - $breakFrom);
        } else {
            $incomplete = true;   // forgot to clock out
        }
    }

    $expected = expected_seconds($schedule, $date);
    $overtime = null;
    $missing  = null;
    $late     = 0;
    if ($schedule) {
        $tol = (int)$schedule['overtime_after_min'] * 60;
        $diff = $worked - $expected;
        $overtime = $diff > $tol ? $diff : 0;
        // Missing hours only once the day is over (or the person clocked out).
        $dayOver = !$isToday || $state === 'done';
        $missing = ($dayOver && $expected > 0 && $worked > 0 && -$diff > $tol) ? -$diff : 0;
        if ($firstIn !== null && $expected > 0) {
            $start = strtotime("$date {$schedule['start_time']}");
            $mins  = (int)floor(($firstIn - $start) / 60);
            if ($mins > (int)$schedule['late_tolerance_min']) $late = $mins;
        }
    }

    // Absent: a workday with no punch at all, once the scheduled end has passed.
    $absent = false;
    if (!$punches && $expected > 0) {
        $end = strtotime("$date {$schedule['end_time']}");
        $absent = $now > $end;
    }

    return [
        'date' => $date, 'worked' => $worked, 'break' => $break, 'expected' => $expected,
        'overtime' => $overtime, 'missing' => $missing, 'late_min' => $late, 'absent' => $absent,
        'first_in' => $firstIn, 'last_out' => $lastOut, 'incomplete' => $incomplete,
        'flagged' => $flagged, 'count' => count($punches), 'state' => $state,
        'workday' => is_workday($schedule, $date),
    ];
}

/* Valid punches for some users between two dates (inclusive), grouped [user_id][Y-m-d][] */
function punches_by_user_day(array $userIds, string $from, string $to): array {
    if (!$userIds) return [];
    $in = implode(',', array_map('intval', $userIds));
    $st = pt_db()->prepare("SELECT * FROM pt_punches
                            WHERE is_void = 0 AND user_id IN ($in) AND punched_at >= ? AND punched_at < ? + INTERVAL 1 DAY
                            ORDER BY punched_at, id");
    $st->execute([$from, $to]);
    $out = [];
    foreach ($st->fetchAll() as $p) {
        $out[(int)$p['user_id']][substr($p['punched_at'], 0, 10)][] = $p;
    }
    return $out;
}

/* Month report for the given users.
   Returns [user_id => ['user' => row, 'days' => [date => summary], 'totals' => [...]]] */
function month_report(array $users, string $ym, ?int $now = null): array {
    $now   = $now ?? time();
    $first = "$ym-01";
    $last  = date('Y-m-t', strtotime($first));
    $today = date('Y-m-d', $now);
    $schedules = schedules_all();
    $punches = punches_by_user_day(array_column($users, 'id'), $first, $last);

    $out = [];
    foreach ($users as $u) {
        $uid = (int)$u['id'];
        $sch = $schedules[(int)$u['schedule_id']] ?? null;
        $since = substr($u['created_at'], 0, 10);   // nobody is absent before their account existed
        $days = [];
        $tot = ['worked' => 0, 'break' => 0, 'expected' => 0, 'overtime' => 0, 'missing' => 0,
                'late_count' => 0, 'late_min' => 0, 'absent' => 0, 'days_worked' => 0, 'incomplete' => 0, 'flagged' => 0];
        for ($d = $first; $d <= $last && $d <= $today; $d = date('Y-m-d', strtotime("$d +1 day"))) {
            $p = $punches[$uid][$d] ?? [];
            if (!$p && $d < $since) continue;
            $s = day_summary($p, $sch, $d, $now);
            $days[$d] = $s;
            $tot['worked']   += $s['worked'];
            $tot['break']    += $s['break'];
            $tot['expected'] += $s['expected'];
            $tot['overtime'] += (int)$s['overtime'];
            $tot['missing']  += (int)$s['missing'];
            $tot['late_min'] += $s['late_min'];
            $tot['late_count'] += $s['late_min'] > 0 ? 1 : 0;
            $tot['absent']   += $s['absent'] ? 1 : 0;
            $tot['days_worked'] += $s['count'] > 0 ? 1 : 0;
            $tot['incomplete'] += $s['incomplete'] ? 1 : 0;
            $tot['flagged']  += $s['flagged'];
        }
        $tot['has_schedule'] = $sch !== null;
        $out[$uid] = ['user' => $u, 'schedule' => $sch, 'days' => $days, 'totals' => $tot];
    }
    return $out;
}

/* 27000 → "7:30" */
function fmt_hm(?int $secs): string {
    if ($secs === null) return '—';
    $m = intdiv(max(0, $secs), 60);
    return intdiv($m, 60) . ':' . str_pad((string)($m % 60), 2, '0', STR_PAD_LEFT);
}

function valid_month(string $ym): bool {
    return (bool)preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym);
}
function month_label(string $ym): string {
    $names = ['pt' => ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'],
              'en' => ['January','February','March','April','May','June','July','August','September','October','November','December'],
              'es' => ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre']];
    [$y, $m] = explode('-', $ym);
    return ucfirst($names[current_lang()][(int)$m - 1]) . ' ' . $y;
}
function weekday_short(string $date): string {
    $n = ['pt' => ['dom','seg','ter','qua','qui','sex','sáb'], 'en' => ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
          'es' => ['dom','lun','mar','mié','jue','vie','sáb']];
    return $n[current_lang()][(int)date('w', strtotime($date))];
}
