<?php
$msg = ''; $lock = 0; $username = '';
$key = (string)($_SESSION['lock_key'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string)($_POST['username'] ?? ''));
    $pw = (string)($_POST['password'] ?? '');
    $key = tracker_key($username);
    $_SESSION['lock_key'] = $key;

    if (($s = ip_block_seconds(ip())) > 0) { $lock = $s; $msg = 'Too many attempts from your network. Please wait.'; }
    elseif (($s = lock_remaining($key)) > 0) { $lock = $s; $msg = 'Too many failed attempts. Please wait.'; }
    elseif (!captcha_ok()) { log_event('CAPTCHA_FAILED', $username); $msg = 'Incorrect CAPTCHA. Please try again.'; }   // my call: a CAPTCHA miss is not a failed log-in
    else {
        $user = null;
        if (preg_match('/^[A-Za-z][A-Za-z0-9]{' . (USER_MIN - 1) . ',' . (USER_MAX - 1) . '}$/', $username) && strlen($pw) >= PASS_MIN && strlen($pw) <= PASS_MAX)
            $user = row("SELECT * FROM users WHERE username=?", 's', [$username]);
        $ok = $user ? password_verify($pw, $user['password_hash']) : (password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]) && false);   // same work either way

        if ($ok) {
            if (!$user['is_verified']) {
                otp_issue($user['id_number'], 'register', $user['email'], $user['first_name']);
                $_SESSION['otp'] = ['uid' => $user['id_number'], 'purpose' => 'register'];
                flash('Please verify your account first. We sent you a new code.', 'info');
                go('verify');
            }
            otp_issue($user['id_number'], 'login', $user['email'], $user['first_name']);   // no session yet
            $_SESSION['otp'] = ['uid' => $user['id_number'], 'purpose' => 'login'];
            go('login-otp');
        }
        $secs = register_failure($key, $user, $username, 'wrong username or password');
        if ($secs > 0) { $lock = $secs; $msg = 'Too many failed attempts. Please wait.'; }
        else $msg = 'Invalid username or password.';        // identical whether or not the username exists
    }
} elseif ($key !== '') {
    $lock = lock_remaining($key);
    if ($lock > 0) $msg = 'Too many failed attempts. Please wait.';
}

page_start('Log-in', 'login');
?>
<section class="card narrow">
<h1>Welcome back</h1>
<p class="muted">Log-in to your InfoGuard account.</p>
<?= msg_html($msg) ?>
<p class="msg error hidden" id="lockMsg">Locked. Try again in <strong id="lockSec"><?= (int)$lock ?></strong> seconds.</p>
<form method="POST" action="<?= BASE ?>login" id="loginForm" novalidate data-lock="<?= (int)$lock ?>">
<?= csrf_field() ?>
<?= field('username', 'Username', ['value' => $username, 'maxlength' => USER_MAX, 'autocomplete' => 'username']) ?>
<?= field('password', 'Password', ['type' => 'password', 'maxlength' => PASS_MAX, 'autocomplete' => 'current-password']) ?>
<?= captcha_block() ?>
<button class="btn block" type="submit" data-lockable>Log-in</button>
</form>
<?php if (($_SESSION['fails'] ?? 0) >= FORGOT_AFTER): ?>
<p class="center"><a href="<?= BASE ?>forgot" class="strong">Forgot Password? Reset Here</a></p>
<?php endif; ?>
<p class="center small">Don't have an account? Please <a href="<?= BASE ?>register" class="lockable">register here</a>.</p>
</section>
<?php page_end();
