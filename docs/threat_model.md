# Threat Model (Appendix B)

**System:** InfoGuard: Secure Student Document Management System
**Name:** John Roucu C. Bertulano

**CIA key:** C = Confidentiality (only the right people can read it), I = Integrity (nobody can change it wrongly), A = Availability (the system stays usable).

## Main table: one row per protection in section 12

| # | Threat | Vulnerability | Countermeasure | CIA | How I tested it |
|---|---|---|---|---|---|
| 1 | Attacker injects SQL through a form field | User input joined into an SQL string | Prepared statements for every query (`q()`, `row()`, `rows()` in `php/bootstrap.php`) | C, I | Typed `' OR '1'='1` as the username at log-in. The page showed "Invalid username or password.", with no SQL error and no log-in. |
| 2 | Attacker runs a script in another user's or the admin's browser (XSS) | Output printed without escaping, including attacker-typed log data | `e()` (`htmlspecialchars`) on all output, `textContent` in `js/admin.js`, Content-Security-Policy with `script-src 'self'` | C, I | Typed `<script>alert(1)</script>` as a username and in registration fields, then opened `/admin`. It appeared as plain text and no alert popped up. |
| 3 | A malicious site makes the user's browser send a forged request (CSRF) | Forms accept a POST without proof it came from our page | Secret token in every POST form (`csrf_field()`, `csrf_check()`), `SameSite=Strict` cookie, `CSRF_REJECTED` logged | I | Deleted the hidden `csrf` field with browser tools and submitted. The server answered "Invalid request." and `CSRF_REJECTED` appeared in the activity log. |
| 4 | Attacker finds out which usernames exist (enumeration) | Different messages for "no such user" and "wrong password" | One message, "Invalid username or password.", in log-in and Forgot Password; believable fake security questions for unknown users; the same password-hash work either way | C | Compared a real username with a wrong password against a made-up username. Both showed the identical message. |
| 5 | Attacker guesses passwords across many accounts from one machine | A per-account limit alone would miss this | IP blocking: 10 failed log-ins in 10 minutes from one IP blocks it for 10 minutes (`ip_block_seconds()`, `ip_blocks` table, `IP_BLOCKED` logged) | C, A | Tried many different usernames from one browser until the 10th failure. The page showed "Too many attempts from your network" with a countdown, and the IP appeared as blocked on the dashboard. |
| 6 | Attacker steals, fixes or reuses a session; a user leaves a session open | Session ID kept after log-in, no timeout, cookie readable by scripts | New session ID only after the OTP is correct, `HttpOnly` + `SameSite=Strict` cookie, 15-minute idle timeout with a warning, log-out destroys the session, no-cache headers | C | Stayed idle past the timeout and pressed Back after log-out. I was sent to the log-in page and the protected page was not shown. |
| 7 | Attacker or user reuses an old or leaked password | Old passwords can be set again | Hashes of the last 3 passwords kept in `password_history` and checked on every change | C | Changed the password to the current one. The page showed "You cannot reuse a recent password." |
| 8 | A bot mass-registers fake accounts | Forms are easy for scripts to fill in | Hidden honeypot field `website`; if it has a value the server refuses silently | I, A | Filled the hidden field with browser tools and submitted. No account was created. |
| 9 | Attacks go unnoticed, or someone denies what they did | No record of events | Every event written with time, IP and browser by `log_event()` (`activity_log`, insert only), shown on the admin dashboard | I | Did a log-in, a failed log-in and a lockout, then read all of them in "Recent events" on `/admin`. |
| 10 | Attacker learns system details from error messages | PHP or SQL errors shown to the user | `display_errors` off, errors written to a log file outside the web root, generic message to the user | C | Sent broken input and briefly stopped MySQL. The user only saw "Something went wrong."; the details were in the error log. |
| 11 | Passwords or keys leak from source code or Git | Secrets stored inside the project or committed | `config.php` outside the web root (`C:\xampp\infoguard_private`), listed in `.gitignore`, only `config.sample.php` committed | C | Searched the Git history for my App Password and encryption key. Neither was found. |
| 12 | Clickjacking, or the browser guessing a file's type | Page can be framed; type sniffing | `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, CSP `frame-ancestors 'none'` on every page | I | Checked the response headers in the browser Network tab. All three headers were present. |
| 13 | Someone who copies the database reads sensitive data | Sensitive field stored as plain text | Emergency Contact Number encrypted with AES-256-CBC and a random IV (`enc()` and `dec()`), decrypted only to show the owner | C | Opened `users.emergency_enc` in phpMyAdmin. It showed unreadable text, while the owner sees the number on the dashboard. |

## Extra rows (defences that are not in the 13 but are part of the system)

| Threat | Vulnerability | Countermeasure | CIA | How I tested it |
|---|---|---|---|---|
| Attacker guesses one account's password | Unlimited attempts | Progressive lockout kept in the database: 3, 6 and 9 failures lock for 15, 30 and 60 seconds; alert email once per tier | C, A | Failed 3, 6 and 9 times, then reloaded and cleared cookies. The countdown stayed and I could not log in until it ended. |
| Attacker has the password but not the mailbox | Password alone gives access | Email OTP: 6 digits, hashed, 5-minute expiry, 3 tries, single use, no session before it is correct | C | Entered 3 wrong codes and then the right one, which was refused; an expired code was also refused. |
| Attacker skips a step by typing a later URL | Pages that do not check earlier steps | Server-side session state for OTP, security question and Change Password; direct visits are sent back | C, I | Typed `/login-otp` and `/change-password` directly. Both sent me back to the start. |
| Bot logs in or registers automatically | No proof of a human | Image CAPTCHA checked on the server, single use | A | Deleted the CAPTCHA field and submitted. The server refused with "Incorrect CAPTCHA." |
| Attacker reads source files by URL | Files reachable in the address bar | `.htaccess` returns "Page Not Found" for `.php`, `scripts/` and direct `.js` requests | C | Opened `/php/router.php` and `/scripts/monitor.php` in the browser. Both showed "Page Not Found". |

## Code map (for my own study before the demo)

| Protection | Where to read it |
|---|---|
| Prepared statements, headers, session, CSRF, encryption, logging | `php/bootstrap.php` |
| Lockout, IP block, OTP, alert emails | `php/security.php` |
| All registration rules and password strength | `php/validation.php` |
| Log-in flow | `php/pages/login.php`, `php/pages/login_otp.php` |
| Forgot and Change Password | `php/pages/forgot*.php`, `php/pages/reset_otp.php`, `php/pages/change_password.php` |
| Admin dashboard and its JSON | `php/pages/admin.php`, `php/pages/api/admin_data.php`, `js/admin.js` |
| Monitoring script | `scripts/monitor.php` |
| Hiding files and direct URLs | `.htaccess` |
