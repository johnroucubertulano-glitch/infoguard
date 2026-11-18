<?php
use PHPMailer\PHPMailer\PHPMailer;

function send_mail(string $to, string $subject, string $html): bool {
    try {
        require_once dirname(__DIR__) . '/libs/PHPMailer/Exception.php';
        require_once dirname(__DIR__) . '/libs/PHPMailer/PHPMailer.php';
        require_once dirname(__DIR__) . '/libs/PHPMailer/SMTP.php';
        $m = new PHPMailer(true);
        $m->isSMTP();
        $m->Host = (string)cfg('smtp_host');
        $m->Port = (int)cfg('smtp_port');
        $m->SMTPAuth = true;
        $m->Username = (string)cfg('smtp_user');
        $m->Password = (string)cfg('smtp_pass');
        $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $m->setFrom((string)cfg('mail_from'), 'InfoGuard');
        $m->addAddress($to);
        $m->isHTML(true);
        $m->Subject = $subject;
        $m->Body = $html;
        $m->AltBody = trim(strip_tags($html));
        $m->send();
        return true;
    } catch (Throwable $e) {
        error_log('Mail error: ' . $e->getMessage());
        return false;
    }
}
