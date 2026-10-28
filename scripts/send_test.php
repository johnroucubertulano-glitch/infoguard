<?php
// Checkpoint 1 test email. Run from the command line:
//   php scripts/send_test.php yourname@gmail.com
if (PHP_SAPI !== 'cli') { http_response_code(404); exit('Page Not Found'); }

require __DIR__ . '/../libs/PHPMailer/Exception.php';
require __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require __DIR__ . '/../libs/PHPMailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;

// Real config lives outside the web root: C:\xampp\infoguard_private\config.php
$cfg = require dirname(__DIR__, 3) . '/infoguard_private/config.php';
$to = $argv[1] ?? $cfg['smtp_user'];

try {
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host = $cfg['smtp_host'];
    $m->Port = $cfg['smtp_port'];
    $m->SMTPAuth = true;
    $m->Username = $cfg['smtp_user'];
    $m->Password = $cfg['smtp_pass'];
    $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $m->setFrom($cfg['mail_from'], 'InfoGuard');
    $m->addAddress($to);
    $m->Subject = 'InfoGuard test email';
    $m->Body = "This is the Checkpoint 1 test email.\nSent: " . date('Y-m-d H:i:s') . ' PHT';
    $m->send();
    echo "Email sent to $to\n";
} catch (Throwable $e) {
    echo 'Failed: ' . $e->getMessage() . "\n";
}
