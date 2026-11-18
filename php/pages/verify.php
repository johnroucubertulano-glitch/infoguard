<?php
otp_flow('register', 'Verify account', 'Verify your account', function (string $uid) {
    q("UPDATE users SET is_verified=1 WHERE id_number=?", 's', [$uid]);
    log_event('ACCOUNT_VERIFIED', null, $uid);
    flash('Your account is verified. You can now log in.', 'success');
    go('login');
});
