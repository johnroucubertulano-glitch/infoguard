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
        $m->CharSet = 'UTF-8';
        $m->setFrom((string)cfg('mail_from'), 'InfoGuard');
        $m->addAddress($to);
        $m->isHTML(true);
        $m->Subject = $subject;
        $m->Body = $html;
        // Plain-text copy for mail apps that do not show HTML
        $alt = preg_replace('/<\/(tr|p|div|h1|li)>/i', "$0\n", $html);
        $m->AltBody = trim(preg_replace("/\n\s*\n+/", "\n\n", preg_replace('/[ \t]+/', ' ', strip_tags($alt))));
        $m->send();
        return true;
    } catch (Throwable $e) {
        error_log('Mail error: ' . $e->getMessage());
        return false;
    }
}

/* ---------- Email design ----------
   Mail apps ignore external CSS, so email styles must be written inline. This applies to the
   email only; the web pages themselves still use css/style.css with no inline styles.
   $lead and $footer are HTML: callers escape any user text with e() first. */
function email_layout(string $title, string $lead, string $content, string $tone = 'info', string $footer = ''): string {
    $accent = ['info' => '#6366f1', 'warn' => '#f87171', 'ok' => '#63d6a0'][$tone] ?? '#6366f1';
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:#0b1020;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b1020;padding:28px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="560" cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;background:#111827;border:1px solid #273250;border-radius:16px;overflow:hidden;font-family:Segoe UI,Arial,sans-serif;">'
        . '<tr><td bgcolor="#3b2a8f" style="background:#3b2a8f;background-image:linear-gradient(135deg,#312e81,#4c1d95);padding:26px 32px;">'
        . '<div style="font-size:22px;font-weight:700;color:#e0e7ff;">&#128737; InfoGuard</div>'
        . '<div style="font-size:12px;color:#c4b5fd;margin-top:5px;">' . e(SYSTEM_NAME) . '</div></td></tr>'
        . '<tr><td style="height:3px;line-height:3px;font-size:0;background:' . $accent . ';">&nbsp;</td></tr>'
        . '<tr><td style="padding:30px 32px 8px;">'
        . '<h1 style="margin:0 0 8px;font-size:22px;line-height:1.3;color:#f8fafc;">' . e($title) . '</h1>'
        . '<p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#94a3b8;">' . $lead . '</p>'
        . $content . '</td></tr>'
        . '<tr><td style="padding:22px 32px 28px;">'
        . ($footer !== '' ? '<p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#cbd5e1;">' . $footer . '</p>' : '')
        . '<p style="margin:0;font-size:12px;color:#64748b;border-top:1px solid #273250;padding-top:14px;">This is an automated message from InfoGuard. Please do not reply to it.</p>'
        . '</td></tr></table></td></tr></table></body></html>';
}
function email_code(string $code): string {
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="background:#0f172a;border:1px solid #334155;border-radius:12px;padding:20px 10px;">'
         . '<div style="font-family:Consolas,Menlo,monospace;font-size:38px;font-weight:700;letter-spacing:12px;color:#a5b4fc;">' . e($code) . '</div></td></tr></table>';
}
function email_notice(string $text, string $tone = 'warn'): string {
    $c = $tone === 'ok' ? ['#10231b', '#1f6f4a', '#86efac'] : ['#2a1417', '#7f1d1d', '#fca5a5'];
    return '<div style="background:' . $c[0] . ';border:1px solid ' . $c[1] . ';border-radius:10px;padding:14px 16px;font-size:14px;line-height:1.6;color:' . $c[2] . ';">' . e($text) . '</div>';
}
function email_details(array $rows): string {
    $h = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;background:#0f172a;border:1px solid #273250;border-radius:10px;">';
    foreach ($rows as $label => $value)
        $h .= '<tr><td style="padding:10px 14px;font-size:12px;color:#94a3b8;width:38%;vertical-align:top;border-bottom:1px solid #1c2540;">' . e((string)$label) . '</td>'
            . '<td style="padding:10px 14px;font-size:13px;color:#f1f5f9;word-break:break-word;border-bottom:1px solid #1c2540;">' . e((string)$value) . '</td></tr>';
    return $h . '</table>';
}
function email_button(string $label, string $url): string {
    return '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:22px auto 0;"><tr><td align="center" bgcolor="#6366f1" style="background:#6366f1;border-radius:10px;">'
         . '<a href="' . e($url) . '" style="display:inline-block;padding:13px 28px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;font-family:Segoe UI,Arial,sans-serif;">' . e($label) . '</a></td></tr></table>';
}