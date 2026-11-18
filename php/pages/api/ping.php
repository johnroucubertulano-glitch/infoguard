<?php
header('Content-Type: application/json');
echo json_encode(['ok' => !empty($_SESSION['uid'])]);   // bootstrap already refreshed the idle timer
