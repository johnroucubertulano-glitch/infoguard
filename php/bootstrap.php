<?php
declare(strict_types=1);

const BASE        = '/infoguard/';
const SYSTEM_NAME = 'InfoGuard: Secure Student Document Management System';
const OWNER       = 'John Roucu C. bertulano';

/* Class-wide settings (handout section 2.3) */
const USER_MIN = 6;   const USER_MAX = 20;
const PASS_MIN = 8;   const PASS_MAX = 64;
const NAME_MAX = 50;
const FORGOT_AFTER = 2;
const LOCK_TIERS = [1 => 15, 2 => 30, 3 => 60];   // seconds
const OTP_MINUTES = 5;
const IDLE_MINUTES = 15;
const IP_THRESHOLD = 10;

/* Secrets come from a config file OUTSIDE the web root (protection 11) */
function cfg(string $key) {
    static $c = null;
    if ($c === null) $c = require dirname(__DIR__, 3) . '/infoguard_private/config.php';
    return $c[$key] ?? null;
}

/* Error handling: users never see PHP/SQL errors (protection 10) */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (cfg('error_log')) ini_set('error_log', (string)cfg('error_log'));
date_default_timezone_set('Asia/Manila');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
set_exception_handler(function (Throwable $e) {
    error_log((string)$e);
    http_response_code(500);
    echo 'Something went wrong. Please try again later.';
});

if (PHP_SAPI !== 'cli') {
    /* Session hardening (protection 6) */
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true,
        'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();

    /* Security headers (protection 12) + no caching so Back never shows a protected page */
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    /* Idle timeout. The admin auto-refresh endpoint does not count as activity. */
    if (isset($_SESSION['uid'])) {
        if (time() - ($_SESSION['last'] ?? 0) > IDLE_MINUTES * 60) {
            $_SESSION = [];
            session_destroy();
            session_start();
            session_regenerate_id(true);
            $_SESSION['flash'] = ['info', 'You were logged out because of inactivity.'];
        } elseif (($_GET['p'] ?? '') !== 'api-admin-data') {
            $_SESSION['last'] = time();
        }
    }
}

/* ---------- Database (prepared statements only: protection 1) ---------- */
function db(): mysqli {
    static $c = null;
    if ($c === null) {
        $c = new mysqli((string)cfg('db_host'), (string)cfg('db_user'), (string)cfg('db_pass'), (string)cfg('db_name'));
        $c->set_charset('utf8mb4');
        $c->query("SET time_zone='+08:00'");
    }
    return $c;
}
function q(string $sql, string $types = '', array $p = []): mysqli_stmt {
    $s = db()->prepare($sql);
    if ($types !== '') $s->bind_param($types, ...$p);
    $s->execute();
    return $s;
}
function row(string $sql, string $types = '', array $p = []): ?array {
    return q($sql, $types, $p)->get_result()->fetch_assoc() ?: null;
}
function rows(string $sql, string $types = '', array $p = []): array {
    return q($sql, $types, $p)->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ---------- Small helpers ---------- */
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }   // XSS (protection 2)
function ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
function ua(): string { return substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 255); }
function go(string $route = ''): void { header('Location: ' . BASE . $route); exit; }
function flash(string $m, string $t = 'info'): void { $_SESSION['flash'] = [$t, $m]; }
function base_url(): string { return rtrim((string)(cfg('base_url') ?: 'http://localhost/infoguard'), '/') . '/'; }

/* ---------- CSRF (protection 3) ---------- */
function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        log_event('CSRF_REJECTED', null, null, (string)($_GET['p'] ?? ''));
        http_response_code(403);
        exit('Invalid request.');
    }
}

/* ---------- Activity log: INSERT only (protection 9) ---------- */
function log_event(string $type, ?string $typed = null, ?string $uid = null, string $details = ''): void {
    q("INSERT INTO activity_log(event_type,username_typed,user_id,ip_address,user_agent,details) VALUES(?,?,?,?,?,?)",
      'ssssss', [$type, $typed !== null ? substr($typed, 0, 100) : null, $uid, ip(), ua(), substr($details, 0, 255)]);
}

/* ---------- CAPTCHA: verified on the server, single use ---------- */
function captcha_ok(): bool {
    $h = $_SESSION['captcha'] ?? '';
    unset($_SESSION['captcha']);
    $in = strtolower(trim((string)($_POST['captcha'] ?? '')));
    return $h !== '' && $in !== '' && hash_equals($h, hash_hmac('sha256', $in, (string)cfg('captcha_secret')));
}

/* ---------- Encryption at rest, AES-256-CBC with a random IV (protection 13) ---------- */
function enc_key(): string { return hash('sha256', (string)cfg('encryption_key'), true); }   // 32 bytes
function enc(string $plain): string {
    $iv = random_bytes(16);
    return base64_encode($iv . openssl_encrypt($plain, 'aes-256-cbc', enc_key(), OPENSSL_RAW_DATA, $iv));
}
function dec(string $blob): string {
    $raw = base64_decode($blob, true) ?: '';
    return (string)openssl_decrypt(substr($raw, 16), 'aes-256-cbc', enc_key(), OPENSSL_RAW_DATA, substr($raw, 0, 16));
}

/* ---------- Current user ---------- */
function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    return row("SELECT * FROM users WHERE id_number=? AND is_verified=1", 's', [$_SESSION['uid']]);
}
function require_login(): array {
    $u = current_user();
    if (!$u) go('login');
    return $u;
}

require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/layout.php';
