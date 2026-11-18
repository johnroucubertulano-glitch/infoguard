<?php
/* ---------- Lockout (server-side, per typed username) ---------- */
function tracker_key(string $username): string { return strtolower(substr(trim($username), 0, 50)); }

function lock_remaining(string $key): int {
    $r = row("SELECT locked_until FROM login_tracker WHERE key_name=?", 's', [$key]);
    return ($r && $r['locked_until']) ? max(0, strtotime($r['locked_until']) - time()) : 0;
}
function tracker_reset(string $key): void {
    q("DELETE FROM login_tracker WHERE key_name=?", 's', [$key]);
    $_SESSION['fails'] = 0;
}
/* Called for every wrong password OR wrong security answer. Returns lock seconds (0 = none). */
function register_failure(string $key, ?array $user, string $typed, string $why): int {
    log_event('LOGIN_FAILED', $typed, $user['id_number'] ?? null, $why);
    q("INSERT INTO login_tracker(key_name,failed) VALUES(?,1) ON DUPLICATE KEY UPDATE failed=failed+1", 's', [$key]);
    $n = (int)row("SELECT failed FROM login_tracker WHERE key_name=?", 's', [$key])['failed'];
    $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
    if ($n % 3 !== 0) return 0;
    $tier = min(3, intdiv($n, 3));
    $secs = LOCK_TIERS[$tier];
    q("UPDATE login_tracker SET locked_until=? WHERE key_name=?", 'ss', [date('Y-m-d H:i:s', time() + $secs), $key]);
    log_event('LOCKOUT_TIER_' . $tier, $typed, $user['id_number'] ?? null, "$n failures, locked $secs seconds");
    if ($user && $n <= 9)   // one email per tier, never one per attempt
        send_alert($user, 'Failed sign-in attempts on your account', "There have been $n failed sign-in attempts on your account.", $n);
    return $secs;
}

/* ---------- IP blocking across ANY account ---------- */
function ip_block_seconds(string $ip): int {
    $b = row("SELECT blocked_until FROM ip_blocks WHERE ip=?", 's', [$ip]);
    if ($b && strtotime($b['blocked_until']) > time()) return strtotime($b['blocked_until']) - time();
    $since = date('Y-m-d H:i:s', time() - 600);
    $n = (int)row("SELECT COUNT(*) c FROM activity_log WHERE event_type='LOGIN_FAILED' AND ip_address=? AND created_at>=?", 'ss', [$ip, $since])['c'];
    if ($n >= IP_THRESHOLD) {
        q("INSERT INTO ip_blocks(ip,blocked_until) VALUES(?,?) ON DUPLICATE KEY UPDATE blocked_until=VALUES(blocked_until)", 'ss', [$ip, date('Y-m-d H:i:s', time() + 600)]);
        log_event('IP_BLOCKED', null, null, "$n failures in 10 minutes");
        return 600;
    }
    return 0;
}

/* ---------- Alert emails ---------- */
function send_alert(array $u, string $subject, string $text, ?int $count = null): void {
    $body = '<p>Hi ' . e($u['first_name']) . ',</p><p>' . e($text) . '</p><ul>'
          . ($count !== null ? '<li>Failed attempts: ' . $count . '</li>' : '')
          . '<li>Date and time: ' . date('Y-m-d H:i:s') . ' PHT</li>'
          . '<li>IP address: ' . e(ip()) . '</li>'
          . '<li>Browser / device: ' . e(ua()) . '</li></ul>'
          . '<p>If this was not you, reset your password now: <a href="' . e(base_url()) . 'forgot">Forgot Password</a></p>';
    send_mail($u['email'], APP_NAME_SHORT . ': ' . $subject, $body);
    log_event('ALERT_EMAIL_SENT', null, $u['id_number'], $subject);
}
const APP_NAME_SHORT = 'InfoGuard';

/* ---------- OTP (6 digits, hashed, single use, 3 tries) ---------- */
function otp_hash(string $code): string { return hash_hmac('sha256', $code, (string)cfg('encryption_key')); }

