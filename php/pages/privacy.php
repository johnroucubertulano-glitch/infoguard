<?php
page_start('Privacy Notice', 'other');
?>
<section class="card prose">
<h1>Privacy Notice</h1>
<p class="muted">Data Privacy Act of 2012 (Republic Act No. 10173)</p>
<h3>What we collect and why</h3>
<ul>
<li><strong>ID Number, name, birthdate:</strong> to identify the student and confirm the user is an adult.</li>
<li><strong>Address:</strong> to identify the student's residence for school records.</li>
<li><strong>Email:</strong> to send one-time codes and security alerts.</li>
<li><strong>Username, password, security answers:</strong> to protect the account. Passwords and answers are hashed and cannot be read by anyone.</li>
<li><strong>Emergency Contact Number (sensitive):</strong> to reach someone if the student needs help. It is encrypted in the database.</li>
<li><strong>Program and Date Enrolled:</strong> to link the account to the correct student record.</li>
<li><strong>Remarks (optional):</strong> for notes the student wants on file.</li>
<li><strong>IP address and browser:</strong> recorded in the activity log to detect attacks.</li>
</ul>
<h3>How it is protected</h3>
<p>Hashed passwords and answers, an encrypted sensitive field, prepared SQL statements, CSRF tokens, CAPTCHA, account lockout, IP blocking, and an append-only activity log.</p>
<h3>How long we keep it</h3>
<p>Account data is kept while the account is active. Activity logs are kept for the school year for security review.</p>
<h3>Contact</h3>
<p>For questions or requests to correct or delete your data, email the system administrator.</p>
<p><a class="btn-ghost" href="<?= BASE ?>register">Back to registration</a></p>
</section>
<?php page_end();
