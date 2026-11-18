<?php
otp_flow('login', 'Log-in code', 'Enter your log-in code', function (string $uid) {
    $u = row("SELECT username, role FROM users WHERE id_number=?", 's', [$uid]);
    session_regenerate_id(true);                      // new session ID only AFTER the OTP is correct
    $_SESSION['uid'] = $uid;
    $_SESSION['last'] = time();
    tracker_reset(tracker_key($u['username']));       // a successful log-in resets the failure counter
    log_event('LOGIN_SUCCESS', $u['username'], $uid);
    go($u['role'] === 'admin' ? 'admin' : 'dashboard');
});
