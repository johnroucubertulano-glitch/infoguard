<?php
/* Every page: system name ABOVE the header, links top-right, copyright footer (section 3) */
function page_start(string $title, string $page = 'other'): void {
    $auth = !empty($_SESSION['uid']);
    $navUser = $auth ? current_user() : null;
    $attr = $auth ? ' data-auth="1" data-idle="' . (IDLE_MINUTES * 60) . '" data-warn="' . ((IDLE_MINUTES - 1) * 60) . '"' : '';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>' . e($title) . ' | InfoGuard</title>'
       . '<link rel="stylesheet" href="' . BASE . 'css/style.css?v=final12"></head>'
       . '<body class="page-' . e($page) . '" data-base="' . BASE . '"' . $attr . '>'
       . '<div class="prospect"><span class="prospect-dot"></span>' . e(SYSTEM_NAME) . '</div>'
       . '<header class="site-header"><a class="brand" href="' . BASE . 'dashboard"><img src="' . BASE . 'images/logo.svg" alt="" width="30" height="30"><span>Info<span class="brand-accent">Guard</span><small>SECURE DOCUMENT PORTAL</small></span></a><nav class="nav">';
    if ($auth) {
        echo '<a href="' . BASE . 'dashboard">Dashboard</a>'
           . '<a href="' . BASE . 'documents">File Explorer</a>'
           . (!empty($navUser['role']) && $navUser['role'] === 'admin' ? '<a href="' . BASE . 'admin">Monitoring</a>' : '')
           . '<form method="POST" action="' . BASE . 'logout" id="logoutForm">' . csrf_field() . '<button type="submit" class="nav-btn">Log-out</button></form>';
    } else {
        echo '<a href="' . BASE . '">Home</a>';
        if ($page !== 'register') echo '<a href="' . BASE . 'register">Register</a>';
        if ($page !== 'login') echo '<a href="' . BASE . 'login">Log-in</a>';
    }
    echo '</nav></header><main class="container' . ($auth ? ' app-container' : '') . '">' . flash_html();
}
function page_end(array $scripts = []): void {
    echo '</main><footer class="site-footer">&copy; 2026 ' . e(OWNER) . '. All rights reserved.</footer>'
       . '<script src="' . BASE . 'js/app.js"></script>';
    foreach ($scripts as $s) echo '<script src="' . BASE . 'js/' . e($s) . '"></script>';
    echo '</body></html>';
}
function flash_html(): string {
    if (empty($_SESSION['flash'])) return '';
    [$t, $m] = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return msg_html($m, $t);
}
function msg_html(string $m, string $t = 'error'): string {
    return $m === '' ? '' : '<p class="msg ' . e($t) . '" role="alert">' . e($m) . '</p>';
}

/* Form helpers: name ABOVE the box, red * for required, red "optional", error directly under the field */
function field(string $name, string $label, array $o = []): string {
    $type = $o['type'] ?? 'text';
    $req = $o['required'] ?? true;
    $err = $o['errors'][$name] ?? '';
    $attrs = '';
    foreach (['maxlength', 'placeholder', 'autocomplete', 'inputmode'] as $a)
        if (isset($o[$a])) $attrs .= ' ' . $a . '="' . e((string)$o[$a]) . '"';
    if (!empty($o['readonly'])) $attrs .= ' readonly';
    $val = $type === 'password' ? '' : (string)($o['value'] ?? '');
    $input = '<input type="' . $type . '" name="' . $name . '" id="' . $name . '" value="' . e($val) . '"' . $attrs . ($err ? ' class="invalid"' : '') . '>';
    if ($type === 'password')
        $input = '<div class="pw-wrap">' . $input . '<button type="button" class="pw-toggle" data-toggle="' . $name . '">Show</button></div>';
    $mark = $req ? '<span class="req">*</span>' : ' <span class="opt">optional</span>';
    return '<div class="field"><label for="' . $name . '">' . e($label) . $mark . '</label>' . $input
         . ($o['after'] ?? '') . '<small class="err" data-for="' . $name . '">' . e($err) . '</small></div>';
}
function select_field(string $name, string $label, array $options, array $o = []): string {
    $req = $o['required'] ?? true;
    $err = $o['errors'][$name] ?? '';
    $sel = (string)($o['value'] ?? '');
    $h = '<option value="">' . e($o['placeholder'] ?? 'Select...') . '</option>';
    foreach ($options as $v => $t) $h .= '<option value="' . e((string)$v) . '"' . ((string)$v === $sel ? ' selected' : '') . '>' . e((string)$t) . '</option>';
    $mark = $req ? '<span class="req">*</span>' : ' <span class="opt">optional</span>';
    return '<div class="field"><label for="' . $name . '">' . e($label) . $mark . '</label><select name="' . $name . '" id="' . $name . '"' . ($err ? ' class="invalid"' : '') . '>' . $h . '</select>'
         . '<small class="err" data-for="' . $name . '">' . e($err) . '</small></div>';
}
function captcha_block(array $errors = []): string {
    $err = $errors['captcha'] ?? '';
    return '<div class="field"><label for="captcha">Security check<span class="req">*</span></label><div class="captcha-row">'
         . '<img src="' . BASE . 'captcha?' . time() . '" alt="CAPTCHA" id="captchaImg" width="160" height="50">'
         . '<button type="button" class="btn-ghost" id="captchaRefresh">Refresh</button></div>'
         . '<input type="text" name="captcha" id="captcha" maxlength="6" autocomplete="off" placeholder="Type the characters"' . ($err ? ' class="invalid"' : '') . '>'
         . '<small class="err" data-for="captcha">' . e($err) . '</small></div>';
}
