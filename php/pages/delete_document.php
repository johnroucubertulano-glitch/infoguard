<?php
$u = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') go('documents');
csrf_check();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$d = $id ? row('SELECT id, original_name, stored_name FROM documents WHERE id=? AND user_id=?', 'is', [$id, $u['id_number']]) : null;
if (!$d) { flash('Document not found.', 'error'); go('documents'); }
q('DELETE FROM documents WHERE id=? AND user_id=?', 'is', [$id, $u['id_number']]);
$path = dirname(__DIR__, 2) . '/storage/files/' . $d['stored_name'] . '.bin';
if (is_file($path)) @unlink($path);
log_event('DOCUMENT_DELETED', $u['username'], $u['id_number'], $d['original_name']);
flash('Document deleted.', 'success');
go('documents');
