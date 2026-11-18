// Browser-side checks for fast feedback. The SERVER repeats every rule, so turning JavaScript off does not help an attacker.
(function () {
  const form = document.getElementById('regForm');
  const base = document.body.dataset.base || '/';
  const NAME_MAX = 50, USER_MIN = 6, USER_MAX = 20, PASS_MIN = 8, PASS_MAX = 64;
  const PROGRAMS = ['BSIT', 'BSCS', 'BSIS', 'BSCE', 'BSEE', 'BSBA', 'BEED', 'BSED'];
  const ROMAN = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X'];
  const $ = function (n) { return form.elements[n]; };

  function titleErr(label, v) {
    if (v.length > 1 && v === v.toUpperCase() && /[A-Z]/.test(v)) return 'Do not use all capital letters.';
    for (const w of v.split(' ')) {
      if (!w) continue;
      if (!/^[A-Z][a-z]*$/.test(w)) {
        if (/^[a-z]/.test(w)) return label.includes('Name') ? 'First letter of your name must be capital.' : 'First letter of each word in ' + label + ' must be capital.';
        return 'Only the first letter of each word in ' + label + ' may be capital.';
      }
    }
    return '';
  }
  function nameErr(label, v, req) {
    if (!v) return req ? label + ' is required.' : '';
    if (v.includes('  ')) return 'Double space is not allowed.';
    if (/\d/.test(v)) return 'Numbers are not allowed in ' + label + '.';
    if (/[^A-Za-z ]/.test(v)) return 'Special characters are not allowed in ' + label + '.';
    if (v.length < 2) return label + ' must be at least 2 characters.';
    if (v.length > NAME_MAX) return label + ' must not be longer than ' + NAME_MAX + ' characters.';
    if (/(.)\1\1/i.test(v)) return 'Three consecutive identical letters are not allowed.';
    return titleErr(label, v);
  }
  function placeErr(label, v) {
    if (!v) return label + ' is required.';
    if (v.includes('  ')) return 'Double space is not allowed.';
    if (/[^A-Za-z ]/.test(v)) return label + ' may contain letters and spaces only.';
    if (v.length < 2 || v.length > 50) return label + ' must be 2 to 50 characters.';
    return titleErr(label, v);
  }
  function ansErr(label, v) {
    if (!v) return label + ' is required.';
    if (v.includes('  ')) return 'Double space is not allowed.';
    if (/[^A-Za-z ]/.test(v)) return label + ' may contain letters and spaces only.';
    if (v.length < 2 || v.length > 50) return label + ' must be 2 to 50 characters.';
    return '';
  }
  function ageOf(d) {
    const b = new Date(d + 'T00:00:00'), t = new Date();
    let a = t.getFullYear() - b.getFullYear();
    if (t.getMonth() < b.getMonth() || (t.getMonth() === b.getMonth() && t.getDate() < b.getDate())) a--;
    return a;
  }
  function validDate(v) { return /^\d{4}-\d{2}-\d{2}$/.test(v) && !isNaN(new Date(v + 'T00:00:00')); }
  function strength(p) {
    const t = /[a-z]/.test(p) + /[A-Z]/.test(p) + /\d/.test(p) + /[^A-Za-z0-9]/.test(p);
    if (p.length >= 12 && t === 4) return 'Strong';
    if (p.length >= PASS_MIN && t >= 3) return 'Medium';
    return 'Weak';
  }

  const rules = {
    id_number: function (v) { return /^\d{4}-\d{4}$/.test(v) ? '' : 'ID Number must follow the format xxxx-xxxx.'; },
    first_name: function (v) { return nameErr('First Name', v, true); },
    middle_name: function (v) { return nameErr('Middle Name', v, false); },
    family_name: function (v) { return nameErr('Family Name', v, true); },
    name_ext_other: function (v) { return $('name_ext').value !== 'Other' || ROMAN.includes(v) ? '' : 'Name Extension must be a Roman numeral such as I, II, III, IV, IX or X.'; },
    birthdate: function (v) {
      if (!validDate(v)) return 'Please enter a valid birthdate.';
      if (new Date(v + 'T00:00:00') > new Date()) return 'Birthdate cannot be in the future.';
      return ageOf(v) < 18 ? 'You must be at least 18 years old to register.' : '';
    },
    street: function (v) {
      if (!v) return 'Purok / Street is required.';
      if (v.includes('  ')) return 'Double space is not allowed.';
      if (/[^A-Za-z0-9 ]/.test(v)) return 'Purok / Street may contain letters, digits and spaces only.';
      return v.length < 3 || v.length > 50 ? 'Purok / Street must be 3 to 50 characters.' : '';
    },
    barangay: function (v) { return placeErr('Barangay', v); },
    municipality: function (v) { return placeErr('Municipality / City', v); },
    province: function (v) { return placeErr('Province', v); },
    country: function (v) { return placeErr('Country', v); },
    zip: function (v) { return /^\d{4}$/.test(v) ? '' : 'ZIP Code must be exactly 4 digits.'; },
    emergency: function (v) { return /^09\d{9}$/.test(v) ? '' : 'Contact number must be 11 digits and start with 09.'; },
    program: function (v) { return PROGRAMS.includes(v) ? '' : 'Please select a valid program.'; },
    date_enrolled: function (v) { return validDate(v) && new Date(v + 'T00:00:00') <= new Date() ? '' : 'Date Enrolled must be a valid date that is not in the future.'; },
    remarks: function (v) { return !v || (/^[A-Za-z, ]+$/.test(v) && v.length <= 100) ? '' : 'Remarks may contain letters, commas and spaces only (maximum 100 characters).'; },
    email: function (v) { return v.length <= 100 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? '' : 'Please enter a valid email address.'; },
    username: function (v) {
      if (!/^[A-Za-z]/.test(v)) return 'Username must start with a letter.';
      if (/[^A-Za-z0-9]/.test(v)) return 'Username may contain letters and digits only.';
      return v.length < USER_MIN || v.length > USER_MAX ? 'Username must be ' + USER_MIN + ' to ' + USER_MAX + ' characters.' : '';
    },
    password: function (v) {
      if (v.length < PASS_MIN) return 'Password must be at least ' + PASS_MIN + ' characters.';
      if (v.length > PASS_MAX) return 'Password must not be longer than ' + PASS_MAX + ' characters.';
      return strength(v) === 'Weak' ? 'Password is too weak. Use upper and lower case letters, numbers and symbols.' : '';
    },
    confirm: function (v) { return v === $('password').value ? '' : 'Passwords do not match.'; },
    q1: function (v) { return v ? '' : 'Please choose a valid question.'; },
    q2: function (v) { return !v ? 'Please choose a valid question.' : (v === $('q1').value || v === $('q3').value ? 'Please choose three different questions.' : ''); },
    q3: function (v) { return v ? '' : 'Please choose a valid question.'; },
    a1: function (v) { return ansErr('Answer 1', v); },
    a2: function (v) { return ansErr('Answer 2', v); },
    a3: function (v) { return ansErr('Answer 3', v); },
    consent: function () { return $('consent').checked ? '' : 'You must agree to the Privacy Notice to register.'; },
    captcha: function (v) { return v ? '' : 'Please type the characters shown.'; }
  };

  function show(name, msg) {
    const el = form.querySelector('[data-for="' + name + '"]');
    if (el) el.textContent = msg;
    const input = $(name);
    if (input && input.classList) input.classList.toggle('invalid', !!msg);
  }
  function check(name) {
    const input = $(name);
    if (!input || !rules[name]) return '';
    const v = input.type === 'password' ? input.value : input.value.trim();
    const msg = rules[name](v);
    show(name, msg);
    return msg;
  }

  Object.keys(rules).forEach(function (n) {
    const el = $(n);
    if (!el) return;
    el.addEventListener('blur', function () { check(n); });
    el.addEventListener('change', function () { check(n); });
  });
  ['q1', 'q2', 'q3'].forEach(function (n) { $(n).addEventListener('change', function () { if ($('q2').value) check('q2'); }); });
  $('password').addEventListener('input', function () { if ($('confirm').value) check('confirm'); });

  // Name Extension "Other"
  const ext = $('name_ext'), wrap = document.getElementById('extOtherWrap');
  ext.addEventListener('change', function () { wrap.classList.toggle('hidden', ext.value !== 'Other'); });

  // Age is calculated automatically (read-only; the server recalculates it)
  $('birthdate').addEventListener('change', function () {
    const v = this.value;
    $('age').value = validDate(v) && new Date(v + 'T00:00:00') <= new Date() ? ageOf(v) : '';
  });

  // Live username availability
  let timer;
  const status = document.getElementById('userStatus');
  $('username').addEventListener('input', function () {
    clearTimeout(timer);
    const v = this.value.trim();
    status.className = 'hint'; status.textContent = '';
    if (!v) return;
    timer = setTimeout(function () {
      fetch(base + 'api-username?u=' + encodeURIComponent(v), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.state === 'free') { status.className = 'hint ok'; status.textContent = 'Username is available.'; }
          else if (d.state === 'taken') { status.className = 'hint bad'; status.textContent = 'This username is already taken.'; }
        }).catch(function () {});
    }, 400);
  });

  form.addEventListener('submit', function (e) {
    let bad = false;
    Object.keys(rules).forEach(function (n) { if (check(n)) bad = true; });
    if (bad) { e.preventDefault(); const f = form.querySelector('.invalid'); if (f) f.focus(); }
  });
})();
