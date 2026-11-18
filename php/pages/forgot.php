<?php
$msg = ''; $username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim((string)($_POST['username'] ?? ''));
    if (($s = ip_block_seconds(ip())) > 0) $msg = 'Too many attempts from your network. Please wait.';
    elseif (($s = lock_remaining(tracker_key($username))) > 0) $msg = 'Too many failed attempts. Please wait.';
    elseif (!captcha_ok()) { log_event('CAPTCHA_FAILED', $username); $msg = 'Incorrect CAPTCHA. Please try again.'; }
    elseif ($username === '') $msg = 'Please enter your username.';
    else {
        $u = row("SELECT id_number FROM users WHERE username=? AND is_verified=1", 's', [$username]);
        log_event('RESET_REQUESTED', $username, $u['id_number'] ?? null);
        $_SESSION['fp'] = ['key' => tracker_key($username), 'typed' => $username, 'stage' => 1];   // same path whether or not the user exists
        go('forgot-question');
    }
}
page_start('Forgot Password', 'other');
?>
<section class="card narrow">
<h1>Forgot your password?</h1>
<p class="muted">Enter your username. We will ask you one of your security questions.</p>
<?= msg_html($msg) ?>
<form method="POST" action="<?= BASE ?>forgot" novalidate>
<?= csrf_field() ?>
<?= field('username', 'Username', ['value' => $username, 'maxlength' => USER_MAX, 'autocomplete' => 'username']) ?>
<?= captcha_block() ?>
<button class="btn block" type="submit">Continue</button>
</form>
<p class="center small"><a href="<?= BASE ?>login">Back to log-in</a></p>
</section>
<?php page_end();
