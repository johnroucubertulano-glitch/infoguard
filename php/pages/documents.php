<?php
$u = require_login();
$uid = (string)$u['id_number'];
$folders = rows('SELECT id, folder_name FROM folders WHERE user_id=? ORDER BY folder_name', 's', [$uid]);
$activeFolder = filter_input(INPUT_GET, 'folder', FILTER_VALIDATE_INT) ?: 0;
if ($activeFolder && !row('SELECT id FROM folders WHERE id=? AND user_id=?', 'is', [$activeFolder, $uid])) $activeFolder = 0;
$search = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT d.*, f.folder_name FROM documents d LEFT JOIN folders f ON f.id=d.folder_id WHERE d.user_id=?';
$types = 's'; $params = [$uid];
if ($activeFolder) { $sql .= ' AND d.folder_id=?'; $types .= 'i'; $params[] = $activeFolder; }
if ($search !== '') { $sql .= ' AND d.original_name LIKE ?'; $types .= 's'; $params[] = '%' . $search . '%'; }
$sql .= ' ORDER BY d.uploaded_at DESC';
$docs = rows($sql, $types, $params);
$total = (int)(row('SELECT COUNT(*) n FROM documents WHERE user_id=?', 's', [$uid])['n'] ?? 0);
$bytes = (int)(row('SELECT COALESCE(SUM(file_size),0) n FROM documents WHERE user_id=?', 's', [$uid])['n'] ?? 0);
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_folder'])) {
    csrf_check();
    $folderName = trim((string)($_POST['folder_name'] ?? ''));
    if ($folderName === '' || mb_strlen($folderName) > 80 || preg_match('/[\\\\\/<>:"|?*]/u', $folderName)) {
        $msg = 'Enter a valid folder name (1–80 characters).';
    } else {
        try { q('INSERT INTO folders(user_id, folder_name) VALUES(?,?)', 'ss', [$uid, $folderName]); log_event('FOLDER_CREATED', $u['username'], $uid, $folderName); flash('Folder created.', 'success'); go('documents'); }
        catch (mysqli_sql_exception $e) { $msg = $e->getCode() === 1062 ? 'A folder with that name already exists.' : 'The folder could not be created.'; }
    }
}
page_start('My Documents', 'other');
?>
<section class="welcome"><p class="eyebrow">Secure file workspace</p><h1>My Documents</h1><p class="lead">Upload and organize your school documents in your private workspace.</p></section>
<?php if ($msg) echo msg_html($msg); ?>
<div class="cards">
 <div class="stat"><span>Total documents</span><strong><?= $total ?></strong></div>
 <div class="stat"><span>Storage used</span><strong><?= e(number_format($bytes / 1048576, 2)) ?> MB</strong></div>
 <div class="stat"><span>My folders</span><strong><?= count($folders) ?></strong></div>
</div>
<section class="card">
 <div class="section-heading"><div><h2>Upload a document</h2><p class="muted small">PDF, JPG, PNG, DOC, DOCX, or TXT · Maximum 10 MB per file</p></div></div>
 <form method="POST" action="<?= BASE ?>upload" enctype="multipart/form-data" class="upload-form">
  <?= csrf_field() ?>
  <div class="field"><label for="document">Choose file</label><input type="file" id="document" name="document" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.txt"></div>
  <div class="field"><label for="folder_id">Save in folder</label><select name="folder_id" id="folder_id"><option value="">Unfiled</option><?php foreach ($folders as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e($f['folder_name']) ?></option><?php endforeach; ?></select></div>
  <button class="btn" type="submit">Upload securely</button>
 </form>
</section>
<section class="card">
 <div class="section-heading"><div><h2>Folders</h2><p class="muted small">Keep documents organized by subject or requirement.</p></div></div>
 <div class="folder-list"><a class="folder-chip <?= !$activeFolder ? 'selected' : '' ?>" href="<?= BASE ?>documents">All documents</a><?php foreach ($folders as $f): ?><a class="folder-chip <?= $activeFolder === (int)$f['id'] ? 'selected' : '' ?>" href="<?= BASE ?>documents?folder=<?= (int)$f['id'] ?>">📁 <?= e($f['folder_name']) ?></a><?php endforeach; ?></div>
 <form method="POST" class="folder-create"><?= csrf_field() ?><input type="text" name="folder_name" maxlength="80" placeholder="New folder name" required><button class="btn-ghost" type="submit" name="create_folder" value="1">Create folder</button></form>
</section>
<section class="card">
 <div class="section-heading"><div><h2><?= $activeFolder ? 'Folder documents' : 'All documents' ?></h2><p class="muted small">Only you can access files uploaded to your account.</p></div>
 <form method="GET" action="<?= BASE ?>documents" class="search-form"><input type="search" name="q" value="<?= e($search) ?>" placeholder="Search documents"><button class="btn-ghost">Search</button></form></div>
 <?php if (!$docs): ?><div class="empty-state"><div class="empty-icon">📄</div><h3>No documents found</h3><p class="muted">Upload a document to get started, or choose another folder.</p></div><?php else: ?>
 <div class="table-wrap"><table><thead><tr><th>Document</th><th>Folder</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr></thead><tbody>
 <?php foreach ($docs as $d): ?><tr><td><strong>📄 <?= e($d['original_name']) ?></strong><div class="muted small"><?= e($d['mime_type']) ?></div></td><td><?= e($d['folder_name'] ?: 'Unfiled') ?></td><td><?= e(number_format((int)$d['file_size'] / 1024, 1)) ?> KB</td><td><?= e($d['uploaded_at']) ?></td><td class="actions"><a class="btn-ghost compact" href="<?= BASE ?>download?id=<?= (int)$d['id'] ?>">Download</a><form method="POST" action="<?= BASE ?>delete-document" onsubmit="return confirm('Delete this document permanently?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="btn-danger compact" type="submit">Delete</button></form></td></tr><?php endforeach; ?>
 </tbody></table></div><?php endif; ?>
</section>
<?php page_end();
