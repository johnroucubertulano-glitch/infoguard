<?php
$u = require_login();
if ($u['role'] !== 'admin') {
    log_event('ADMIN_ACCESS_DENIED', $u['username'], $u['id_number']);
    http_response_code(403);
    page_start('Access denied', 'other');
    echo '<section class="card narrow center"><h1>Access denied</h1><p class="muted">You do not have permission to view this page.</p><a class="btn" href="' . BASE . 'dashboard">Back to dashboard</a></section>';
    page_end();
    exit;
}
log_event('ADMIN_VIEWED_DASHBOARD', $u['username'], $u['id_number']);
page_start('Security monitoring', 'other');
?>
<section class="welcome"><p class="eyebrow">Administrator &middot; read-only</p><h1>Security monitoring</h1><p class="lead">Live view of the activity log. Refreshes every 60 seconds.</p></section>
<section class="cards" id="cards"></section>
<section class="card"><h2>Failed log-ins per hour (last 24 hours)</h2><canvas id="chart" height="220"></canvas></section>
<div class="grid-2">
<section class="card"><h2>Top IP addresses (24 hours)</h2><div class="table-wrap"><table id="ipTable"><thead><tr><th>IP address</th><th>Failures</th><th>Status</th></tr></thead><tbody></tbody></table></div></section>
<section class="card"><h2>Flags from monitor.php</h2><div class="table-wrap"><table id="flagTable"><thead><tr><th>Level</th><th>Pattern</th><th>Target</th><th>Details</th></tr></thead><tbody></tbody></table></div></section>
</div>
<section class="card"><h2>Recent events</h2>
<form id="filters" class="filters" novalidate>
<div class="field"><label for="date_from">From</label><input type="date" name="date_from" id="date_from"></div>
<div class="field"><label for="date_to">To</label><input type="date" name="date_to" id="date_to"></div>
<div class="field"><label for="etype">Event type</label><input type="text" name="etype" id="etype" placeholder="LOGIN_FAILED" maxlength="40"></div>
<div class="field"><label for="uname">Username</label><input type="text" name="uname" id="uname" maxlength="50"></div>
<div class="field"><label for="fip">IP address</label><input type="text" name="fip" id="fip" maxlength="45"></div>
<button class="btn" type="submit">Apply</button>
</form>
<div class="table-wrap"><table id="eventTable"><thead><tr><th>Time</th><th>Event</th><th>Username typed</th><th>User ID</th><th>IP address</th><th>Browser</th><th>Details</th></tr></thead><tbody></tbody></table></div>
</section>
<?php page_end(['admin.js']);
