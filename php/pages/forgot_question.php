<?php
$fp = $_SESSION['fp'] ?? null;
if (!$fp || ($fp['stage'] ?? 0) < 1) go('forgot');          // cannot skip step 1
$key = $fp['key']; $typed = $fp['typed'];
$user = row("SELECT * FROM users WHERE username=? AND is_verified=1", 's', [$typed]);

/* The user's own three questions. A non-existing username gets three believable fake ones. */
if ($user) {
    $nums = array_column(rows("SELECT q_no FROM auth_answers WHERE id_number=? ORDER BY q_no", 's', [$user['id_number']]), 'q_no');
} else {
    $h = crc32($key); $nums = [];
    for ($i = 0; count($nums) < 3; $i++) { $n = (($h + $i * 3) % 8) + 1; if (!in_array($n, $nums)) $nums[] = $n; }
    sort($nums);
}
$nums = array_map('intval', $nums);
$opts = [];
foreach ($nums as $n) $opts[$n] = QUESTIONS[$n];

$msg = ''; $errors = []; $lock = lock_remaining($key); $sel = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sel = (string)($_POST['q'] ?? '');
    $a1 = trim((string)($_POST['a1'] ?? ''));
    $a2 = trim((string)($_POST['a2'] ?? ''));
    if (($s = ip_block_seconds(ip())) > 0) { $msg = 'Too many attempts from your network. Please wait.'; $lock = $s; }
    elseif ($lock > 0) $msg = 'Too many failed attempts. Please wait.';
    elseif (!in_array((int)$sel, $nums, true)) $errors['q'] = 'Please choose one of your questions.';
    elseif ($a1 === '' || v_answer('Your Answer', $a1)) $errors['a1'] = v_answer('Your Answer', $a1) ?? 'Your Answer is required.';
    elseif ($a1 !== $a2) $errors['a2'] = 'Answers do not match.';
    else {
        $row = $user ? row("SELECT answer_hash FROM auth_answers WHERE id_number=? AND q_no=?", 'si', [$user['id_number'], (int)$sel]) : null;
        $ok = $row ? password_verify(strtolower($a1), $row['answer_hash']) : (password_hash($a1, PASSWORD_BCRYPT, ['cost' => 10]) && false);
        if ($ok) {
            otp_issue($user['id_number'], 'reset', $user['email'], $user['first_name']);
            $_SESSION['otp'] = ['uid' => $user['id_number'], 'purpose' => 'reset'];
            $_SESSION['fp']['stage'] = 2;
            go('reset-otp');
        }
        $secs = register_failure($key, $user, $typed, 'wrong security answer');   // counts toward the SAME lockout
        if ($secs > 0) { $lock = $secs; $msg = 'Account locked for ' . $secs . ' seconds.'; }
        else $msg = 'Incorrect answer. ' . attempts_text(attempts_left($key));
    }
}
page_start('Security question', 'other');
?>
<section class="card narrow">
<h1>Choose the following question</h1>
<p class="muted">Answer one of the questions you chose when you registered.</p>
<?= msg_html($msg) ?>
<p class="msg error hidden" id="lockMsg">Locked. Try again in <strong id="lockSec"><?= (int)$lock ?></strong> seconds.</p>
<form method="POST" action="<?= BASE ?>forgot-question" id="loginForm" novalidate data-lock="<?= (int)$lock ?>">
<?= csrf_field() ?>
<?= select_field('q', 'Question', $opts, ['value' => $sel, 'errors' => $errors, 'placeholder' => 'Choose a question']) ?>
<?= field('a1', 'Your Answer', ['errors' => $errors, 'maxlength' => 50, 'autocomplete' => 'off']) ?>
<?= field('a2', 'Re-enter Answer', ['errors' => $errors, 'maxlength' => 50, 'autocomplete' => 'off']) ?>
<button class="btn block" type="submit" data-lockable>Verify answer</button>
</form>
<p class="center small"><a href="<?= BASE ?>login" class="lockable">Back to log-in</a></p>
</section>
<?php page_end();
