<?php
$keys = ['id_number','first_name','middle_name','family_name','name_ext','name_ext_other','birthdate','street','barangay','municipality',
         'province','country','zip','email','username','q1','q2','q3','a1','a2','a3','emergency','program','date_enrolled','remarks','consent','website'];
$v = array_fill_keys($keys, '');
$v['country'] = 'Philippines';
$v['password'] = ''; $v['confirm'] = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($keys as $k) $v[$k] = trim((string)($_POST[$k] ?? ''));
    $v['password'] = (string)($_POST['password'] ?? '');
    $v['confirm'] = (string)($_POST['confirm'] ?? '');
    $v['email'] = strtolower($v['email']);

    if ($v['website'] !== '') go('login');             // honeypot: a bot filled the hidden field, refuse silently

    $errors = validate_registration($v);
    if (!captcha_ok()) { $errors['captcha'] = 'Incorrect CAPTCHA. Please try again.'; log_event('CAPTCHA_FAILED', $v['username']); }
    if (!isset($errors['id_number']) && row("SELECT 1 FROM users WHERE id_number=?", 's', [$v['id_number']])) $errors['id_number'] = 'This ID Number is already registered.';
    if (!isset($errors['email']) && row("SELECT 1 FROM users WHERE email=?", 's', [$v['email']])) $errors['email'] = 'This email is already registered.';
    if (!isset($errors['username']) && row("SELECT 1 FROM users WHERE username=?", 's', [$v['username']])) $errors['username'] = 'This username is already taken.';

    if (!$errors) {
        $ext = $v['name_ext'] === 'Other' ? $v['name_ext_other'] : $v['name_ext'];
        try {
            db()->begin_transaction();
            $hash = password_hash($v['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            q("INSERT INTO users(id_number,first_name,middle_name,family_name,name_ext,birthdate,street,barangay,municipality,province,country,zip,email,username,password_hash,consent_at,emergency_enc,program,date_enrolled,remarks)
               VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?,?,?,?)", str_repeat('s', 19),
              [$v['id_number'], $v['first_name'], $v['middle_name'] ?: null, $v['family_name'], $ext ?: null, $v['birthdate'], $v['street'], $v['barangay'],
               $v['municipality'], $v['province'], $v['country'], $v['zip'], $v['email'], $v['username'], $hash, enc($v['emergency']),
               $v['program'], $v['date_enrolled'], $v['remarks'] ?: null]);
            foreach ([1, 2, 3] as $i)
                q("INSERT INTO auth_answers(id_number,q_no,answer_hash) VALUES(?,?,?)", 'sis',
                  [$v['id_number'], (int)$v["q$i"], password_hash(strtolower($v["a$i"]), PASSWORD_BCRYPT, ['cost' => 10])]);
            q("INSERT INTO password_history(id_number,password_hash) VALUES(?,?)", 'ss', [$v['id_number'], $hash]);
            db()->commit();
        } catch (mysqli_sql_exception $ex) {
            db()->rollback();
            error_log((string)$ex);
            $errors['id_number'] = 'We could not save your registration. Please check your details and try again.';
        }
        if (!$errors) {
            log_event('REGISTERED', $v['username'], $v['id_number']);
            otp_issue($v['id_number'], 'register', $v['email'], $v['first_name']);
            $_SESSION['otp'] = ['uid' => $v['id_number'], 'purpose' => 'register'];
            go('verify');
        }
    }
}

$o = ['errors' => $errors];
$qopts = QUESTIONS;
$progs = array_combine(PROGRAMS, PROGRAMS);
page_start('Register', 'register');
?>
<section class="card form-card">
<h1>Create your account</h1>
<p class="muted">Fields marked <span class="req">*</span> are required.</p>
<form method="POST" action="<?= BASE ?>register" id="regForm" novalidate>
<?= csrf_field() ?>
<div class="hp" aria-hidden="true"><label for="website">Leave this empty</label><input type="text" name="website" id="website" tabindex="-1" autocomplete="off"></div>

