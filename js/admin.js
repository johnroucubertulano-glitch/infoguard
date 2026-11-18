// Read-only dashboard. Every value is written with textContent, so attacker-typed usernames can never run as code.
(function () {
  const base = document.body.dataset.base || '/';
  const filters = document.getElementById('filters');

  function cell(tr, text, cls) {
    const td = document.createElement('td');
    td.textContent = text == null ? '' : String(text);
    if (cls) { td.textContent = ''; const s = document.createElement('span'); s.className = cls; s.textContent = text; td.appendChild(s); }
    tr.appendChild(td);
  }
  function fill(id, rows, builder) {
    const body = document.querySelector('#' + id + ' tbody');
    body.textContent = '';
    if (!rows.length) { const tr = document.createElement('tr'); const td = document.createElement('td'); td.colSpan = 8; td.className = 'muted'; td.textContent = 'Nothing to show.'; tr.appendChild(td); body.appendChild(tr); return; }
    rows.forEach(function (r) { const tr = document.createElement('tr'); builder(tr, r); body.appendChild(tr); });
  }
  function drawChart(data) {
    const c = document.getElementById('chart');
    const w = c.clientWidth, h = 220, dpr = window.devicePixelRatio || 1;
    c.width = w * dpr; c.height = h * dpr;
    const g = c.getContext('2d'); g.scale(dpr, dpr); g.clearRect(0, 0, w, h);
    const max = Math.max(5, ...data.map(function (d) { return d.count; }));
    const left = 34, bottom = 26, top = 10, bw = (w - left) / data.length;
    g.font = '11px Segoe UI, Arial'; g.fillStyle = '#94a3b8'; g.strokeStyle = '#273250';
    for (let i = 0; i <= 4; i++) {
      const y = top + (h - bottom - top) * (1 - i / 4);
      g.beginPath(); g.moveTo(left, y); g.lineTo(w, y); g.stroke();
      g.fillText(Math.round(max * i / 4), 4, y + 4);
    }
    data.forEach(function (d, i) {
      const bh = (h - bottom - top) * d.count / max, x = left + i * bw + 3;
      g.fillStyle = '#6366f1'; g.fillRect(x, h - bottom - bh, bw - 6, bh);
      if (d.count) { g.fillStyle = '#e2e8f0'; g.fillText(d.count, x + (bw - 6) / 2 - 3, h - bottom - bh - 4); }
      if (i % 3 === 0) { g.fillStyle = '#94a3b8'; g.fillText(d.label, x - 4, h - 8); }
    });
  }
  function render(d) {
    const cards = document.getElementById('cards'); cards.textContent = '';
    Object.keys(d.summary).forEach(function (k) {
      const s = document.createElement('div'); s.className = 'stat';
      const l = document.createElement('span'); l.textContent = k;
      const v = document.createElement('strong'); v.textContent = d.summary[k];
      s.appendChild(l); s.appendChild(v); cards.appendChild(s);
    });
    drawChart(d.hourly);
    fill('ipTable', d.top, function (tr, r) { cell(tr, r.ip); cell(tr, r.count); cell(tr, r.blocked ? 'Blocked' : 'Not blocked', 'badge ' + (r.blocked ? 'high' : 'ok')); });
    fill('flagTable', d.flags, function (tr, r) { cell(tr, r.severity, 'badge ' + r.severity.toLowerCase()); cell(tr, r.pattern); cell(tr, r.target); cell(tr, r.details + ' (' + r.flag_date + ')'); });
    fill('eventTable', d.events, function (tr, r) { cell(tr, r.created_at); cell(tr, r.event_type); cell(tr, r.username_typed); cell(tr, r.user_id); cell(tr, r.ip_address); cell(tr, r.user_agent); cell(tr, r.details); });
  }
  function load() {
    const qs = new URLSearchParams(new FormData(filters)).toString();
    fetch(base + 'api-admin-data?' + qs, { credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
      .then(render).catch(function () {});
  }
  filters.addEventListener('submit', function (e) { e.preventDefault(); load(); });
  window.addEventListener('resize', load);
  load();
  setInterval(load, 60000);   // auto-refresh every 60 seconds
})();