function otp_issue(string $uid, string $purpose, string $email, string $name): void {
    q("UPDATE otps SET used=1 WHERE id_number=? AND purpose=? AND used=0", 'ss', [$uid, $purpose]);   // cancels the previous code
    $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $exp = time() + OTP_MINUTES * 60;
    q("INSERT INTO otps(id_number,purpose,code_hash,expires_at) VALUES(?,?,?,?)", 'ssss', [$uid, $purpose, otp_hash($code), date('Y-m-d H:i:s', $exp)]);
    $title = ['register' => 'Verify your account', 'login' => 'Your log-in code', 'reset' => 'Password reset code'][$purpose];
    send_mail($email, SYSTEM_NAME . ' - ' . $title,
        '<h3>' . e(SYSTEM_NAME) . '</h3><p>' . e($title) . '</p><h1>' . $code . '</h1>'
        . '<p>This code expires at ' . date('h:i A', $exp) . ' PHT (' . OTP_MINUTES . ' minutes).</p>'
        . '<p>If you did not request this code, ignore this email and consider changing your password.</p>');
    log_event('OTP_SENT', null, $uid, $purpose);
}
function otp_check(string $uid, string $purpose, string $code): string {
    $r = row("SELECT * FROM otps WHERE id_number=? AND purpose=? AND used=0 ORDER BY id DESC LIMIT 1", 'ss', [$uid, $purpose]);
    if (!$r) return 'cancelled';
    if (strtotime($r['expires_at']) < time()) {
        q("UPDATE otps SET used=1 WHERE id=?", 'i', [$r['id']]);
        log_event('OTP_FAILED', null, $uid, 'expired code');
        return 'expired';
    }
    if (hash_equals($r['code_hash'], otp_hash($code))) {
        q("UPDATE otps SET used=1 WHERE id=?", 'i', [$r['id']]);
        return 'ok';
    }
    $a = (int)$r['attempts'] + 1;
    q("UPDATE otps SET attempts=?, used=? WHERE id=?", 'iii', [$a, $a >= 3 ? 1 : 0, $r['id']]);   // 3 wrong = cancelled
    log_event('OTP_FAILED', null, $uid, "wrong code ($a of 3)");
    return $a >= 3 ? 'cancelled' : 'wrong';
}
function otp_wait(string $uid, string $purpose): int {
    $r = row("SELECT created_at FROM otps WHERE id_number=? AND purpose=? ORDER BY id DESC LIMIT 1", 'ss', [$uid, $purpose]);
    return $r ? max(0, strtotime($r['created_at']) + 60 - time()) : 0;   // 60-second cooldown
}
function otp_resend_allowed(string $uid, string $purpose): bool {
    $since = date('Y-m-d H:i:s', time() - 900);
    $n = (int)row("SELECT COUNT(*) c FROM otps WHERE id_number=? AND purpose=? AND created_at>=?", 'sss', [$uid, $purpose, $since])['c'];
    return $n < 4;   // 1 original + 3 resends per 15 minutes
}

/* One OTP screen used by registration, log-in and password reset */
function otp_flow(string $purpose, string $title, string $heading, callable $onOk): void {
    $ctx = $_SESSION['otp'] ?? null;
    if (!$ctx || $ctx['purpose'] !== $purpose) go('login');      // steps cannot be skipped
    $uid = (string)$ctx['uid'];
    $user = row("SELECT email, first_name FROM users WHERE id_number=?", 's', [$uid]);
    if (!$user) go('login');
    $msg = ''; $type = 'error';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        if (isset($_POST['resend'])) {
            if (otp_wait($uid, $purpose) > 0) $msg = 'Please wait before requesting a new code.';
            elseif (!otp_resend_allowed($uid, $purpose)) $msg = 'Resend limit reached. Try again in 15 minutes.';
            else { otp_issue($uid, $purpose, $user['email'], $user['first_name']); $msg = 'A new code was sent to your email.'; $type = 'success'; }
        } else {
            $code = trim((string)($_POST['code'] ?? ''));
            if (!preg_match('/^\d{6}$/', $code)) $msg = 'Please enter the 6-digit code.';
            else {
                $res = otp_check($uid, $purpose, $code);
                if ($res === 'ok') { unset($_SESSION['otp']); $onOk($uid); return; }
                $msg = ['wrong' => 'Incorrect code. Please try again.',
                        'expired' => 'This code has expired. Request a new one.',
                        'cancelled' => 'This code was cancelled. Request a new one.'][$res];
            }
        }
    }
    $wait = otp_wait($uid, $purpose);
    $masked = preg_replace('/(?<=.{2}).(?=[^@]*@)/', '*', $user['email']);
    page_start($title, 'other');
    echo '<section class="card narrow"><h1>' . e($heading) . '</h1><p class="muted">We sent a 6-digit code to ' . e($masked)
       . '. It expires in ' . OTP_MINUTES . ' minutes.</p>' . msg_html($msg, $type)
       . '<form method="POST" action="' . BASE . e($_GET['p']) . '" novalidate>' . csrf_field()
       . '<div class="field"><label for="code">6-digit code<span class="req">*</span></label><input type="text" name="code" id="code" class="otp" inputmode="numeric" maxlength="6" autocomplete="one-time-code"></div>'
       . '<button class="btn block" type="submit">Verify</button></form>'
       . '<form method="POST" action="' . BASE . e($_GET['p']) . '" class="center">' . csrf_field()
       . '<button class="btn-link" type="submit" name="resend" value="1" id="resendBtn" data-wait="' . $wait . '">Resend OTP</button></form>'
       . '<p class="center small"><a href="' . BASE . 'login">Back to log-in</a></p></section>';
    page_end();
}