<h2>Personal information</h2>
<div class="grid-2">
<?= field('id_number', 'ID Number', $o + ['value' => $v['id_number'], 'placeholder' => 'xxxx-xxxx', 'maxlength' => 9]) ?>
<div></div>
<?= field('first_name', 'First Name', $o + ['value' => $v['first_name'], 'maxlength' => NAME_MAX]) ?>
<?= field('middle_name', 'Middle Name', $o + ['value' => $v['middle_name'], 'required' => false, 'maxlength' => NAME_MAX]) ?>
<?= field('family_name', 'Family Name', $o + ['value' => $v['family_name'], 'maxlength' => NAME_MAX]) ?>
<div>
<?= select_field('name_ext', 'Name Extension', ['Jr.' => 'Jr.', 'Sr.' => 'Sr.', 'Other' => 'Other'], $o + ['value' => $v['name_ext'], 'required' => false, 'placeholder' => 'None']) ?>
<div id="extOtherWrap" class="<?= $v['name_ext'] === 'Other' ? '' : 'hidden' ?>"><?= field('name_ext_other', 'Other extension', $o + ['value' => $v['name_ext_other'], 'placeholder' => 'I, II, III, IV, IX, X', 'maxlength' => 5]) ?></div>
</div>
<?= field('birthdate', 'Birthdate', $o + ['type' => 'date', 'value' => $v['birthdate']]) ?>
<?= field('age', 'Age', ['type' => 'text', 'value' => $v['birthdate'] && valid_date($v['birthdate']) ? (string)age_from($v['birthdate']) : '', 'readonly' => true, 'required' => false, 'errors' => []]) ?>
</div>

<h2>Address</h2>
<div class="grid-2">
<?= field('street', 'Purok / Street', $o + ['value' => $v['street'], 'maxlength' => 50]) ?>
<?= field('barangay', 'Barangay', $o + ['value' => $v['barangay'], 'maxlength' => 50]) ?>
<?= field('municipality', 'Municipality / City', $o + ['value' => $v['municipality'], 'maxlength' => 50]) ?>
<?= field('province', 'Province', $o + ['value' => $v['province'], 'maxlength' => 50]) ?>
<?= field('country', 'Country', $o + ['value' => $v['country'], 'maxlength' => 50]) ?>
<?= field('zip', 'ZIP Code', $o + ['value' => $v['zip'], 'maxlength' => 4, 'inputmode' => 'numeric']) ?>
</div>

<h2>Student information</h2>
<div class="grid-2">
<?= field('emergency', 'Emergency Contact Number', $o + ['value' => $v['emergency'], 'placeholder' => '09xxxxxxxxx', 'maxlength' => 11, 'inputmode' => 'numeric']) ?>
<?= select_field('program', 'Program', $progs, $o + ['value' => $v['program']]) ?>
<?= field('date_enrolled', 'Date Enrolled', $o + ['type' => 'date', 'value' => $v['date_enrolled']]) ?>
<?= field('remarks', 'Remarks', $o + ['value' => $v['remarks'], 'required' => false, 'maxlength' => 100]) ?>
</div>

<h2>Account</h2>
<div class="grid-2">
<?= field('email', 'Email', $o + ['type' => 'email', 'value' => $v['email'], 'maxlength' => 100]) ?>
<?= field('username', 'Username', $o + ['value' => $v['username'], 'maxlength' => USER_MAX, 'autocomplete' => 'username', 'after' => '<small class="hint" id="userStatus"></small>']) ?>
<?= field('password', 'Password', $o + ['type' => 'password', 'maxlength' => PASS_MAX, 'autocomplete' => 'new-password', 'after' => '<div class="meter" id="meter" data-for="password"><span></span></div><small class="hint" id="meterText">Strength: -</small>']) ?>
<?= field('confirm', 'Re-enter Password', $o + ['type' => 'password', 'maxlength' => PASS_MAX, 'autocomplete' => 'new-password']) ?>
</div>

<h2>Authentication questions</h2>
<p class="muted">Choose three different questions. You will need them if you forget your password.</p>
<?php foreach ([1, 2, 3] as $i): ?>
<div class="grid-2">
<?= select_field("q$i", "Question $i", $qopts, $o + ['value' => $v["q$i"]]) ?>
<?= field("a$i", "Answer $i", $o + ['value' => $v["a$i"], 'maxlength' => 50, 'autocomplete' => 'off']) ?>
</div>
<?php endforeach; ?>

<h2>Privacy</h2>
<div class="field check">
<label><input type="checkbox" name="consent" id="consent" value="1"> I have read the <a href="<?= BASE ?>privacy" target="_blank" rel="noopener">Privacy Notice</a> and agree to the collection and processing of my personal data in accordance with the Data Privacy Act of 2012 (RA 10173).</label>
<small class="err" data-for="consent"><?= e($errors['consent'] ?? '') ?></small>
</div>

<?= captcha_block($errors) ?>
<button class="btn block" type="submit">Create account</button>
</form>
<p class="center small">Already registered? <a href="<?= BASE ?>login">Log-in</a></p>
</section>
<?php page_end(['register.js']);
