<?php
$u = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$d = $id ? row('SELECT * FROM documents WHERE id=? AND user_id=?', 'is', [$id, $u['id_number']]) : null;
if (!$d) { http_response_code(404); exit('Document not found.'); }
$path = dirname(__DIR__, 2) . '/storage/files/' . $d['stored_name'] . '.bin';
if (!is_file($path) || !hash_equals($d['sha256'], hash_file('sha256', $path))) { log_event('DOCUMENT_INTEGRITY_FAILURE', $u['username'], $u['id_number'], 'Document ID ' . $id); http_response_code(404); exit('Document unavailable.'); }
log_event('DOCUMENT_DOWNLOADED', $u['username'], $u['id_number'], $d['original_name']);
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . rawurlencode($d['original_name']) . '"; filename*=UTF-8\'\'' . rawurlencode($d['original_name']));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
