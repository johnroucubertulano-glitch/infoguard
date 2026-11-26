<?php
$u = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') go('documents');
csrf_check();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$f = $id ? row('SELECT id, folder_name FROM folders WHERE id=? AND user_id=?', 'is', [$id, $u['id_number']]) : null;
if (!$f) { flash('Folder not found.', 'error'); go('documents'); }
q('DELETE FROM folders WHERE id=? AND user_id=?', 'is', [$id, $u['id_number']]);
log_event('FOLDER_DELETED', $u['username'], $u['id_number'], $f['folder_name']);
flash('Folder deleted. Its documents were moved to Unfiled.', 'success');
go('documents');