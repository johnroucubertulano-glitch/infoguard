<?php
$u = require_login();
$uid = (string)$u['id_number'];
$total = (int)(row('SELECT COUNT(*) n FROM documents WHERE user_id=?', 's', [$uid])['n'] ?? 0);
$bytes = (int)(row('SELECT COALESCE(SUM(file_size),0) n FROM documents WHERE user_id=?', 's', [$uid])['n'] ?? 0);
$folderCount = (int)(row('SELECT COUNT(*) n FROM folders WHERE user_id=?', 's', [$uid])['n'] ?? 0);
$recentFiles = rows('SELECT id, original_name, mime_type, file_size, uploaded_at FROM documents WHERE user_id=? ORDER BY uploaded_at DESC LIMIT 5', 's', [$uid]);
$recent = rows("SELECT event_type, ip_address, created_at FROM activity_log WHERE user_id=? ORDER BY id DESC LIMIT 5", 's', [$uid]);
$name = trim($u['first_name'] . ' ' . ($u['middle_name'] ? $u['middle_name'] . ' ' : '') . $u['family_name'] . ' ' . ($u['name_ext'] ?? ''));
page_start('Dashboard', 'other');
?>
<div class="dash-head">
 <div><p class="eyebrow">STUDENT WORKSPACE</p><h1>Good day, <?= e($u['first_name']) ?> <span class="wave">✦</span></h1><p class="lead">Your documents, organized and protected in one place.</p></div>
 <a class="btn" href="<?= BASE ?>documents">＋ Open file explorer</a>
</div>
<div class="cards dashboard-stats">
 <div class="stat stat-doc"><span class="stat-icon">▤</span><span>Total documents</span><strong><?= $total ?></strong><small>Files in your workspace</small></div>
 <div class="stat stat-folder"><span class="stat-icon">▰</span><span>Folders</span><strong><?= $folderCount ?></strong><small>Your personal organization</small></div>
 <div class="stat stat-storage"><span class="stat-icon">◉</span><span>Storage used</span><strong><?= e(number_format($bytes / 1048576, 2)) ?> <small>MB</small></strong><small>Across all uploaded files</small></div>
</div>
<section class="card workspace-card">
 <div class="section-heading"><div><p class="eyebrow">YOUR PRIVATE SPACE</p><h2>My documents</h2><p class="muted small">Upload, organize, and securely access your school files.</p></div><a class="btn-ghost" href="<?= BASE ?>documents">View all files&nbsp; →</a></div>
 <?php if (!$recentFiles): ?>
 <div class="empty-state"><div class="empty-icon">▤</div><h3>Your workspace is ready</h3><p class="muted">You haven't uploaded any documents yet. Start by adding your first school file.</p><a class="btn" href="<?= BASE ?>documents">Upload your first document</a></div>
 <?php else: ?>
 <div class="table-wrap"><table><thead><tr><th>NAME</th><th>FILE TYPE</th><th>SIZE</th><th>DATE ADDED</th><th></th></tr></thead><tbody>
 <?php foreach ($recentFiles as $f): ?><tr><td><span class="file-mark">▤</span><strong><?= e($f['original_name']) ?></strong></td><td><?= e(strtoupper(pathinfo($f['original_name'], PATHINFO_EXTENSION))) ?></td><td><?= e(number_format((int)$f['file_size']/1024, 1)) ?> KB</td><td><?= e(date('M j, Y', strtotime($f['uploaded_at']))) ?></td><td><a class="table-link" href="<?= BASE ?>download?id=<?= (int)$f['id'] ?>">Download</a></td></tr><?php endforeach; ?>
 </tbody></table></div>
 <?php endif; ?>
</section>
<div class="dashboard-bottom">
 <section class="card"><div class="section-heading"><div><h2>Account security</h2><p class="muted small">Your account protection at a glance.</p></div><span class="secure-pill">● Protected</span></div>
 <div class="security-list"><div><span class="security-check">✓</span><span><strong>Verified account</strong><small>Your account is verified.</small></span></div><div><span class="security-check">✓</span><span><strong>Private file access</strong><small>Files are restricted to your account.</small></span></div><div><span class="security-check">✓</span><span><strong>Session protection</strong><small>Automatic idle timeout is enabled.</small></span></div></div>
 <a class="btn-ghost" href="<?= BASE ?>change-password">Change password</a>
 </section>
 <section class="card"><div class="section-heading"><div><h2>Recent activity</h2><p class="muted small">Latest account events.</p></div></div>
 <?php if (!$recent): ?><p class="muted small">No recent activity to display.</p><?php else: ?><div class="activity-list"><?php foreach ($recent as $r): ?><div><span class="activity-dot"></span><span><strong><?= e(str_replace('_',' ', $r['event_type'])) ?></strong><small><?= e($r['created_at']) ?> · <?= e($r['ip_address']) ?></small></span></div><?php endforeach; ?></div><?php endif; ?>
 </section>
</div>
<section class="card profile-card"><div class="section-heading"><div><h2>Student information</h2><p class="muted small">Your registered account details.</p></div></div><dl class="info">
<div><dt>ID Number</dt><dd><?= e($u['id_number']) ?></dd></div><div><dt>Full name</dt><dd><?= e($name) ?></dd></div><div><dt>Program</dt><dd><?= e($u['program']) ?></dd></div><div><dt>Date enrolled</dt><dd><?= e($u['date_enrolled']) ?></dd></div><div><dt>Email</dt><dd><?= e($u['email']) ?></dd></div><div><dt>Birthdate</dt><dd><?= e($u['birthdate']) ?></dd></div>
</dl></section>
<?php page_end();
