<?php
// SAMPLE ONLY (fake values). This file IS committed to Git.
// The REAL file must be named config.php and live OUTSIDE the web root,
// for example:  C:\xampp\infoguard_private\config.php
// Never put real passwords or keys in this sample file.
return [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => 'fake-db-password',
    'db_name' => 'Security_lab',

    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_user' => 'yourname@gmail.com',
    'smtp_pass' => 'xxxx xxxx xxxx xxxx',   // Gmail App Password
    'mail_from' => 'yourname@gmail.com',

    'captcha_secret' => 'fake-captcha-secret',
    'encryption_key' => 'fake-32-byte-key-change-me-0000',  // exactly 32 characters for AES-256
    'error_log'      => 'C:/xampp/infoguard_private/error.log',
];
