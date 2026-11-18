<?php
// Run from the command line:  C:\xampp\php\php.exe scripts\monitor.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit('Page Not Found'); }   // never runs from the browser
require __DIR__ . '/../php/bootstrap.php';

$since = date('Y-m-d H:i:s', time() - 86400);     // last 24 hours
$today = date('Y-m-d');
$scanned = (int)row("SELECT COUNT(*) c FROM activity_log WHERE created_at>=?", 's', [$since])['c'];
$found = [];   // [severity, label, pattern, target, detail]

// Pattern 1: one IP, failures at or above the threshold, any usernames
foreach (rows("SELECT ip_address ip, COUNT(*) c, COUNT(DISTINCT username_typed) u FROM activity_log
               WHERE event_type='LOGIN_FAILED' AND created_at>=? GROUP BY ip_address HAVING c>=?", 'si', [$since, IP_THRESHOLD]) as $r)
    $found[] = ['HIGH', 'IP over threshold', 'IP_OVER_THRESHOLD', $r['ip'], "{$r['c']} failures, {$r['u']} usernames"];

// Pattern 2: one username, failures from 3 or more different IPs
foreach (rows("SELECT username_typed u, COUNT(DISTINCT ip_address) n FROM activity_log
               WHERE event_type='LOGIN_FAILED' AND username_typed IS NOT NULL AND created_at>=? GROUP BY username_typed HAVING n>=3", 's', [$since]) as $r)
    $found[] = ['MEDIUM', 'Account targeted', 'ACCOUNT_TARGETED', $r['u'], "failures from {$r['n']} IPs"];

// Pattern 3: 5 or more OTP_FAILED for one account within 10 minutes
$times = [];
foreach (rows("SELECT user_id, created_at FROM activity_log WHERE event_type='OTP_FAILED' AND user_id IS NOT NULL AND created_at>=? ORDER BY user_id, created_at", 's', [$since]) as $r)
    $times[$r['user_id']][] = strtotime($r['created_at']);
foreach ($times as $uid => $ts) {
    for ($i = 0; $i + 4 < count($ts); $i++) {
        if ($ts[$i + 4] - $ts[$i] <= 600) {
            $name = row("SELECT username FROM users WHERE id_number=?", 's', [$uid])['username'] ?? $uid;
            $found[] = ['MEDIUM', 'OTP guessing', 'OTP_GUESSING', $name, '5 wrong OTPs in 10 min'];
            break;
        }
    }
}

// Pattern 4: LOGIN_SUCCESS between 12:00 AM and 5:00 AM Philippine time
foreach (rows("SELECT username_typed u, COUNT(*) n FROM activity_log WHERE event_type='LOGIN_SUCCESS' AND created_at>=? AND HOUR(created_at)<5 GROUP BY username_typed", 's', [$since]) as $r)
    $found[] = ['MEDIUM', 'Off-hours log-in', 'OFF_HOURS_LOGIN', (string)$r['u'], "{$r['n']} log-in(s) between 12:00 AM and 5:00 AM"];

$new = 0; $old = 0;
$lines = [];
foreach ($found as [$sev, $label, $pattern, $target, $detail]) {
    $stmt = q("INSERT IGNORE INTO security_flags(pattern,target,severity,details,flag_date) VALUES(?,?,?,?,?)", 'sssss', [$pattern, $target, $sev, $detail, $today]);
    $stmt->affected_rows === 1 ? $new++ : $old++;      // the UNIQUE key blocks a duplicate on the same day
    $lines[] = '[' . str_pad($sev === 'MEDIUM' ? 'MED' : $sev, 4) . '] ' . str_pad($label, 18) . ' ' . str_pad($target, 16) . ' ' . $detail;
}

echo 'IT 107 Monitoring Report  -  ' . date('Y-m-d H:i') . " PHT\n";
echo 'Scanned: ' . number_format($scanned) . " events (last 24 hours)\n";
echo $lines ? implode("\n", $lines) . "\n" : "No suspicious patterns found.\n";
echo "New flags written: $new      Already flagged today: $old\n";
