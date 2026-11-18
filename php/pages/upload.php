<?php
$u = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') go('documents');
csrf_check();
$f = $_FILES['document'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK) { flash('Upload failed. Choose a file and try again.', 'error'); go('documents'); }
$max = 10 * 1024 * 1024;
if ($f['size'] < 1 || $f['size'] > $max || !is_uploaded_file($f['tmp_name'])) { flash('File must be between 1 byte and 10 MB.', 'error'); go('documents'); }
$allowed = ['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','txt'=>'text/plain'];
$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
if (!isset($allowed[$ext]) || $mime !== $allowed[$ext]) { flash('Unsupported or mismatched file type.', 'error'); go('documents'); }
$folder = filter_input(INPUT_POST, 'folder_id', FILTER_VALIDATE_INT) ?: null;
if ($folder && !row('SELECT id FROM folders WHERE id=? AND user_id=?', 'is', [$folder, $u['id_number']])) { flash('Invalid folder selection.', 'error'); go('documents'); }
$original = basename(str_replace('\\', '/', (string)$f['name']));
$original = preg_replace('/[\\x00-\\x1F\\x7F]/u', '', $original);
$original = mb_substr($original, 0, 255);
$stored = bin2hex(random_bytes(16));
$dir = dirname(__DIR__, 2) . '/storage/files';
if (!is_dir($dir) && !mkdir($dir, 0700, true)) { flash('Secure storage is unavailable.', 'error'); go('documents'); }
$target = $dir . '/' . $stored . '.bin';
if (!move_uploaded_file($f['tmp_name'], $target)) { flash('Could not save the uploaded file.', 'error'); go('documents'); }
chmod($target, 0600);
$sha = hash_file('sha256', $target);
try {
    q('INSERT INTO documents(user_id,folder_id,original_name,stored_name,mime_type,file_size,sha256) VALUES(?,?,?,?,?,?,?)', 'sisssis', [$u['id_number'], $folder, $original, $stored, $mime, (int)$f['size'], $sha]);
    log_event('DOCUMENT_UPLOADED', $u['username'], $u['id_number'], $original);
    flash('Document uploaded and stored securely.', 'success');
} catch (Throwable $e) { @unlink($target); throw $e; }
go('documents');
