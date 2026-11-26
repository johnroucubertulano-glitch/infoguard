<?php
page_start('Home', 'home');
?>
<section class="hero">
    <h1>Keep your student documents safe.</h1>
    <p class="lead">InfoGuard is a secure document management system with verified registration, two-step log-in and active monitoring of every sign-in attempt.</p>
    <div class="hero-actions"><a class="btn" href="<?= BASE ?>register">Create an account</a><a class="btn-ghost" href="<?= BASE ?>login">Log-in</a></div>
</section>
<section class="grid-3">
    <article class="card"><h3>Verified accounts</h3><p class="muted">Every account is activated by a one-time code sent to your own email.</p></article>
    <article class="card"><h3>Two-step log-in</h3><p class="muted">A password alone is not enough. A fresh code is required each time you sign in.</p></article>
    <article class="card"><h3>Always watching</h3><p class="muted">Failed attempts lock the account, alert you by email and are reviewed by the administrator.</p></article>
</section>
<?php page_end();
