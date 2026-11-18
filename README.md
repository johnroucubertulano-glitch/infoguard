# InfoGuard - Secure Student Document Management System
IT 107 Final Laboratory Project (PHP, JavaScript, HTML, CSS, MySQL).

This package retains the existing authentication and read-only security monitoring features and adds a student-facing private document workspace.

## Main features
- Registration, email OTP verification, secure login, CAPTCHA, password recovery, lockout, and alert emails.
- Student dashboard and private document workspace: create folders, upload, search, download, and delete documents.
- Files stored with random server-side names in a directory denied by Apache; authenticated downloads verify ownership and SHA-256 integrity.
- Session validation, idle timeout, CSRF checks, no-store response headers, and back-forward-cache reload handling.
- Read-only administrator security monitoring dashboard and monitoring script.

The interface uses a responsive professional layout with the student document workspace as the primary dashboard feature. See `docs/STEPS.md` for installation and testing. Real secrets live in `C:\\xampp\\infoguard_private\\config.php` (never committed).

**Important:** Back/Forward may show a page while the same login session is still valid. After logout or session expiration, protected pages must redirect to login. No browser-side script can replace server-side authorization.


## Revised class-wide security settings (Section 2.3)
- Username: 6–20 characters.
- Password: minimum 8 characters (the implementation also applies a 64-character maximum).
- Each name field: maximum 50 characters.
- Forgot Password link appears after 2 failed sign-ins in the current login session.
- Account lockout: 3 failures = 15 seconds; 3 additional failures = 30 seconds; 3 additional failures = 60 seconds.
- OTP expiry: 5 minutes.
- Idle session timeout: 15 minutes.
- IP block threshold: 10 failed sign-ins within 10 minutes across any accounts; the implementation blocks the IP for 10 minutes.
- Each registrant selects 3 different authentication questions from the question bank.

The instructor's revised announcement removes the student-number seed formulas and seed-table documentation requirement. Do not include the old seed table in the final report. All other project requirements remain applicable.
