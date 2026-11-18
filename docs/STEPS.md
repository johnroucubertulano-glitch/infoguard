# InfoGuard: step by step

## A. Install (do once)

### Revised Section 2.3 settings
Use the same class-wide values for every student; do not calculate D1, D2, or any `mod` formula:
- Username length: 6–20 characters
- Password minimum: 8 characters (implementation maximum: 64)
- Each name field maximum: 50 characters
- Forgot Password appears after 2 failed log-ins
- Lockout tiers: 3 failures = 15 seconds; 3 more = 30 seconds; 3 more = 60 seconds
- OTP expiry: 5 minutes
- Idle session timeout: 15 minutes
- IP block: 10 failed log-ins in 10 minutes across any account (implementation block duration: 10 minutes)
- User selects 3 different authentication questions from the Section 5 bank

The revised announcement removes the seed table from documentation. Do not include the old seed table in your final documentation. System Proposal and Checkpoint 1 remain unchanged.


1. Copy everything in this folder INTO `C:\xampp\htdocs\infoguard\` (overwrite `.htaccess`, `.gitignore`, `config.sample.php`). Keep your `libs\PHPMailer\` folder.
2. Make sure `C:\xampp\infoguard_private\config.php` exists (copy of `config.sample.php` with your real Gmail, App Password and keys). Add the line `'base_url' => 'http://localhost/infoguard',` if it is missing.
3. XAMPP: start Apache + MySQL. In `php.ini` enable `extension=gd`, `extension=fileinfo`, `extension=openssl`, then restart Apache.
4. Apache must allow .htaccess and mod_rewrite: in `C:\xampp\apache\conf\httpd.conf` make sure `LoadModule rewrite_module` has no `#`, and `AllowOverride All` is set for htdocs. Restart Apache.
5. For a NEW/empty project database only: phpMyAdmin -> Import `schema.sql` (this DROPS/recreates project tables). If you already have InfoGuard accounts/data, DO NOT import `schema.sql`; instead select the existing `Security_lab` database and import `migrations/001_document_management.sql` to add the document tables without deleting existing records.
6. Create the admin (command prompt in the project folder):
   `C:\xampp\php\php.exe scripts\create_admin.php 0000-0001 adminuser "Admin@Pass1234" yourgmail@gmail.com`
7. Open http://localhost/infoguard/

## B. Checkpoint 2: Registration
- Register with ONE bad input first (e.g. First Name `juan`), see the message under the field.
- Turn JavaScript off, submit bad data: the server must still refuse.
- Tick privacy consent, solve CAPTCHA, submit. You get an email OTP, enter it, account is verified.

## C. Checkpoint 3: Log-in, lockout, recovery
- Log-in -> OTP email -> dashboard. Admin lands on /admin.
- 2 wrong passwords: "Forgot Password? Reset Here" appears. 3 wrong: 15 s lockout, 6: 30 s, 9: 60 s. Alert email arrives (once per tier).
- Forgot Password: username + CAPTCHA -> choose question -> answer -> OTP -> new password (not one of the last 3).
- Typing /login-otp, /reset-otp, /change-password directly must send you back.

## D. Checkpoint 4: Protections, dashboard, script
- Fail log-in 10+ times from one IP with different usernames -> IP blocked 10 min.
- Admin opens /admin: cards, bar chart, top IPs, flags, events with filters.
- Run `C:\xampp\php\php.exe scripts\monitor.php`, refresh the dashboard to see flags.

## E. Git: commit as you go (one clear message per step)
```
git add .
git commit -m "Add database schema"
git push
```
Suggested commits: schema, bootstrap/config, validation, registration page, OTP, CAPTCHA, login, lockout, alerts, forgot password, change password, admin dashboard, monitor script, CSS redesign, docs. Commit honestly when you actually do the work.

## F. Documents you still must write (section 16.2)
Threat Model (Appendix B), Design Decisions (every "Your call"), Privacy note, Error Message Mapping, Development Journal, test accounts, SQL dump with 5+ test records.

## G. Files to study for the 6-minute explanation
php/security.php (lockout, IP block, OTP), php/validation.php, php/pages/login.php, php/pages/change_password.php, php/bootstrap.php (headers, session, CSRF, encryption), scripts/monitor.php.

## H. Student document workspace
- After signing in as a student, open **My Documents** in the header or from the dashboard.
- Create folders, upload permitted PDF/JPG/PNG/DOC/DOCX/TXT files (maximum 10 MB), download, search, and delete your own files.
- Uploaded files are stored under `storage/files/` with random server-side names. Direct web access is denied; downloads pass through an authenticated PHP route that checks ownership and SHA-256 integrity.
- The updated `schema.sql` adds `folders` and `documents` tables for fresh installs. Existing installs should run `migrations/001_document_management.sql` instead; it adds only the two new tables and preserves existing records.

## I. Browser history and session checks
- Protected pages send `Cache-Control: no-store` and other no-cache headers. The browser `pageshow` handler reloads a protected page restored from the back-forward cache.
- Every protected route calls `require_login()` and checks the current verified user on the server. After logout or idle expiration, Back/Forward, refresh, and direct URLs must be rejected or redirected to log-in.
- While the user is still logged in, Back/Forward may legitimately return to a protected page. This is not a security bypass; the server must reject it after the session ends.
