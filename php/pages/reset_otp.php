<?php
if (($_SESSION['fp']['stage'] ?? 0) < 2) go('forgot');      // question step must be done first
otp_flow('reset', 'Password reset code', 'Enter your password reset code', function (string $uid) {
    $_SESSION['reset_ok'] = ['uid' => $uid, 'exp' => time() + 600];   // the server remembers steps 1-5 were completed
    go('change-password');
});
