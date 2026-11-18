(function () {
  const base = document.body.dataset.base || '/';

  // Show / hide password
  document.querySelectorAll('[data-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      const i = document.getElementById(b.dataset.toggle);
      const show = i.type === 'password';
      i.type = show ? 'text' : 'password';
      b.textContent = show ? 'Hide' : 'Show';
    });
  });

  // CAPTCHA refresh
  const cr = document.getElementById('captchaRefresh');
  if (cr) cr.addEventListener('click', function () { document.getElementById('captchaImg').src = base + 'captcha?' + Date.now(); });

  // Password strength meter (same rules as the server)
  function strength(p) {
    const t = /[a-z]/.test(p) + /[A-Z]/.test(p) + /\d/.test(p) + /[^A-Za-z0-9]/.test(p);
    if (p.length >= 12 && t === 4) return 'Strong';
    if (p.length >= 8 && t >= 3) return 'Medium';
    return 'Weak';
  }
  const meter = document.getElementById('meter');
  if (meter) {
    const input = document.getElementById(meter.dataset.for);
    const text = document.getElementById('meterText');
    input.addEventListener('input', function () {
      const s = input.value ? strength(input.value) : '';
      meter.className = 'meter ' + s.toLowerCase();
      text.textContent = 'Strength: ' + (s || '-');
    });
  }

  // Server-enforced lockout: the page only shows the countdown the server gave it
  const lockForm = document.getElementById('loginForm');
  if (lockForm && Number(lockForm.dataset.lock) > 0) {
    let left = Number(lockForm.dataset.lock);
    const secEl = document.getElementById('lockSec');
    const box = document.getElementById('lockMsg');
    const items = document.querySelectorAll('[data-lockable], .lockable');
    const stop = function (e) { e.preventDefault(); };
    box.classList.remove('hidden');
    items.forEach(function (el) { el.classList.add('disabled'); el.setAttribute('aria-disabled', 'true'); el.setAttribute('tabindex', '-1'); el.addEventListener('click', stop); if (el.tagName === 'BUTTON') el.disabled = true; });
    const timer = setInterval(function () {
      left -= 1; secEl.textContent = Math.max(left, 0);
      if (left <= 0) {
        clearInterval(timer); box.classList.add('hidden');
        items.forEach(function (el) { el.classList.remove('disabled'); el.removeAttribute('aria-disabled'); el.removeAttribute('tabindex'); el.removeEventListener('click', stop); if (el.tagName === 'BUTTON') el.disabled = false; });
      }
    }, 1000);
  }

  // Resend OTP cooldown with a visible countdown
  const rb = document.getElementById('resendBtn');
  if (rb) {
    let w = Number(rb.dataset.wait);
    const label = 'Resend OTP';
    const tick = function () {
      if (w > 0) { rb.disabled = true; rb.textContent = label + ' (' + w + 's)'; w -= 1; setTimeout(tick, 1000); }
      else { rb.disabled = false; rb.textContent = label; }
    };
    tick();
  }

  // Back button: if the browser restores a protected page from its cache, reload so the server decides
  window.addEventListener('pageshow', function (e) { if (e.persisted && document.body.dataset.auth) location.reload(); });

  // Idle timeout: warn one minute before, then log out
  if (document.body.dataset.idle) {
    const idle = Number(document.body.dataset.idle) * 1000;
    const warn = Number(document.body.dataset.warn) * 1000;
    let t1, t2, banner;
    const arm = function () {
      clearTimeout(t1); clearTimeout(t2);
      t1 = setTimeout(showWarning, warn);
      t2 = setTimeout(function () { document.getElementById('logoutForm').submit(); }, idle);
    };
    const showWarning = function () {
      banner = document.createElement('div'); banner.className = 'idle-banner'; banner.setAttribute('role', 'alert');
      const p = document.createElement('p'); p.textContent = 'You will be logged out in 1 minute because of inactivity.';
      const b = document.createElement('button'); b.className = 'btn'; b.type = 'button'; b.textContent = 'Stay logged in';
      b.addEventListener('click', function () { fetch(base + 'api-ping', { credentials: 'same-origin' }).then(function () { banner.remove(); arm(); }); });
      banner.appendChild(p); banner.appendChild(b); document.body.appendChild(banner);
    };
    arm();
  }
})();
