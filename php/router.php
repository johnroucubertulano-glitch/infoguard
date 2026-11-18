<?php
require __DIR__ . '/bootstrap.php';

$routes = [
    'home' => 'home', 'register' => 'register', 'privacy' => 'privacy',
    'login' => 'login', 'login-otp' => 'login_otp', 'verify' => 'verify',
    'forgot' => 'forgot', 'forgot-question' => 'forgot_question', 'reset-otp' => 'reset_otp',
    'change-password' => 'change_password', 'dashboard' => 'dashboard', 'documents' => 'documents', 'upload' => 'upload', 'download' => 'download', 'delete-document' => 'delete_document', 'admin' => 'admin', 'logout' => 'logout',
    'api-username' => 'api/username', 'api-admin-data' => 'api/admin_data', 'api-ping' => 'api/ping', 'captcha' => 'api/captcha',
];
$p = (string)($_GET['p'] ?? 'home');
if (!isset($routes[$p])) {
    http_response_code(404);
    readfile(dirname(__DIR__) . '/404.html');
    exit;
}
require __DIR__ . '/pages/' . $routes[$p] . '.php';
