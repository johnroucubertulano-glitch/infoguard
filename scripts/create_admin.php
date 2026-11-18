<?php
// Creates the admin account directly in the database (never through the registration form).
// Usage: C:\xampp\php\php.exe scripts\create_admin.php 0000-0001 adminuser "Admin@Pass1234" you@gmail.com
if (PHP_SAPI !== 'cli') { http_response_code(404); exit('Page Not Found'); }
require __DIR__ . '/../php/bootstrap.php';
[$self, $id, $user, $pass, $email] = array_pad($argv, 5, '');
if (!preg_match('/^\d{4}-\d{4}$/', $id) || !$user || v_password($pass) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Usage: php scripts/create_admin.php 0000-0001 adminuser \"StrongPass@123\" you@gmail.com\n");
}
$hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
q("INSERT INTO users(id_number,first_name,family_name,birthdate,street,barangay,municipality,province,country,zip,email,username,password_hash,role,is_verified,consent_at,emergency_enc,program,date_enrolled)
   VALUES(?,'System','Administrator','1990-01-01','Admin Office','Admin','Butuan City','Agusan del Norte','Philippines','8600',?,?,?, 'admin',1,NOW(),?,'BSIT',CURDATE())",
  'sssss', [$id, strtolower($email), $user, $hash, enc('09000000000')]);
q("INSERT INTO password_history(id_number,password_hash) VALUES(?,?)", 'ss', [$id, $hash]);
echo "Admin '$user' created. Log in at " . base_url() . "login (an OTP will be emailed to $email).\n";
