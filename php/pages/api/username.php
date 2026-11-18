<?php
header('Content-Type: application/json');
$u = trim((string)($_GET['u'] ?? ''));
if (!preg_match('/^[A-Za-z][A-Za-z0-9]{' . (USER_MIN - 1) . ',' . (USER_MAX - 1) . '}$/', $u)) { echo json_encode(['state' => 'invalid']); exit; }
echo json_encode(['state' => row("SELECT 1 FROM users WHERE username=?", 's', [$u]) ? 'taken' : 'free']);
