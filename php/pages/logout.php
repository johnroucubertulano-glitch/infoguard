<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') go(empty($_SESSION['uid']) ? 'login' : 'dashboard');
csrf_check();
if (!empty($_SESSION['uid'])) log_event('LOGOUT', null, (string)$_SESSION['uid']);
$_SESSION = [];
setcookie(session_name(), '', time() - 3600, '/');
session_destroy();                                   // invalidate the old session
session_start();
session_regenerate_id(true);                         // issue a fresh anonymous session ID
flash('You have been logged out.', 'info');
go('login');
