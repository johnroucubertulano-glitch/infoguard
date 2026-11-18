<?php
// SAMPLE ONLY (fake values). This file IS committed to Git.
// The REAL file is named config.php and lives OUTSIDE the web root:
//   C:\xampp\infoguard_private\config.php
return [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'Security_lab',

    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'yourname@gmail.com',
    'smtp_pass' => 'xxxx xxxx xxxx xxxx',      // Gmail App Password
    'mail_from' => 'yourname@gmail.com',

    'captcha_secret' => 'fake-captcha-secret',
    'encryption_key' => 'fake-32-byte-key-change-me-0000',
    'error_log'      => 'C:/xampp/infoguard_private/error.log',
    'base_url'       => 'http://localhost/infoguard',
];
