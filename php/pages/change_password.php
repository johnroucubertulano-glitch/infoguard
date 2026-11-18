<?php
/* Two ways in: (a) verified through Forgot Password, (b) logged in. Anything else goes back to the start. */
$reset = !empty($_SESSION['reset_ok']) && $_SESSION['reset_ok']['exp'] > time();
if ($reset) $user = row("SELECT * FROM users WHERE id_number=?", 's', [$_SESSION['reset_ok']['uid']]);
else $user = current_user();
if (!$user) { unset($_SESSION['reset_ok']); go($reset ? 'forgot' : 'login'); }

$errors = []; $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $cur = (string)($_POST['current'] ?? '');
    $pw = (string)($_POST['password'] ?? '');
    $cf = (string)($_POST['confirm'] ?? '');
    if (!$reset && !password_verify($cur, $user['password_hash'])) $errors['current'] = 'Current password is incorrect.';
    elseif ($x = v_password($pw)) $errors['password'] = $x;
    elseif ($pw !== $cf) $errors['confirm'] = 'Mismatch Password.';
    else {
        $reused = false;
        foreach (rows("SELECT password_hash FROM password_history WHERE id_number=? ORDER BY id DESC LIMIT 3", 's', [$user['id_number']]) as $h)
            if (password_verify($pw, $h['password_hash'])) { $reused = true; break; }
        if ($reused) $errors['password'] = 'You cannot reuse a recent password.';
        else {
            $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
            q("UPDATE users SET password_hash=? WHERE id_number=?", 'ss', [$hash, $user['id_number']]);
            q("INSERT INTO password_history(id_number,password_hash) VALUES(?,?)", 'ss', [$user['id_number'], $hash]);
            tracker_reset(tracker_key($user['username']));
            log_event('PASSWORD_CHANGED', $user['username'], $user['id_number']);
            send_alert($user, 'Your password was changed', 'Your InfoGuard password was changed successfully.');
            if ($reset) { unset($_SESSION['reset_ok'], $_SESSION['fp'], $_SESSION['otp']); session_regenerate_id(true); }  // the reset is used up
            flash('Successfully Change Password.', 'success');
            go($reset ? 'login' : 'dashboard');
        }
    }
}
page_start('Change Password', 'other');
?>
<section class="card narrow">
<h1>Change Password</h1>
<?= msg_html($msg) ?>
<form method="POST" action="<?= BASE ?>change-password" novalidate>
<?= csrf_field() ?>
<?php if (!$reset) echo field('current', 'Current Password', ['type' => 'password', 'errors' => $errors, 'maxlength' => PASS_MAX, 'autocomplete' => 'current-password']); ?>
<?= field('password', 'Enter Password', ['type' => 'password', 'errors' => $errors, 'maxlength' => PASS_MAX, 'autocomplete' => 'new-password', 'after' => '<div class="meter" id="meter" data-for="password"><span></span></div><small class="hint" id="meterText">Strength: -</small>']) ?>
<?= field('confirm', 'Re-enter Password', ['type' => 'password', 'errors' => $errors, 'maxlength' => PASS_MAX, 'autocomplete' => 'new-password']) ?>
<button class="btn block" type="submit">Change Password</button>
</form>
</section>
<?php page_end();
