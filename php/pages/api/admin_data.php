<?php
header('Content-Type: application/json');
$u = current_user();
if (!$u || $u['role'] !== 'admin') { http_response_code(403); exit; }   // returns nothing unless an admin session exists

$today = date('Y-m-d 00:00:00');
$day = date('Y-m-d H:i:s', time() - 86400);
$count = fn(string $t): int => (int)row("SELECT COUNT(*) c FROM activity_log WHERE event_type=? AND created_at>=?", 'ss', [$t, $today])['c'];

$summary = [
    'Successful log-ins' => $count('LOGIN_SUCCESS'),
    'Failed log-ins' => $count('LOGIN_FAILED'),
    'Accounts locked now' => (int)row("SELECT COUNT(*) c FROM login_tracker WHERE locked_until>NOW()")['c'],
    'IPs blocked now' => (int)row("SELECT COUNT(*) c FROM ip_blocks WHERE blocked_until>NOW()")['c'],
    'OTPs sent' => $count('OTP_SENT'),
    'OTPs failed' => $count('OTP_FAILED'),
    'Alert emails sent' => $count('ALERT_EMAIL_SENT'),
];

$byHour = [];
foreach (rows("SELECT DATE_FORMAT(created_at,'%Y-%m-%d %H:00') h, COUNT(*) c FROM activity_log WHERE event_type='LOGIN_FAILED' AND created_at>=? GROUP BY h", 's', [$day]) as $r)
    $byHour[$r['h']] = (int)$r['c'];
$hourly = [];
for ($i = 23; $i >= 0; $i--) {
    $t = time() - $i * 3600;
    $hourly[] = ['label' => date('H:00', $t), 'count' => $byHour[date('Y-m-d H:00', $t)] ?? 0];
}

$top = [];
foreach (rows("SELECT ip_address ip, COUNT(*) c FROM activity_log WHERE event_type='LOGIN_FAILED' AND created_at>=? GROUP BY ip_address ORDER BY c DESC LIMIT 5", 's', [$day]) as $r) {
    $b = row("SELECT 1 FROM ip_blocks WHERE ip=? AND blocked_until>NOW()", 's', [$r['ip']]);
    $top[] = ['ip' => $r['ip'], 'count' => (int)$r['c'], 'blocked' => (bool)$b];
}

$flags = rows("SELECT pattern, target, severity, details, flag_date FROM security_flags ORDER BY id DESC LIMIT 20");

$where = []; $types = ''; $params = [];
$df = (string)($_GET['date_from'] ?? ''); $dt = (string)($_GET['date_to'] ?? '');
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $df)) { $where[] = 'created_at>=?'; $params[] = $df . ' 00:00:00'; }
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) { $where[] = 'created_at<=?'; $params[] = $dt . ' 23:59:59'; }
$et = trim((string)($_GET['etype'] ?? ''));
if ($et !== '' && preg_match('/^[A-Za-z0-9_]{1,40}$/', $et)) { $where[] = 'event_type=?'; $params[] = strtoupper($et); }
$un = trim((string)($_GET['uname'] ?? ''));
if ($un !== '') { $where[] = 'username_typed LIKE ?'; $params[] = '%' . substr($un, 0, 50) . '%'; }
$fi = trim((string)($_GET['fip'] ?? ''));
if ($fi !== '') { $where[] = 'ip_address LIKE ?'; $params[] = '%' . substr($fi, 0, 45) . '%'; }
$sql = "SELECT created_at, event_type, username_typed, user_id, ip_address, user_agent, details FROM activity_log"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY id DESC LIMIT 50";
$events = rows($sql, str_repeat('s', count($params)), $params);

echo json_encode(compact('summary', 'hourly', 'top', 'flags', 'events'));
